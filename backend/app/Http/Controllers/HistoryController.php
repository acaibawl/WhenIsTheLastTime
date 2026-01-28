<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\HistoryResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * Get all histories for a specific event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // 認証ユーザーのイベントかチェック
        if ($event->user_id !== $user->id) {
            return $this->notFoundResponse('Event not found');
        }

        // 履歴を取得（最新順）
        $histories = $event->histories()
            ->orderBy('executed_at', 'desc')
            ->get();

        return $this->successResponseWithMeta([
            'histories' => HistoryResource::collection($histories),
        ]);
    }
}
