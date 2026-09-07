<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('gmail');
            $table->string('google_id');
            $table->string('email');
            $table->string('name')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('last_history_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('idle');
            $table->text('sync_error')->nullable();
            $table->json('gmail_label_ids')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'google_id']);
            $table->unique(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
