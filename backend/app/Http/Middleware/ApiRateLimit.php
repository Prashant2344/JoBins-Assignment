<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request with custom rate limiting logic.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Get current attempts
        $attempts = Cache::get($key, 0);
        
        // Check if rate limit exceeded
        if ($attempts >= $maxAttempts) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $decayMinutes * 60
            ], 429)->header('Retry-After', $decayMinutes * 60);
        }
        
        // Increment attempts
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        
        // Add rate limit headers to response
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
        
        return $response;
    }
    
    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address and user agent for basic rate limiting
        // In production, you might want to use API keys or user authentication
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $endpoint = $request->path();
        
        return 'rate_limit:' . md5($ip . '|' . $userAgent . '|' . $endpoint);
    }
}
