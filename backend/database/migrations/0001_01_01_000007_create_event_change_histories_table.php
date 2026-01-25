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
        Schema::create('event_change_histories', function (Blueprint $table) {
            $table->comment('イベント変更履歴');

            $table->id()->comment('ID');
            $table->string('event_id', 50)->index()->comment('イベントID');
            $table->unsignedTinyInteger('history_type')->comment('タイプ(1:create,2:update,3:delete)');

            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->string('name', 100)->comment('イベント名');
            $table->string('category_icon', 20)->comment('カテゴリーアイコン');
            $table->string('last_executed_history_id', 50)->nullable()->comment('最終実行履歴ID');
            $table->timestamp('created_at')->nullable()->comment('作成日時');
        });

        DB::unprepared(
            '
            CREATE TRIGGER trigger_events_create AFTER INSERT ON `events`
            FOR EACH ROW
                BEGIN
                    INSERT INTO event_change_histories
                    SET event_id                  = NEW.id,
                        history_type              = 1,
                        user_id                   = NEW.user_id,
                        name                      = NEW.name,
                        category_icon             = NEW.category_icon,
                        last_executed_history_id  = NEW.last_executed_history_id,
                        created_at                = NEW.created_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_events_update AFTER UPDATE ON `events`
            FOR EACH ROW
                BEGIN
                    INSERT INTO event_change_histories
                    SET event_id                  = NEW.id,
                        history_type              = 2,
                        user_id                   = NEW.user_id,
                        name                      = NEW.name,
                        category_icon             = NEW.category_icon,
                        last_executed_history_id  = NEW.last_executed_history_id,
                        created_at                = NEW.updated_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_events_delete AFTER DELETE ON `events`
            FOR EACH ROW
                BEGIN
                    INSERT INTO event_change_histories
                    SET event_id                  = OLD.id,
                        history_type              = 3,
                        user_id                   = OLD.user_id,
                        name                      = OLD.name,
                        category_icon             = OLD.category_icon,
                        last_executed_history_id  = OLD.last_executed_history_id,
                        created_at                = NOW();
                END
            '
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_events_create');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_events_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_events_delete');
        Schema::dropIfExists('event_change_histories');
    }
};
