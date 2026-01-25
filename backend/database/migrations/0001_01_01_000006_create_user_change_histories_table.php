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
        Schema::create('user_change_histories', function (Blueprint $table) {
            $table->comment('ユーザー変更履歴');

            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->index()->comment('ユーザーID');
            $table->unsignedTinyInteger('history_type')->comment('タイプ(1:create,2:update,3:delete)');

            $table->string('email')->comment('メールアドレス');
            $table->string('password_hash')->comment('パスワードハッシュ');
            $table->string('nickname', 50)->comment('ニックネーム');
            $table->timestamp('created_at')->nullable()->comment('作成日時');
        });

        DB::unprepared(
            '
            CREATE TRIGGER trigger_users_create AFTER INSERT ON `users`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_change_histories
                    SET user_id       = NEW.id,
                        history_type  = 1,
                        email         = NEW.email,
                        password_hash = NEW.password_hash,
                        nickname      = NEW.nickname,
                        created_at    = NEW.created_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_users_update AFTER UPDATE ON `users`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_change_histories
                    SET user_id       = NEW.id,
                        history_type  = 2,
                        email         = NEW.email,
                        password_hash = NEW.password_hash,
                        nickname      = NEW.nickname,
                        created_at    = NEW.updated_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_users_delete AFTER DELETE ON `users`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_change_histories
                    SET user_id       = OLD.id,
                        history_type  = 3,
                        email         = OLD.email,
                        password_hash = OLD.password_hash,
                        nickname      = OLD.nickname,
                        created_at    = NOW();
                END
            '
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_users_create');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_users_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_users_delete');
        Schema::dropIfExists('user_change_histories');
    }
};
