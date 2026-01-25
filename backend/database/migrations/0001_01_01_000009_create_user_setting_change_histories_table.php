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
        Schema::create('user_setting_change_histories', function (Blueprint $table) {
            $table->comment('ユーザー設定変更履歴');

            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_setting_id')->index()->comment('設定ID');
            $table->unsignedTinyInteger('history_type')->comment('タイプ(1:create,2:update,3:delete)');

            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->json('settings_json')->comment('設定データ（JSON形式）');
            $table->timestamp('created_at')->nullable()->comment('作成日時');
        });

        DB::unprepared(
            '
            CREATE TRIGGER trigger_user_settings_create AFTER INSERT ON `user_settings`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_setting_change_histories
                    SET user_setting_id = NEW.id,
                        history_type    = 1,
                        user_id         = NEW.user_id,
                        settings_json   = NEW.settings_json,
                        created_at      = NEW.created_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_user_settings_update AFTER UPDATE ON `user_settings`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_setting_change_histories
                    SET user_setting_id = NEW.id,
                        history_type    = 2,
                        user_id         = NEW.user_id,
                        settings_json   = NEW.settings_json,
                        created_at      = NEW.updated_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_user_settings_delete AFTER DELETE ON `user_settings`
            FOR EACH ROW
                BEGIN
                    INSERT INTO user_setting_change_histories
                    SET user_setting_id = OLD.id,
                        history_type    = 3,
                        user_id         = OLD.user_id,
                        settings_json   = OLD.settings_json,
                        created_at      = NOW();
                END
            '
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_user_settings_create');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_user_settings_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_user_settings_delete');
        Schema::dropIfExists('user_setting_change_histories');
    }
};
