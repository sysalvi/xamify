<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckExamBrowser
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = (string) $request->header('User-Agent');
        $extensionHeader = (string) $request->header('X-Xamify-Ext');

        if ($extensionHeader === '1') {
            return $next($request);
        }

        if (! str_contains($userAgent, 'EXAMUQ-BROWSER')) {
            return response()->json([
                'message' => 'Akses ditolak. Gunakan aplikasi ujian resmi.',
            ], 403);
        }

        return $next($request);
    }
}
