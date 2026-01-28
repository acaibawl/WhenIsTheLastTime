<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string $category_icon
 * @property string|null $last_executed_history_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read History|null $lastExecutedHistory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, History> $histories
 * @property-read int|null $histories_count
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCategoryIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLastExecutedHistoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUserId($value)
 * @mixin \Eloquent
 */
class Event extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'category_icon',
        'last_executed_history_id',
    ];

    /**
     * カテゴリーアイコン一覧
     */
    public const CATEGORY_ICONS = [
        'pin',
        'book',
        'folder',
        'star',
        'chart',
        'sun',
        'person',
        'hospital',
        'medical',
        'leaf',
        'search',
        'people',
        'snowflake',
        'fire',
        'lightning',
    ];

    /**
     * Get the user that owns the event.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the last executed history for the event.
     *
     * @return BelongsTo<History, $this>
     */
    public function lastExecutedHistory(): BelongsTo
    {
        return $this->belongsTo(History::class, 'last_executed_history_id');
    }

    /**
     * Get all histories for the event.
     *
     * @return HasMany<History, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(History::class);
    }

    /**
     * Generate a new unique ID with prefix for the model.
     */
    public function newUniqueId(): string
    {
        return 'evt_' . (string) Str::ulid();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
