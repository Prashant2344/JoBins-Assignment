<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiProtection
{
    /**
     * Handle an incoming request with API protection measures.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log all API requests for monitoring
        $this->logApiRequest($request);
        
        // Check for suspicious patterns
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious API request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'headers' => $request->headers->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Request blocked due to security policy',
                'error_code' => 'REQUEST_BLOCKED'
            ], 403);
        }
        
        // Validate request size
        $contentLength = $request->header('content-length') ? (int) $request->header('content-length') : 0;
        if ($contentLength > 10 * 1024 * 1024) { // 10MB limit
            return response()->json([
                'success' => false,
                'message' => 'Request payload too large',
                'error_code' => 'PAYLOAD_TOO_LARGE'
            ], 413);
        }
        
        $response = $next($request);
        
        // Add security headers
        $this->addSecurityHeaders($response);
        
        return $response;
    }
    
    /**
     * Log API request details for monitoring.
     */
    protected function logApiRequest(Request $request): void
    {
        Log::info('API Request', [
            'timestamp' => now()->toISOString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'query_params' => $request->query(),
            'content_length' => $request->header('content-length') ? (int) $request->header('content-length') : 0,
            'referer' => $request->header('referer'),
            'request_id' => uniqid('req_', true)
        ]);
    }
    
    /**
     * Check for suspicious request patterns.
     */
    protected function isSuspiciousRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();
        $path = $request->path();
        
        // Check for common bot patterns
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        // Check for suspicious endpoints
        $suspiciousEndpoints = [
            '/admin',
            '/wp-admin',
            '/phpmyadmin',
            '/.env',
            '/config',
            '/backup'
        ];
        
        foreach ($suspiciousEndpoints as $endpoint) {
            if (str_contains($path, $endpoint)) {
                return true;
            }
        }
        
        // Check for SQL injection patterns
        $sqlPatterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+set/i',
            '/or\s+1\s*=\s*1/i'
        ];
        
        $queryString = $request->getQueryString() ?? '';
        $requestBody = $request->getContent();
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $queryString) || preg_match($pattern, $requestBody)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-API-Version', '1.0');
        $response->headers->set('X-Request-ID', uniqid('req_', true));
    }
}
