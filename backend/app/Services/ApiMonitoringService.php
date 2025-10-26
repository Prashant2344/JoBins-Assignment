<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiMonitoringService
{
    /**
     * Get API usage statistics.
     */
    public function getApiStats(string $period = '24h'): array
    {
        $startTime = $this->getStartTime($period);
        
        $stats = DB::table('api_request_logs')
            ->where('created_at', '>=', $startTime)
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(DISTINCT ip_address) as unique_ips,
                AVG(duration_ms) as avg_response_time,
                MAX(duration_ms) as max_response_time,
                MIN(duration_ms) as min_response_time,
                SUM(response_size) as total_data_transferred,
                COUNT(CASE WHEN status = ? THEN 1 END) as error_count,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_4xx_count,
                COUNT(CASE WHEN status_code >= 500 THEN 1 END) as error_5xx_count
            ', ['error'])
            ->first();
        
        return [
            'period' => $period,
            'total_requests' => (int) $stats->total_requests,
            'unique_ips' => (int) $stats->unique_ips,
            'avg_response_time_ms' => round($stats->avg_response_time ?? 0, 2),
            'max_response_time_ms' => (int) $stats->max_response_time,
            'min_response_time_ms' => (int) $stats->min_response_time,
            'total_data_transferred_bytes' => (int) $stats->total_data_transferred,
            'error_count' => (int) $stats->error_count,
            'error_4xx_count' => (int) $stats->error_4xx_count,
            'error_5xx_count' => (int) $stats->error_5xx_count,
            'success_rate' => $stats->total_requests > 0 
                ? round((($stats->total_requests - $stats->error_count) / $stats->total_requests) * 100, 2)
                : 0
        ];
    }
    
    /**
     * Get endpoint usage statistics.
     */
    public function getEndpointStats(string $period = '24h'): array
    {
        $startTime = $this->getStartTime($period);
        
        return DB::table('api_request_logs')
            ->where('created_at', '>=', $startTime)
            ->selectRaw('
                method,
                path,
                COUNT(*) as request_count,
                AVG(duration_ms) as avg_response_time,
                COUNT(CASE WHEN status = ? THEN 1 END) as error_count,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_4xx_count
            ', ['error'])
            ->groupBy('method', 'path')
            ->orderBy('request_count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'endpoint' => $item->method . ' ' . $item->path,
                    'request_count' => (int) $item->request_count,
                    'avg_response_time_ms' => round($item->avg_response_time ?? 0, 2),
                    'error_count' => (int) $item->error_count,
                    'error_4xx_count' => (int) $item->error_4xx_count,
                    'success_rate' => $item->request_count > 0 
                        ? round((($item->request_count - $item->error_count) / $item->request_count) * 100, 2)
                        : 0
                ];
            })
            ->toArray();
    }
    
    /**
     * Get IP address statistics.
     */
    public function getIpStats(string $period = '24h'): array
    {
        $startTime = $this->getStartTime($period);
        
        return DB::table('api_request_logs')
            ->where('created_at', '>=', $startTime)
            ->selectRaw('
                ip_address,
                COUNT(*) as request_count,
                COUNT(DISTINCT path) as unique_endpoints,
                AVG(duration_ms) as avg_response_time,
                COUNT(CASE WHEN status = ? THEN 1 END) as error_count,
                MAX(created_at) as last_request
            ', ['error'])
            ->groupBy('ip_address')
            ->orderBy('request_count', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'ip_address' => $item->ip_address,
                    'request_count' => (int) $item->request_count,
                    'unique_endpoints' => (int) $item->unique_endpoints,
                    'avg_response_time_ms' => round($item->avg_response_time ?? 0, 2),
                    'error_count' => (int) $item->error_count,
                    'last_request' => $item->last_request,
                    'success_rate' => $item->request_count > 0 
                        ? round((($item->request_count - $item->error_count) / $item->request_count) * 100, 2)
                        : 0
                ];
            })
            ->toArray();
    }
    
    /**
     * Get hourly request distribution.
     */
    public function getHourlyStats(string $period = '24h'): array
    {
        $startTime = $this->getStartTime($period);
        
        return DB::table('api_request_logs')
            ->where('created_at', '>=', $startTime)
            ->selectRaw('
                DATE_TRUNC(\'hour\', created_at) as hour,
                COUNT(*) as request_count,
                AVG(duration_ms) as avg_response_time,
                COUNT(CASE WHEN status = ? THEN 1 END) as error_count
            ', ['error'])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour,
                    'request_count' => (int) $item->request_count,
                    'avg_response_time_ms' => round($item->avg_response_time ?? 0, 2),
                    'error_count' => (int) $item->error_count
                ];
            })
            ->toArray();
    }
    
    /**
     * Get system health metrics.
     */
    public function getSystemHealth(): array
    {
        $last24h = $this->getApiStats('24h');
        $last1h = $this->getApiStats('1h');
        
        // Check for anomalies
        $anomalies = [];
        
        if ($last1h['error_count'] > 10) {
            $anomalies[] = 'High error rate in last hour';
        }
        
        if ($last1h['avg_response_time_ms'] > 1000) {
            $anomalies[] = 'High response time in last hour';
        }
        
        if ($last1h['total_requests'] > 1000) {
            $anomalies[] = 'High request volume in last hour';
        }
        
        return [
            'status' => empty($anomalies) ? 'healthy' : 'warning',
            'anomalies' => $anomalies,
            'last_24h' => $last24h,
            'last_1h' => $last1h,
            'timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Clean old logs.
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return DB::table('api_request_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Get start time based on period.
     */
    protected function getStartTime(string $period): Carbon
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay()
        };
    }
}
