<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\DataTransferObjects\Settings\ExportSettings;
use App\DataTransferObjects\Settings\MiscSettings;
use App\DataTransferObjects\Settings\NotificationSettings;
use App\DataTransferObjects\Settings\ReminderSettings;
use App\DataTransferObjects\Settings\Settings;
use App\DataTransferObjects\Settings\TimingSettings;

class SettingsHydrator
{
    /**
     * Convert array to Settings object.
     *
     * @param array<string, mixed> $data
     */
    public static function hydrate(array $data): Settings
    {
        return new Settings(
            export: self::hydrateExport($data['export'] ?? []),
            notification: self::hydrateNotification($data['notification'] ?? []),
            misc: self::hydrateMisc($data['misc'] ?? []),
        );
    }

    /**
     * Merge partial array data into existing Settings object.
     *
     * @param Settings $settings Existing settings object
     * @param array<string, mixed> $data Partial data to merge
     */
    public static function merge(Settings $settings, array $data): Settings
    {
        // Export settings
        if (isset($data['export'])) {
            $export = $settings->export;
            if (isset($data['export']['lastExportedAt'])) {
                $export = $export->withLastExportedAt($data['export']['lastExportedAt']);
            }
            $settings = $settings->withExport($export);
        }

        // Notification settings
        if (isset($data['notification']['reminder'])) {
            $reminder = $settings->notification->reminder;

            if (isset($data['notification']['reminder']['enabled'])) {
                $reminder = $reminder->withEnabled($data['notification']['reminder']['enabled']);
            }

            if (isset($data['notification']['reminder']['targetEvents'])) {
                $reminder = $reminder->withTargetEvents($data['notification']['reminder']['targetEvents']);
            }

            // Timing settings
            if (isset($data['notification']['reminder']['timing'])) {
                $timing = $reminder->timing;

                if (isset($data['notification']['reminder']['timing']['type'])) {
                    $timing = $timing->withType($data['notification']['reminder']['timing']['type']);
                }

                if (isset($data['notification']['reminder']['timing']['time'])) {
                    $timing = $timing->withTime($data['notification']['reminder']['timing']['time']);
                }

                if (array_key_exists('dayOfWeek', $data['notification']['reminder']['timing'])) {
                    $timing = $timing->withDayOfWeek($data['notification']['reminder']['timing']['dayOfWeek']);
                }

                if (array_key_exists('dayOfMonth', $data['notification']['reminder']['timing'])) {
                    $timing = $timing->withDayOfMonth($data['notification']['reminder']['timing']['dayOfMonth']);
                }

                $reminder = $reminder->withTiming($timing);
            }

            $notification = $settings->notification->withReminder($reminder);
            $settings = $settings->withNotification($notification);
        }

        // Misc settings
        if (isset($data['misc'])) {
            $misc = $settings->misc;
            if (isset($data['misc']['showTutorial'])) {
                $misc = $misc->withShowTutorial($data['misc']['showTutorial']);
            }
            $settings = $settings->withMisc($misc);
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateExport(array $data): ExportSettings
    {
        return new ExportSettings(
            lastExportedAt: $data['lastExportedAt'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateNotification(array $data): NotificationSettings
    {
        return new NotificationSettings(
            reminder: self::hydrateReminder($data['reminder'] ?? []),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateReminder(array $data): ReminderSettings
    {
        return new ReminderSettings(
            enabled: $data['enabled'] ?? false,
            timing: self::hydrateTiming($data['timing'] ?? []),
            targetEvents: $data['targetEvents'] ?? 'week',
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateTiming(array $data): TimingSettings
    {
        return new TimingSettings(
            type: $data['type'] ?? 'daily',
            time: $data['time'] ?? '09:00',
            dayOfWeek: $data['dayOfWeek'] ?? null,
            dayOfMonth: $data['dayOfMonth'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateMisc(array $data): MiscSettings
    {
        return new MiscSettings(
            showTutorial: $data['showTutorial'] ?? true,
        );
    }
}
