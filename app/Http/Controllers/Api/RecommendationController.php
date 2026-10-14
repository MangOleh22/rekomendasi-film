<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index(Request $request, RecommendationService $service): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $recommendations = $service->forUser($request->user(), $limit);

        return response()->json([
            'data' => $recommendations,
        ]);
    }
}
