<?php

namespace App\Http\Controllers;

use App\Services\HealthProbe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __invoke(Request $request, HealthProbe $probe): JsonResponse
    {
        $token = config('observability.health_token');
        $given = $request->header('X-Health-Token');

        abort_unless(is_string($token) && $token !== '' && is_string($given) && hash_equals($token, $given), 404);

        $report = $probe->run();

        return response()->json($report, $report['status'] === 'ok' ? 200 : 503);
    }
}
