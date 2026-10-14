<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_movies' => Movie::count(),
                'total_genres' => Genre::count(),
                'total_users' => User::count(),
                'total_watched' => WatchHistory::count(),
            ],
        ]);
    }
}
