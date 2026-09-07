<?php

namespace Tests\Unit;

use App\Enums\SenderCategory;
use App\Enums\SenderRecommendation;
use App\Models\Message;
use App\Models\Sender;
use App\Services\Inbox\SenderClassifier;
use Tests\TestCase;

class SenderClassifierTest extends TestCase
{
    public function test_it_recommends_unsubscribe_for_unread_promos_with_list_header(): void
    {
        $sender = new Sender([
            'email' => 'deals@shop.com',
            'name' => 'Shop',
            'domain' => 'shop.com',
            'message_count' => 8,
            'unread_count' => 8,
            'has_list_unsubscribe' => true,
        ]);

        $messages = collect([
            new Message(['subject' => '50% off today', 'label_ids' => ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS']]),
            new Message(['subject' => 'Weekend sale', 'label_ids' => ['INBOX', 'UNREAD', 'CATEGORY_PROMOTIONS']]),
        ]);

        $result = (new SenderClassifier)->classify($sender, $messages);

        $this->assertSame(SenderCategory::Promo, $result->category);
        $this->assertSame(SenderRecommendation::Unsubscribe, $result->recommendation);
        $this->assertStringContainsString('unsubscribe', strtolower($result->reason));
    }

    public function test_it_keeps_people_and_receipts(): void
    {
        $classifier = new SenderClassifier;

        $person = new Sender([
            'email' => 'jordan@example.com',
            'name' => 'Jordan',
            'domain' => 'example.com',
            'message_count' => 2,
            'unread_count' => 1,
            'has_list_unsubscribe' => false,
        ]);

        $personResult = $classifier->classify($person, collect([
            new Message(['subject' => 'Lunch tomorrow?', 'label_ids' => ['INBOX', 'UNREAD']]),
        ]));

        $this->assertSame(SenderRecommendation::Keep, $personResult->recommendation);
        $this->assertSame(SenderCategory::Person, $personResult->category);

        $receipts = new Sender([
            'email' => 'receipts@store.com',
            'name' => 'Store',
            'domain' => 'store.com',
            'message_count' => 4,
            'unread_count' => 0,
            'has_list_unsubscribe' => false,
        ]);

        $receiptResult = $classifier->classify($receipts, collect([
            new Message(['subject' => 'Your invoice for March', 'label_ids' => ['INBOX', 'CATEGORY_UPDATES']]),
        ]));

        $this->assertSame(SenderRecommendation::Keep, $receiptResult->recommendation);
        $this->assertSame(SenderCategory::Receipt, $receiptResult->category);
    }

    public function test_it_digests_newsletters_that_are_not_noisy_enough_to_unsubscribe(): void
    {
        $sender = new Sender([
            'email' => 'hello@newsletter.com',
            'name' => 'Weekly',
            'domain' => 'newsletter.com',
            'message_count' => 2,
            'unread_count' => 1,
            'has_list_unsubscribe' => true,
        ]);

        $result = (new SenderClassifier)->classify($sender, collect([
            new Message(['subject' => 'This week in design', 'label_ids' => ['INBOX', 'CATEGORY_UPDATES']]),
        ]));

        $this->assertSame(SenderRecommendation::Digest, $result->recommendation);
        $this->assertSame(SenderCategory::Newsletter, $result->category);
    }

    public function test_it_splits_amazon_promos_from_invoices_using_subject_and_snippet(): void
    {
        $classifier = new SenderClassifier;

        $this->assertSame(
            SenderCategory::Promo,
            $classifier->classifyMessage([
                'subject' => 'Prime Day: 40% off',
                'snippet' => 'Lightning deals on headphones.',
                'email' => 'auto-confirm@amazon.com',
                'name' => 'Amazon',
                'label_ids' => ['INBOX', 'CATEGORY_PROMOTIONS'],
                'list_unsubscribe' => '<https://amazon.com/unsub>',
            ])
        );

        $this->assertSame(
            SenderCategory::Receipt,
            $classifier->classifyMessage([
                'subject' => 'Your invoice for March',
                'snippet' => 'Payment received for order #114-883.',
                'email' => 'auto-confirm@amazon.com',
                'name' => 'Amazon',
                'label_ids' => ['INBOX', 'CATEGORY_UPDATES'],
            ])
        );
    }
}
