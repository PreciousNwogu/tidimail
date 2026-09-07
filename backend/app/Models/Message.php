<?php

namespace App\Models;

use App\Enums\SenderCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'sender_id',
        'gmail_id',
        'thread_id',
        'subject',
        'snippet',
        'received_at',
        'is_read',
        'is_in_inbox',
        'label_ids',
        'list_unsubscribe',
        'category',
        'purge_at',
        'purged_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_read' => 'boolean',
        'is_in_inbox' => 'boolean',
        'label_ids' => 'array',
        'category' => SenderCategory::class,
        'purge_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }
}
