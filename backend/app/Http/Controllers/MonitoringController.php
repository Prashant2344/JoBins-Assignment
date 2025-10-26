<?php

namespace App\Http\Controllers;

use App\Services\ApiMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonitoringController extends Controller
{
    protected $monitoringService;
    
    public function __construct(ApiMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }
    
    /**
     * Get API statistics.
     */
    public function getStats(Request $request)
    {
        $period = $request->get('period', '24h');
        
        $stats = $this->monitoringService->getApiStats($period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Get endpoint statistics.
     */
    public function getEndpointStats(Request $request)
    {
        $period = $request->get('period', '24h');
        
        $stats = $this->monitoringService->getEndpointStats($period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Get IP address statistics.
     */
    public function getIpStats(Request $request)
    {
        $period = $request->get('period', '24h');
        
        $stats = $this->monitoringService->getIpStats($period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Get hourly request distribution.
     */
    public function getHourlyStats(Request $request)
    {
        $period = $request->get('period', '24h');
        
        $stats = $this->monitoringService->getHourlyStats($period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Get system health status.
     */
    public function getHealth()
    {
        $health = $this->monitoringService->getSystemHealth();
        
        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        
        return response()->json([
            'success' => $health['status'] === 'healthy',
            'data' => $health
        ], $statusCode);
    }
    
    /**
     * Get comprehensive monitoring dashboard data.
     */
    public function getDashboard(Request $request)
    {
        $period = $request->get('period', '24h');
        
        $data = [
            'overview' => $this->monitoringService->getApiStats($period),
            'endpoints' => $this->monitoringService->getEndpointStats($period),
            'ips' => $this->monitoringService->getIpStats($period),
            'hourly' => $this->monitoringService->getHourlyStats($period),
            'health' => $this->monitoringService->getSystemHealth()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Clean old logs.
     */
    public function cleanLogs(Request $request)
    {
        $daysToKeep = $request->get('days', 30);
        
        $deletedCount = $this->monitoringService->cleanOldLogs($daysToKeep);
        
        return response()->json([
            'success' => true,
            'message' => "Cleaned {$deletedCount} old log entries",
            'data' => [
                'deleted_count' => $deletedCount,
                'days_kept' => $daysToKeep
            ]
        ]);
    }
}
