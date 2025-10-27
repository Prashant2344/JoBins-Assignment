# API Security Implementation Guide

## Overview
This document outlines the comprehensive security measures implemented for the Client Management System API, including rate limiting, API protection, request logging, and monitoring capabilities.

## 🔒 Security Features Implemented

### 1. Rate Limiting (`ApiRateLimit` Middleware)
- **Purpose**: Prevents API abuse and DDoS attacks
- **Implementation**: Custom middleware with configurable limits
- **Features**:
  - Per-IP rate limiting based on IP address, user agent, and endpoint
  - Configurable max attempts and decay time
  - Rate limit headers in responses (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
  - Detailed logging of rate limit violations

**Configuration**:
```php
// Applied to different route groups with different limits
Route::middleware('api.rate_limit:60,1')  // 60 requests per minute
Route::middleware('api.rate_limit:10,1')  // 10 requests per minute (for sensitive operations)
Route::middleware('api.rate_limit:30,1')  // 30 requests per minute (for monitoring)
```

### 2. API Protection (`ApiProtection` Middleware)
- **Purpose**: Blocks suspicious requests and adds security headers
- **Features**:
  - Bot detection (blocks common bot user agents)
  - SQL injection pattern detection
  - Suspicious endpoint detection
  - Request size validation (10MB limit)
  - Security headers injection
  - Comprehensive request logging

**Security Headers Added**:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
X-API-Version: 1.0
X-Request-ID: [unique-request-id]
```

### 3. Request Logging (`RequestLogging` Middleware)
- **Purpose**: Comprehensive API request monitoring and analytics
- **Features**:
  - Request/response timing and performance metrics
  - Memory usage tracking
  - Error logging with stack traces
  - Database storage for analytics
  - Request ID tracking across the entire request lifecycle
  - Sanitized header logging (sensitive data redacted)

**Logged Data**:
- Request details (method, URL, IP, user agent)
- Performance metrics (duration, memory usage, response size)
- Error information (if any)
- Request ID for correlation

### 4. API Monitoring Service
- **Purpose**: Real-time API analytics and health monitoring
- **Features**:
  - Usage statistics (requests, unique IPs, response times)
  - Endpoint analytics (most used endpoints, error rates)
  - IP address monitoring (top requesters, suspicious activity)
  - Hourly request distribution
  - System health checks with anomaly detection
  - Log cleanup functionality

**Available Metrics**:
- Total requests and unique IPs
- Average, min, max response times
- Error rates (4xx, 5xx)
- Success rates per endpoint
- Data transfer volumes
- Memory usage patterns

## CORS Security Configuration

Updated CORS settings for enhanced security:

```php
'allowed_origins' => [
    'http://localhost:3000',    // React development
    'http://127.0.0.1:3000',
    'http://localhost:8080',    // Alternative dev port
    'http://127.0.0.1:8080',
],

'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => [
    'Accept', 'Authorization', 'Content-Type', 
    'X-Requested-With', 'X-API-Key', 'X-Request-ID'
],
'exposed_headers' => [
    'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset',
    'X-Request-ID', 'X-Response-Time', 'X-Memory-Usage'
],
'max_age' => 86400, // 24 hours
'supports_credentials' => false,
```

## 📊 Monitoring Endpoints

### API Statistics
- `GET /api/v1/monitoring/stats` - Overall API statistics
- `GET /api/v1/monitoring/endpoints` - Endpoint usage statistics
- `GET /api/v1/monitoring/ips` - IP address statistics
- `GET /api/v1/monitoring/hourly` - Hourly request distribution
- `GET /api/v1/monitoring/health` - System health status
- `GET /api/v1/monitoring/dashboard` - Comprehensive dashboard data
- `POST /api/v1/monitoring/clean-logs` - Clean old log entries

### Rate Limiting Applied
- **Monitoring endpoints**: 30 requests/minute
- **Standard API**: 60 requests/minute
- **Sensitive operations** (import/export/delete-all): 10 requests/minute

## 🗄️ Database Schema

### `api_request_logs` Table
```sql
CREATE TABLE api_request_logs (
    id BIGINT PRIMARY KEY,
    request_id VARCHAR(255) UNIQUE,
    method VARCHAR(10),
    path VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    status_code INT,
    duration_ms INT,
    response_size INT,
    memory_usage BIGINT,
    error_message TEXT,
    status ENUM('started', 'completed', 'error'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_method_path (method, path),
    INDEX idx_ip_address (ip_address),
    INDEX idx_status_code (status_code),
    INDEX idx_created_at (created_at)
);
```

## 📝 Logging Configuration

### Log Channels
- **API Channel**: `storage/logs/api.log` (30 days retention)
- **Security Channel**: `storage/logs/security.log` (90 days retention)

### Log Levels
- **API Logs**: INFO level and above
- **Security Logs**: WARNING level and above

## 🚀 Usage Examples

### Rate Limit Headers in Response
```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
X-Request-ID: req_63f8a1b2c3d4e5f6
X-Response-Time: 45.67ms
X-Memory-Usage: 2.5 MB
```

### Rate Limit Exceeded Response
```json
{
    "success": false,
    "message": "Too many requests. Please try again later.",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}
```

### Suspicious Request Blocked Response
```json
{
    "success": false,
    "message": "Request blocked due to security policy",
    "error_code": "REQUEST_BLOCKED"
}
```

### Monitoring Dashboard Response
```json
{
    "success": true,
    "data": {
        "overview": {
            "total_requests": 1250,
            "unique_ips": 45,
            "avg_response_time_ms": 125.5,
            "success_rate": 98.4
        },
        "endpoints": [...],
        "ips": [...],
        "hourly": [...],
        "health": {
            "status": "healthy",
            "anomalies": []
        }
    }
}
```

## 🔧 Configuration

### Environment Variables
```env
# Logging
LOG_API_DAYS=30
LOG_SECURITY_DAYS=90
LOG_LEVEL=info

# Rate Limiting (can be configured per route)
API_RATE_LIMIT_DEFAULT=60
API_RATE_LIMIT_SENSITIVE=10
API_RATE_LIMIT_MONITORING=30
```

### Middleware Registration
All middleware is automatically registered in `bootstrap/app.php`:
```php
$middleware->alias([
    'api.rate_limit' => \App\Http\Middleware\ApiRateLimit::class,
    'api.protection' => \App\Http\Middleware\ApiProtection::class,
    'api.logging' => \App\Http\Middleware\RequestLogging::class,
]);
```

## 🛠️ Maintenance

### Log Cleanup
Run periodically to clean old logs:
```bash
curl -X POST http://localhost:8000/api/v1/monitoring/clean-logs?days=30
```

### Health Monitoring
Check system health:
```bash
curl http://localhost:8000/api/v1/monitoring/health
```

## 📈 Benefits

1. **Security**: Protection against common attacks (DDoS, SQL injection, bot scraping)
2. **Performance**: Rate limiting prevents resource exhaustion
3. **Monitoring**: Comprehensive analytics for API usage patterns
4. **Debugging**: Detailed request logging for troubleshooting
5. **Compliance**: Audit trail for security and performance monitoring
6. **Scalability**: Performance metrics help identify bottlenecks

## 🔍 Security Considerations

- **IP-based rate limiting**: May need adjustment for shared IPs (corporate networks)
- **Bot detection**: May need tuning based on legitimate bot traffic
- **Log retention**: Consider data privacy regulations for log storage
- **Monitoring access**: Consider authentication for monitoring endpoints in production
- **CORS origins**: Update allowed origins for production domains

This implementation provides enterprise-grade security and monitoring capabilities for the API while maintaining performance and usability.
