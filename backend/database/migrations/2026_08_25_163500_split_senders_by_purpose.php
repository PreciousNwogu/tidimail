<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senders', function (Blueprint $table) {
            $table->string('purpose')->default('unknown')->after('email');
        });

        Schema::table('senders', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'email']);
            $table->unique(['account_id', 'email', 'purpose']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('category')->default('unknown')->after('snippet');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('senders', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'email', 'purpose']);
            $table->unique(['account_id', 'email']);
            $table->dropColumn('purpose');
        });
    }
};
