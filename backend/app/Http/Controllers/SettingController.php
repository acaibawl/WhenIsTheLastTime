<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SettingResource;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
