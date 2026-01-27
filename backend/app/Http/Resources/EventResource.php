<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @var Event
     */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastHistory = $this->lastExecutedHistory;

        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'name' => $this->name,
            'categoryIcon' => $this->category_icon,
            'lastExecutedHistoryId' => $this->last_executed_history_id,
            'lastExecutedAt' => $lastHistory?->executed_at?->toIso8601String(),
            'lastExecutedMemo' => $lastHistory?->memo,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
