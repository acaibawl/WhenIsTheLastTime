<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreHistoryRequest;
use App\Http\Requests\UpdateHistoryRequest;
use App\Http\Resources\HistoryResource;
use App\Models\Event;
use App\Models\History;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    /**
     * Get all histories for a specific event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        // 履歴を取得（最新順）
        $histories = $event->histories()
            ->orderBy('executed_at', 'desc')
            ->get();

        return $this->successResponseWithMeta([
            'histories' => HistoryResource::collection($histories),
        ]);
    }

    /**
     * Add a new history entry for a specific event.
     */
    public function store(StoreHistoryRequest $request, Event $event): JsonResponse
    {
        DB::beginTransaction();
        try {
            // 履歴を作成
            $history = History::create([
                'event_id' => $event->id,
                'executed_at' => $request->input('executedAt'),
                'memo' => $request->input('memo'),
            ]);

            // 最新の履歴を取得してイベントの最終実行履歴を更新
            $latestHistory = $event->histories()
                ->orderBy('executed_at', 'desc')
                ->first();

            $event->update([
                'last_executed_history_id' => $latestHistory->id,
            ]);

            DB::commit();

            return $this->successResponseWithMeta(
                [
                    'history' => new HistoryResource($history),
                ],
                JsonResponse::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a specific history entry.
     */
    public function update(UpdateHistoryRequest $request, Event $event, History $history): JsonResponse
    {
        // 履歴がイベントに属しているかチェック
        if ($history->event_id !== $event->id) {
            return $this->notFoundResponse('History not found');
        }

        DB::beginTransaction();
        try {
            // 履歴を更新
            $history->update([
                'executed_at' => $request->input('executedAt'),
                'memo' => $request->input('memo'),
            ]);

            // 最新の履歴を取得してイベントの最終実行履歴を更新
            $latestHistory = $event->histories()
                ->orderBy('executed_at', 'desc')
                ->first();

            $event->update([
                'last_executed_history_id' => $latestHistory->id,
            ]);

            DB::commit();

            return $this->successResponseWithMeta([
                'history' => new HistoryResource($history->fresh()),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
