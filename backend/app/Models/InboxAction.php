<?php

namespace App\Models;

use App\Enums\InboxActionStatus;
use App\Enums\InboxActionType;
use App\Enums\SenderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'sender_id',
        'type',
        'status',
        'gmail_message_ids',
        'previous_label_ids',
        'previous_sender_status',
        'metadata',
        'expires_at',
        'undone_at',
    ];

    protected $casts = [
        'type' => InboxActionType::class,
        'status' => InboxActionStatus::class,
        'gmail_message_ids' => 'array',
        'previous_label_ids' => 'array',
        'previous_sender_status' => SenderStatus::class,
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    public function scopeUndoable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                InboxActionStatus::Applied->value,
                InboxActionStatus::Queued->value,
                InboxActionStatus::Confirmed->value,
            ])
            ->whereNull('undone_at')
            ->where(function (Builder $inner) {
                $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function canBeUndone(): bool
    {
        if ($this->undone_at !== null || $this->status === InboxActionStatus::Undone) {
            return false;
        }

        if (! in_array($this->status, [
            InboxActionStatus::Applied,
            InboxActionStatus::Queued,
            InboxActionStatus::Confirmed,
            InboxActionStatus::Failed,
        ], true)) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
