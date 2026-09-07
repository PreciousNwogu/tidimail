<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained()->cascadeOnDelete();
            $table->string('gmail_id');
            $table->string('thread_id')->nullable();
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->boolean('is_read')->default(true);
            $table->boolean('is_in_inbox')->default(false);
            $table->json('label_ids')->nullable();
            $table->text('list_unsubscribe')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'gmail_id']);
            $table->index(['sender_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
