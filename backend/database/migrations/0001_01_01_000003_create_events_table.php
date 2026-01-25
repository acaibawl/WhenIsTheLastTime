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
        Schema::create('events', function (Blueprint $table) {
            $table->string('id', 50)->primary()->comment('イベントID（evt_プレフィックス）');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('ユーザーID');
            $table->string('name', 100)->comment('イベント名');
            $table->string('category_icon', 20)->comment('カテゴリーアイコン');
            $table->string('last_executed_history_id', 50)->nullable()->comment('最終実行履歴ID');
            $table->timestamps();

            $table->index('user_id', 'idx_events_user_id');
            $table->index('category_icon', 'idx_events_category_icon');
            $table->index('created_at', 'idx_events_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
