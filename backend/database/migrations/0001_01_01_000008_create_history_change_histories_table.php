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
        Schema::create('history_change_histories', function (Blueprint $table) {
            $table->comment('イベント実行履歴の変更履歴');

            $table->id()->comment('ID');
            $table->string('history_id', 50)->index()->comment('履歴ID');
            $table->unsignedTinyInteger('history_type')->comment('タイプ(1:create,2:update,3:delete)');

            $table->string('event_id', 50)->comment('イベントID');
            $table->timestamp('executed_at')->comment('実行日時');
            $table->text('memo')->nullable()->comment('メモ');
            $table->timestamp('created_at')->nullable()->comment('作成日時');
        });

        DB::unprepared(
            '
            CREATE TRIGGER trigger_histories_create AFTER INSERT ON `histories`
            FOR EACH ROW
                BEGIN
                    INSERT INTO history_change_histories
                    SET history_id    = NEW.id,
                        history_type  = 1,
                        event_id      = NEW.event_id,
                        executed_at   = NEW.executed_at,
                        memo          = NEW.memo,
                        created_at    = NEW.created_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_histories_update AFTER UPDATE ON `histories`
            FOR EACH ROW
                BEGIN
                    INSERT INTO history_change_histories
                    SET history_id    = NEW.id,
                        history_type  = 2,
                        event_id      = NEW.event_id,
                        executed_at   = NEW.executed_at,
                        memo          = NEW.memo,
                        created_at    = NEW.updated_at;
                END
            '
        );

        DB::unprepared(
            '
            CREATE TRIGGER trigger_histories_delete AFTER DELETE ON `histories`
            FOR EACH ROW
                BEGIN
                    INSERT INTO history_change_histories
                    SET history_id    = OLD.id,
                        history_type  = 3,
                        event_id      = OLD.event_id,
                        executed_at   = OLD.executed_at,
                        memo          = OLD.memo,
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
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_histories_create');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_histories_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_histories_delete');
        Schema::dropIfExists('history_change_histories');
    }
};
