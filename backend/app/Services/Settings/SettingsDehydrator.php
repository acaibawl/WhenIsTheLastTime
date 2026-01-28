<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\DataTransferObjects\Settings\ExportSettings;
use App\DataTransferObjects\Settings\MiscSettings;
use App\DataTransferObjects\Settings\NotificationSettings;
use App\DataTransferObjects\Settings\ReminderSettings;
use App\DataTransferObjects\Settings\Settings;
use App\DataTransferObjects\Settings\TimingSettings;

class SettingsDehydrator
{
    /**
     * Convert Settings object to array.
     *
     * @return array<string, mixed>
     */
    public static function dehydrate(Settings $settings): array
    {
        return [
            'export' => self::dehydrateExport($settings->export),
            'notification' => self::dehydrateNotification($settings->notification),
            'misc' => self::dehydrateMisc($settings->misc),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateExport(ExportSettings $export): array
    {
        return [
            'lastExportedAt' => $export->lastExportedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateNotification(NotificationSettings $notification): array
    {
        return [
            'reminder' => self::dehydrateReminder($notification->reminder),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateReminder(ReminderSettings $reminder): array
    {
        return [
            'enabled' => $reminder->enabled,
            'timing' => self::dehydrateTiming($reminder->timing),
            'targetEvents' => $reminder->targetEvents,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateTiming(TimingSettings $timing): array
    {
        return [
            'type' => $timing->type,
            'time' => $timing->time,
            'dayOfWeek' => $timing->dayOfWeek,
            'dayOfMonth' => $timing->dayOfMonth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateMisc(MiscSettings $misc): array
    {
        return [
            'showTutorial' => $misc->showTutorial,
        ];
    }
}
