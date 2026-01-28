<?php

declare(strict_types=1);

namespace App\Models;

use App\DataTransferObjects\Settings\Settings;
use App\Services\Settings\SettingsDehydrator;
use App\Services\Settings\SettingsHydrator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property array<string, mixed> $settings_json
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @method static \Database\Factories\UserSettingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSettingsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUserId($value)
 * @mixin \Eloquent
 */
class UserSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'settings_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    /**
     * Get the user that owns the settings.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings structure.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultSettings(): array
    {
        return [
            'export' => [
                'lastExportedAt' => null,
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => false,
                    'timing' => [
                        'type' => 'daily',
                        'time' => '09:00',
                        'dayOfWeek' => null,
                        'dayOfMonth' => null,
                    ],
                    'targetEvents' => 'week',
                ],
            ],
            'misc' => [
                'showTutorial' => true,
            ],
        ];
    }

    /**
     * Get settings as object.
     */
    public function getSettings(): Settings
    {
        return SettingsHydrator::hydrate($this->settings_json);
    }

    /**
     * Set settings from object.
     */
    public function setSettings(Settings $settings): void
    {
        $this->settings_json = SettingsDehydrator::dehydrate($settings);
    }
}
