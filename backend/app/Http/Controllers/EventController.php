<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\History;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Get all events for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 認証ユーザーの全イベントを取得
        $events = $user->events()
            ->with('lastExecutedHistory')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponseWithMeta([
            'events' => EventResource::collection($events),
        ]);
    }

    /**
     * Create a new event with initial history.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            // トランザクション内でイベントと履歴を作成
            $event = DB::transaction(function () use ($request, $user) {
                // イベントを作成
                /** @var Event $event */
                $event = $user->events()->create([
                    'name' => $request->input('name'),
                    'category_icon' => $request->input('categoryIcon'),
                ]);

                // 初回履歴を作成
                /** @var History $history */
                $history = $event->histories()->create([
                    'executed_at' => $request->input('executedAt'),
                    'memo' => $request->input('memo'),
                ]);

                // イベントの最終実行履歴IDを更新
                $event->update([
                    'last_executed_history_id' => $history->id,
                ]);

                // リレーションをリロード
                $event->load('lastExecutedHistory');

                return $event;
            });

            return $this->successResponseWithMeta(
                [
                    'event' => new EventResource($event),
                ],
                code: 201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'イベントの作成に失敗しました',
                code: 500
            );
        }
    }
}
