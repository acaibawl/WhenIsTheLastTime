<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\UserSetting;
use App\Services\Settings\SettingsDehydrator;
use App\Services\Settings\SettingsHydrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * Get user settings.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // ユーザー設定を取得（存在しない場合はデフォルト値で作成）
        $userSetting = $user->setting;

        if (! $userSetting) {
            $userSetting = UserSetting::create([
                'user_id' => $user->id,
                'settings_json' => UserSetting::getDefaultSettings(),
            ]);

            Log::info('User settings created with default values', [
                'user_id' => $user->id,
            ]);
        }

        return $this->successResponseWithMeta([
            'settings' => new SettingResource($userSetting),
        ]);
    }

    /**
     * Update user settings.
     */
    public function update(UpdateSettingRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        DB::beginTransaction();
        try {
            // ユーザー設定を取得（存在しない場合はデフォルト値で作成）
            $userSetting = $user->setting;

            if (! $userSetting) {
                /** @var UserSetting $userSetting */
                $userSetting = UserSetting::create([
                    'user_id' => $user->id,
                    'settings_json' => UserSetting::getDefaultSettings(),
                ]);

                Log::info('User settings created with default values on update', [
                    'user_id' => $user->id,
                ]);
            }

            // 既存の設定をオブジェクトに変換
            $settings = SettingsHydrator::hydrate($userSetting->settings_json);

            // リクエストデータを取得
            $validated = $request->validated();

            // Settings オブジェクトを更新（マージ処理）
            $settings = SettingsHydrator::merge($settings, $validated);

            // Settings オブジェクトを配列に変換してデータベースに保存
            $userSetting->settings_json = SettingsDehydrator::dehydrate($settings);
            $userSetting->save();

            DB::commit();

            Log::info('User settings updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
            ]);

            return $this->successResponseWithMeta([
                'settings' => new SettingResource($userSetting),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update user settings', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                '設定の更新に失敗しました',
                500
            );
        }
    }
}
