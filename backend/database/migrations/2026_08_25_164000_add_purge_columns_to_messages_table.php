<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('purge_at')->nullable()->after('list_unsubscribe');
            $table->timestamp('purged_at')->nullable()->after('purge_at');
            $table->index(['purge_at', 'purged_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['purge_at', 'purged_at']);
            $table->dropColumn(['purge_at', 'purged_at']);
        });
    }
};
