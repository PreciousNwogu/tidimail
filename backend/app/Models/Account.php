<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'google_id',
        'email',
        'name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_history_id',
        'last_synced_at',
        'sync_status',
        'sync_error',
        'sync_scanned_count',
        'sync_phase',
        'sync_page_token',
        'cleanup_pending_count',
        'cleanup_alert_at',
        'gmail_label_ids',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'cleanup_alert_at' => 'datetime',
        'gmail_label_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function senders(): HasMany
    {
        return $this->hasMany(Sender::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function inboxActions(): HasMany
    {
        return $this->hasMany(InboxAction::class);
    }

    public function labelId(string $key): ?string
    {
        return $this->gmail_label_ids[$key] ?? null;
    }

    public function setLabelId(string $key, string $labelId): void
    {
        $labels = $this->gmail_label_ids ?? [];
        $labels[$key] = $labelId;
        $this->gmail_label_ids = $labels;
    }
}
