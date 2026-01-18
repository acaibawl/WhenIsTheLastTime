<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        return $this->successResponse(message: 'Application is healthy');
    }
}
