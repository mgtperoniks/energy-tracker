<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Device;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing X-Device-Token header.'
            ], 401);
        }

        $device = Device::where('api_token', $token)
            ->where('status', true)
            ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive device token.'
            ], 401);
        }

        // Attach device to request for downstream use
        $request->merge(['authenticated_device' => $device]);

        return $next($request);
    }
}
