<?php

namespace App\Services\Inbox;

use App\Enums\SenderCategory;
use App\Enums\SenderRecommendation;
use App\Models\Message;
use App\Models\Sender;
use Illuminate\Support\Collection;

class SenderClassifier
{
    /**
     * Sort one email into a purpose pile using subject, snippet, and Gmail labels — not the full body.
     *
     * @param  array{subject?: ?string, snippet?: ?string, email?: string, name?: ?string, label_ids?: array, list_unsubscribe?: ?string}  $message
     */
    public function classifyMessage(array $message): SenderCategory
    {
        $subject = (string) ($message['subject'] ?? '');
        $snippet = (string) ($message['snippet'] ?? '');
        $email = (string) ($message['email'] ?? '');
        $name = (string) ($message['name'] ?? '');
        $labels = collect($message['label_ids'] ?? []);
        $hasUnsub = filled($message['list_unsubscribe'] ?? null);
        $text = strtolower($subject.' '.$snippet);

        if ($this->textLooksLikeReceipt($text, $email, $name)) {
            return SenderCategory::Receipt;
        }

        if ($labels->contains('CATEGORY_SOCIAL')) {
            return SenderCategory::Social;
        }

        if ($labels->contains('CATEGORY_PROMOTIONS') || $this->textLooksLikePromo($text)) {
            return SenderCategory::Promo;
        }

        if ($this->looksLikePersonAddress($email, $hasUnsub, $labels)) {
            return SenderCategory::Person;
        }

        if ($hasUnsub || $labels->contains('CATEGORY_UPDATES') || $this->textLooksLikeNewsletter($text)) {
            return SenderCategory::Newsletter;
        }

        return SenderCategory::Unknown;
    }

    /**
     * @param  Collection<int, Message>|iterable<Message>  $messages
     */
    public function classify(Sender $sender, iterable $messages): ClassificationResult
    {
        $messages = collect($messages);
        $labels = $messages->flatMap(fn (Message $message) => $message->label_ids ?? [])->unique()->values();
        $text = $messages
            ->map(fn (Message $message) => trim($message->subject.' '.$message->snippet))
            ->filter()
            ->implode(' ');
        $gmailCategories = $labels
            ->filter(fn (string $label) => str_starts_with($label, 'CATEGORY_'))
            ->values()
            ->all();

        $unreadRatio = $sender->message_count > 0
            ? $sender->unread_count / $sender->message_count
            : 0;

        $purpose = $sender->purpose instanceof SenderCategory
            ? $sender->purpose
            : SenderCategory::tryFrom((string) $sender->purpose);

        if ($purpose === SenderCategory::Receipt || $this->textLooksLikeReceipt($text, $sender->email, (string) $sender->name)) {
            return new ClassificationResult(
                SenderCategory::Receipt,
                SenderRecommendation::Keep,
                'This pile looks like receipts, invoices, or shipping — not ads.',
                $gmailCategories,
            );
        }

        if ($purpose === SenderCategory::Person || $this->looksLikePerson($sender, $labels)) {
            return new ClassificationResult(
                SenderCategory::Person,
                SenderRecommendation::Keep,
                'Looks like a person, not a list.',
                $gmailCategories,
            );
        }

        if ($purpose === SenderCategory::Social || $labels->contains('CATEGORY_SOCIAL')) {
            return new ClassificationResult(
                SenderCategory::Social,
                SenderRecommendation::Digest,
                'Social notifications are better as a digest than inbox noise.',
                $gmailCategories,
            );
        }

        $isPromo = $purpose === SenderCategory::Promo
            || $labels->contains('CATEGORY_PROMOTIONS')
            || $this->textLooksLikePromo($text);

        if ($isPromo && $sender->has_list_unsubscribe && ($sender->message_count >= 3 || $unreadRatio >= 0.7)) {
            return new ClassificationResult(
                SenderCategory::Promo,
                SenderRecommendation::Unsubscribe,
                'Promotional mail with an unsubscribe header; you rarely open it.',
                $gmailCategories,
            );
        }

        if ($sender->has_list_unsubscribe || $isPromo || $labels->contains('CATEGORY_UPDATES') || $purpose === SenderCategory::Newsletter) {
            return new ClassificationResult(
                $isPromo ? SenderCategory::Promo : SenderCategory::Newsletter,
                SenderRecommendation::Digest,
                'Recurring mail you can read later instead of in the inbox.',
                $gmailCategories,
            );
        }

        return new ClassificationResult(
            SenderCategory::Unknown,
            SenderRecommendation::Digest,
            'Not clearly personal; we suggest moving it out of the inbox.',
            $gmailCategories,
        );
    }

    public function splitReason(ClassificationResult $result): ClassificationResult
    {
        $result->reason .= ' Other mail from this address is filed in a separate pile.';

        return $result;
    }

    private function textLooksLikeReceipt(string $text, string $email, string $name): bool
    {
        $haystack = strtolower($email.' '.$name.' '.$text);

        return (bool) preg_match(
            '/\b(receipt|invoice|order confirmation|payment received|your payment|your order|statement|shipping|shipped|tracking|billing|order #)/i',
            $haystack
        );
    }

    private function textLooksLikePromo(string $text): bool
    {
        return (bool) preg_match('/% off|\bsale\b|\bdeal\b|\bpromo\b|\boffer\b|\bdiscount\b|prime day|lightning deal/i', $text);
    }

    private function textLooksLikeNewsletter(string $text): bool
    {
        return (bool) preg_match('/\bissue\s+\d+|newsletter|this week in|weekly digest/i', $text);
    }

    private function looksLikePersonAddress(string $email, bool $hasUnsub, Collection $labels): bool
    {
        if ($hasUnsub) {
            return false;
        }

        if ($labels->contains('CATEGORY_PROMOTIONS') || $labels->contains('CATEGORY_SOCIAL')) {
            return false;
        }

        if (preg_match('/^(no-?reply|notifications?|news|offers|marketing|hello|info|team|auto-confirm|store-news)@/i', $email)) {
            return false;
        }

        return true;
    }

    private function looksLikePerson(Sender $sender, Collection $labels): bool
    {
        if ($sender->has_list_unsubscribe) {
            return false;
        }

        if ($labels->contains('CATEGORY_PROMOTIONS') || $labels->contains('CATEGORY_SOCIAL')) {
            return false;
        }

        if (preg_match('/^(no-?reply|notifications?|news|offers|marketing|hello|info|team|auto-confirm|store-news)@/i', $sender->email)) {
            return false;
        }

        return $sender->message_count <= 3;
    }
}
