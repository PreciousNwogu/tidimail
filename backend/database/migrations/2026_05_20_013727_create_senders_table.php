<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('has_list_unsubscribe')->default(false);
            $table->text('list_unsubscribe_header')->nullable();
            $table->string('list_unsubscribe_post')->nullable();
            $table->string('category')->default('unknown');
            $table->string('recommendation')->default('digest');
            $table->text('recommendation_reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->json('gmail_categories')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'email']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senders');
    }
};
