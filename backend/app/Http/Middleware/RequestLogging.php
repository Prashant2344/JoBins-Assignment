<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequestLogging
{
    /**
     * Handle an incoming request with comprehensive logging.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_', true);
        
        $request->attributes->set('request_id', $requestId);
        
        $this->logRequestStart($request, $requestId);
        
        try {
            $response = $next($request);
            $this->logRequestEnd($request, $response, $requestId, $startTime);
            return $response;
        } catch (\Exception $e) {
            $this->logRequestError($request, $e, $requestId, $startTime);
            throw $e;
        }
    }
    
    /**
     * Log request start details.
     */
    protected function logRequestStart(Request $request, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'content_type' => $request->header('content-type'),
            'content_length' => $request->header('content-length') ? (int) $request->header('content-length') : 0,
            'query_params' => $request->query(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'status' => 'started'
        ];
        
        Log::channel('api')->info('API Request Started', $logData);
        
        $this->storeRequestMetrics($logData);
    }
    
    /**
     * Log request completion details.
     */
    protected function logRequestEnd(Request $request, Response $response, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        
        $logData = [
            'request_id' => $requestId,
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
            'memory_usage' => memory_get_peak_usage(true),
            'status' => 'completed'
        ];
        
        Log::channel('api')->info('API Request Completed', $logData);
        
        $this->updateRequestMetrics($requestId, $logData);
        
        // Add performance headers
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', $duration . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes(memory_get_peak_usage(true)));
    }
    
    /**
     * Log request error details.
     */
    protected function logRequestError(Request $request, \Exception $e, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $logData = [
            'request_id' => $requestId,
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'path' => $request->path(),
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString(),
            'duration_ms' => $duration,
            'status' => 'error'
        ];
        
        Log::channel('api')->error('API Request Error', $logData);
        
        $this->updateRequestMetrics($requestId, $logData);
    }
    
    /**
     * Sanitize headers to remove sensitive information.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }
        
        return $headers;
    }
    
    /**
     * Store request metrics in database.
     */
    protected function storeRequestMetrics(array $logData): void
    {
        try {
            DB::table('api_request_logs')->insert([
                'request_id' => $logData['request_id'],
                'method' => $logData['method'],
                'path' => $logData['path'],
                'ip_address' => $logData['ip'],
                'user_agent' => $logData['user_agent'],
                'status' => $logData['status'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store request metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update request metrics with completion data.
     */
    protected function updateRequestMetrics(string $requestId, array $logData): void
    {
        try {
            DB::table('api_request_logs')
                ->where('request_id', $requestId)
                ->update([
                    'status_code' => $logData['status_code'] ?? null,
                    'duration_ms' => $logData['duration_ms'] ?? null,
                    'response_size' => $logData['response_size'] ?? null,
                    'memory_usage' => $logData['memory_usage'] ?? null,
                    'error_message' => $logData['error_message'] ?? null,
                    'status' => $logData['status'],
                    'updated_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update request metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
