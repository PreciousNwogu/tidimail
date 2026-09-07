<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('cleanup_pending_count')->default(0)->after('sync_scanned_count');
            $table->timestamp('cleanup_alert_at')->nullable()->after('cleanup_pending_count');
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->timestamps();

            $table->unique(['user_id', 'public_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['cleanup_pending_count', 'cleanup_alert_at']);
        });
    }
};
