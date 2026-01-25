<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->string('id', 50)->primary()->comment('履歴ID（hist_プレフィックス）');
            $table->string('event_id', 50)->comment('イベントID');
            $table->timestamp('executed_at')->comment('実行日時');
            $table->text('memo')->nullable()->comment('メモ');
            $table->timestamps();

            $table->index('event_id', 'idx_histories_event_id');
            $table->index('executed_at', 'idx_histories_executed_at');
            $table->index(['event_id', 'executed_at'], 'idx_histories_event_id_executed_at');

            $table->foreign('event_id', 'fk_histories_events')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();
        });

        // Add foreign key to events table for last_executed_history_id
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('last_executed_history_id', 'fk_events_histories')
                ->references('id')
                ->on('histories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign('fk_events_histories');
        });

        Schema::dropIfExists('histories');
    }
};
