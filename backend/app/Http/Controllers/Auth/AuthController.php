<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPost;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function login(LoginPost $request): JsonResponse
    {
        $credentials = $request->validated();
        // email・password（自動でハッシュする）で検索をかけて、一致するuserがいればtokenを設定。なければfalseが入る
        /** @var mixed $token */
        $token = auth()->guard('api')->attempt($credentials);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        $user = auth()->guard('api')->user();

        return response()->json($user);
    }

    public function logout(): JsonResponse
    {
        auth()->guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        return $this->respondWithToken($guard->refresh());
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl'),
        ]);
    }
}
