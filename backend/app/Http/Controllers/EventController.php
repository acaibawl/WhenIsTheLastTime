<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Get all events for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 認証ユーザーの全イベントを取得
        $events = Event::where('user_id', $user->id)
            ->with('lastExecutedHistory')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponseWithMeta([
            'events' => EventResource::collection($events),
        ]);
    }
}
