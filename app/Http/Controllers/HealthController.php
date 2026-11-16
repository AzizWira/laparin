<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::selectFromWriteConnection('select 1');
        } catch (QueryException) {
            return response()->json([
                'status' => 'unavailable',
                'database' => 'disconnected',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
        ]);
    }
}
