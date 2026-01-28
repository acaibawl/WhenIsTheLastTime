<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\History;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Get a specific event by ID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // イベントを取得（認証ユーザーのイベントのみ）
        /** @var Event|null $event */
        $event = $user->events()
            ->with('lastExecutedHistory')
            ->find($id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        return $this->successResponseWithMeta([
            'event' => new EventResource($event),
        ]);
    }

    /**
     * Create a new event with initial history.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::beginTransaction();
        try {
            // イベントを作成
            /** @var Event $event */
            $event = $user->events()->create([
                'name' => $request->input('name'),
                'category_icon' => $request->input('categoryIcon'),
            ]);

            // 初回履歴を作成
            /** @var History $firstHistory */
            $firstHistory = $event->histories()->create([
                'executed_at' => $request->input('executedAt'),
                'memo' => $request->input('memo'),
            ]);

            // イベントの最終実行履歴IDを更新
            $event->update([
                'last_executed_history_id' => $firstHistory->id,
            ]);

            // リレーションをリロード
            $event->load('lastExecutedHistory');

            DB::commit();

            return $this->successResponseWithMeta(
                [
                    'event' => new EventResource($event),
                ],
                code: 201
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('イベントの作成に失敗しました: ' . $e->getMessage(), [
                'userId' => $user->id,
                'requestData' => $request->all(),
            ]);

            return $this->errorResponse(
                'イベントの作成に失敗しました',
                500
            );
        }
    }

    /**
     * Update an existing event.
     */
    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // イベントを取得（認証ユーザーのイベントのみ）
        /** @var Event|null $event */
        $event = $user->events()->find($id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        DB::beginTransaction();
        try {
            // イベントを更新
            $event->update([
                'name' => $request->input('name'),
                'category_icon' => $request->input('categoryIcon'),
            ]);

            // リレーションをリロード
            $event->load('lastExecutedHistory');

            DB::commit();

            return $this->successResponseWithMeta([
                'event' => new EventResource($event),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('イベントの更新に失敗しました: ' . $e->getMessage(), [
                'userId' => $user->id,
                'eventId' => $id,
                'requestData' => $request->all(),
            ]);

            return $this->errorResponse(
                'イベントの更新に失敗しました',
                500
            );
        }
    }

    /**
     * Delete an event and all its histories.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // イベントを取得（認証ユーザーのイベントのみ）
        /** @var Event|null $event */
        $event = $user->events()->find($id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        DB::beginTransaction();
        try {
            // イベントを削除（カスケードで履歴も削除される）
            $event->delete();

            DB::commit();

            return $this->successResponseWithMeta([
                'message' => 'Event and all histories deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('イベントの削除に失敗しました: ' . $e->getMessage(), [
                'userId' => $user->id,
                'eventId' => $id,
            ]);

            return $this->errorResponse(
                'イベントの削除に失敗しました',
                500
            );
        }
    }
}
