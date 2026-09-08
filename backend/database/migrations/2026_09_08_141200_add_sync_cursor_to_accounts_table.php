<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('sync_phase')->nullable()->after('sync_scanned_count');
            $table->text('sync_page_token')->nullable()->after('sync_phase');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['sync_phase', 'sync_page_token']);
        });
    }
};
