<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('twitter_id')->nullable()->unique()->after('email')->comment('Twitter(X) ID');
        });

        // password_hash を nullable に変更（ソーシャル認証ユーザーはパスワード不要）
        DB::statement('ALTER TABLE `users` MODIFY `password_hash` VARCHAR(255) NULL DEFAULT NULL COMMENT "パスワードハッシュ"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('twitter_id');
        });

        DB::statement('ALTER TABLE `users` MODIFY `password_hash` VARCHAR(255) NOT NULL COMMENT "パスワードハッシュ"');
    }
};
