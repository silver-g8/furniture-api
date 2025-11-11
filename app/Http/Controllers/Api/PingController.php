<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PingController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/ping",
     *   summary="Ping",
     *   tags={"Health"},
     *
     *   @OA\Response(response=200, description="pong")
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['message' => 'pong']);
    }
}
