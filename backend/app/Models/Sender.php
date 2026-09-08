<?php

namespace App\Models;

use App\Enums\SenderCategory;
use App\Enums\SenderRecommendation;
use App\Enums\SenderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Sender extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'email',
        'purpose',
        'name',
        'domain',
        'message_count',
        'unread_count',
        'first_seen_at',
        'last_message_at',
        'has_list_unsubscribe',
        'list_unsubscribe_header',
        'list_unsubscribe_post',
        'category',
        'recommendation',
        'recommendation_reason',
        'status',
        'reviewed_at',
        'trash_unsubscribed_immediately',
        'gmail_categories',
    ];

    protected $casts = [
        'message_count' => 'integer',
        'unread_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_message_at' => 'datetime',
        'has_list_unsubscribe' => 'boolean',
        'reviewed_at' => 'datetime',
        'trash_unsubscribed_immediately' => 'boolean',
        'gmail_categories' => 'array',
        'purpose' => SenderCategory::class,
        'category' => SenderCategory::class,
        'recommendation' => SenderRecommendation::class,
        'status' => SenderStatus::class,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function inboxActions(): HasMany
    {
        return $this->hasMany(InboxAction::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SenderStatus::Pending);
    }

    public function displayName(): string
    {
        $base = $this->name ?: $this->email;
        $purpose = $this->purpose ?? $this->category;

        return match ($purpose) {
            SenderCategory::Receipt => $base.' · receipts',
            SenderCategory::Promo => $base.' · promotions',
            SenderCategory::Social => $base.' · social',
            default => $base,
        };
    }

    public function httpUnsubscribeUrl(): ?string
    {
        if (! $this->list_unsubscribe_header) {
            return null;
        }

        if (preg_match_all('/<([^>]+)>/', $this->list_unsubscribe_header, $matches)) {
            foreach ($matches[1] as $target) {
                if (str_starts_with(strtolower($target), 'https://') || str_starts_with(strtolower($target), 'http://')) {
                    return $target;
                }
            }
        }

        if (filter_var($this->list_unsubscribe_header, FILTER_VALIDATE_URL)) {
            return $this->list_unsubscribe_header;
        }

        return null;
    }

    public function supportsOneClickUnsubscribe(): bool
    {
        return $this->httpUnsubscribeUrl() !== null
            && is_string($this->list_unsubscribe_post)
            && str_contains(strtolower($this->list_unsubscribe_post), 'one-click');
    }
}
