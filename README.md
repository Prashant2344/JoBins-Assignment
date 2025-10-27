# Client Management System

A fullstack CSV-based client management system with duplicate detection built with Laravel (backend) and React (frontend).

## 🎯 Features

### Backend (Laravel)
- ✅ **CSV Import Functionality**
  - Upload CSV files with client data (company_name, email, phone_number)
  - Data validation before importing
  - Graceful error handling with detailed error messages
  - Batch processing for large CSV files
  - Support for up to 10MB files

- ✅ **Duplicate Detection & Management**
  - Automatic detection of duplicate records during import
  - Duplicates are identified by matching company_name, email, and phone_number
  - Duplicate records are flagged and grouped together
  - View and manage duplicate records through API endpoints
  - Get specific duplicate group with all clients in the group

- ✅ **CSV Export Functionality**
  - Export all client data to CSV format
  - Filter exports (all clients, unique only, duplicates only)
  - Efficient handling of large exports
  - Includes duplicate status and group information

- ✅ **RESTful API Design**
  - Complete CRUD operations for clients
  - Proper HTTP methods and status codes
  - Comprehensive validation and error responses
  - Pagination support
  - Search functionality
  - Batch configuration management

- ✅ **API Monitoring & Security**
  - Real-time API usage statistics and analytics
  - Endpoint performance monitoring
  - IP address tracking and suspicious activity detection
  - Hourly request distribution analysis
  - System health checks with anomaly detection
  - Request/response logging with performance metrics
  - API rate limiting (configurable per endpoint group)
  - Bot detection and SQL injection prevention
  - Security headers injection
  - Comprehensive error tracking and reporting

### Frontend (React)
- ✅ **Modern React Interface**
  - Material-UI components for professional look
  - Responsive design
  - Tabbed interface for different functionalities
  - Real-time statistics dashboard

- ✅ **CSV Import Interface**
  - Drag-and-drop file upload
  - Sample CSV download
  - Import progress indication
  - Detailed import results with statistics

- ✅ **CSV Export Interface**
  - Multiple export options (all, unique, duplicates)
  - One-click download functionality

- ✅ **Duplicate Management**
  - View duplicate groups
  - Manage individual duplicate records
  - Delete functionality for duplicate records

- ✅ **Client Management**
  - View all clients in a data grid
  - Search and filter functionality
  - Pagination support

## 🛠️ Technology Stack

### Backend
- **Laravel 12** - PHP framework
- **PostgreSQL** - Database
- **League CSV** - CSV processing library
- **PHPUnit** - Testing framework

### Frontend
- **React 19** - Frontend framework
- **Material-UI** - UI component library
- **Axios** - HTTP client
- **React Router** - Routing

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 16 or higher
- npm or yarn

## 🚀 Installation & Setup

### Backend Setup

1. **Navigate to backend directory:**
   ```bash
   cd backend
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup:**
   ```bash
   php artisan migrate
   ```

5. **Start the Laravel server:**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000`

### Frontend Setup

1. **Navigate to frontend directory:**
   ```bash
   cd frontend
   ```

2. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

3. **Start the React development server:**
   ```bash
   npm start
   ```
   The frontend will be available at `http://localhost:3000`

## 🧪 Running Tests

## Running together
  ```bash
   npm run dev
  ```

### Backend Tests
```bash
cd backend
php artisan test
```

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Endpoints

#### Client Management
- `GET /clients` - List all clients (with pagination and filtering)
- `GET /clients/{id}` - Get specific client
- `PUT /clients/{id}` - Update client
- `DELETE /clients/{id}` - Delete client

#### CSV Operations
- `POST /clients/import` - Import CSV file
- `GET /clients/export` - Export clients to CSV

#### Duplicate Management
- `GET /clients/duplicates/groups` - Get all duplicate groups
- `GET /clients/duplicates/groups/{groupId}/clients` - Get clients in a specific duplicate group

#### Statistics
- `GET /clients/stats` - Get import statistics
- `GET /clients/batch-config` - Get batch processing configuration

#### API Monitoring
- `GET /monitoring/stats` - Get overall API statistics
- `GET /monitoring/endpoints` - Get endpoint usage statistics
- `GET /monitoring/ips` - Get IP address statistics
- `GET /monitoring/hourly` - Get hourly request distribution
- `GET /monitoring/health` - Get system health status
- `GET /monitoring/dashboard` - Get comprehensive dashboard data
- `POST /monitoring/clean-logs` - Clean old log entries

#### Utility
- `GET /health` - API health check
- `DELETE /clients/delete-all` - Delete all clients (for testing)

## 📁 Project Structure

```
JoBins-Assignment/
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── ClientController.php
│   │   │   │   └── MonitoringController.php
│   │   │   ├── Middleware/
│   │   │   │   ├── ApiRateLimit.php
│   │   │   │   ├── ApiProtection.php
│   │   │   │   └── RequestLogging.php
│   │   │   ├── Requests/
│   │   │   │   └── CsvImportRequest.php
│   │   │   └── Resources/
│   │   │       ├── ClientResource.php
│   │   │       ├── ClientCollection.php
│   │   │       ├── ClientWithDuplicatesResource.php
│   │   │       ├── DuplicateGroupResource.php
│   │   │       ├── DuplicateGroupCollection.php
│   │   │       ├── StatsResource.php
│   │   │       └── BatchConfigResource.php
│   │   ├── Models/
│   │   │   ├── Client.php
│   │   │   └── User.php
│   │   └── Services/
│   │       ├── CsvImportService.php
│   │       └── ApiMonitoringService.php
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 2025_10_20_072524_create_clients_table.php
│   │   │   └── 2025_10_25_073832_create_api_request_logs_table.php
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       └── ClientSeeder.php
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       ├── Unit/
│       │   └── ClientTest.php
│       └── Feature/
│           └── CsvImportTest.php
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   ├── ClientManagement.js
│   │   │   ├── CsvImport.js
│   │   │   ├── CsvExport.js
│   │   │   ├── DuplicateManager.js
│   │   │   └── StatsDashboard.js
│   │   └── services/
│   │       └── clientService.js
│   └── public/
├── sample_clients.csv
├── API_SECURITY_GUIDE.md
├── postman_collection.json
└── setup.sh
```

## 🔧 Configuration

### Environment Variables

#### Backend (.env)
```env
APP_NAME="Client Management System"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=client_management
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

#### Frontend
The frontend automatically connects to `http://localhost:8000/api/v1`. To change this, update the `API_BASE_URL` in `frontend/src/services/clientService.js`.

## 📊 Database Schema

### Clients Table
```sql
CREATE TABLE clients (
    id BIGSERIAL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone_number VARCHAR(255) NOT NULL,
    is_duplicate BOOLEAN DEFAULT FALSE,
    duplicate_group_id VARCHAR(255),
    import_metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### API Request Logs Table
```sql
CREATE TABLE api_request_logs (
    id BIGSERIAL PRIMARY KEY,
    request_id VARCHAR(255) UNIQUE,
    method VARCHAR(10),
    path VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    status_code INTEGER,
    duration_ms INTEGER,
    response_size INTEGER,
    memory_usage BIGINT,
    error_message TEXT,
    status VARCHAR(20) CHECK (status IN ('started', 'completed', 'error')),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Indexes
**Clients Table:**
- `company_name, email, phone_number` (composite index for duplicate detection)
- `duplicate_group_id` (for grouping duplicates)
- `is_duplicate` (for filtering)

**API Request Logs Table:**
- `method, path` (for endpoint analysis)
- `ip_address` (for IP tracking)
- `status_code` (for error analysis)
- `created_at` (for time-based queries)

## 🎨 Architecture Decisions

### Backend Architecture
- **Service Layer Pattern**: 
  - `CsvImportService` handles all CSV processing logic
  - `ApiMonitoringService` provides comprehensive API analytics and health monitoring
- **Middleware Stack**: 
  - `ApiRateLimit` for preventing API abuse
  - `ApiProtection` for security (bot detection, SQL injection prevention)
  - `RequestLogging` for comprehensive request/response tracking
- **Repository Pattern**: Eloquent models with scopes for data access
- **Request Validation**: Dedicated form request classes for validation
- **RESTful Design**: Standard HTTP methods and status codes
- **API Resources**: Dedicated resource classes for consistent API responses

### Frontend Architecture
- **Component-Based**: Modular React components for each feature
- **Service Layer**: Centralized API communication through `clientService`
- **Material-UI**: Consistent design system and responsive layout
- **State Management**: Local component state with React hooks

### Duplicate Detection Strategy
- **Composite Key Matching**: Duplicates identified by company_name + email + phone_number
- **Grouping System**: Duplicate records grouped with UUID-based group IDs
- **Flagging System**: `is_duplicate` boolean flag for easy filtering
- **Metadata Tracking**: Import batch information stored for audit trails

### Security & Monitoring Strategy
- **Rate Limiting**: Configurable per-endpoint-group rate limits (60/min standard, 10/min sensitive, 30/min monitoring)
- **Request Logging**: All API requests logged with performance metrics, errors, and IP tracking
- **Health Monitoring**: Real-time system health checks with anomaly detection
- **Security Headers**: Comprehensive security headers for XSS, clickjacking, and MIME-sniffing protection
- **Bot Protection**: Automatic detection and blocking of suspicious bot traffic

## 🚀 Usage Examples

### Import Sample Data
1. Use the provided `sample_clients.csv` file
2. Navigate to the Import CSV tab in the frontend
3. Upload the file and view the results

### API Usage with cURL
```bash
# Import CSV
curl -X POST http://localhost:8000/api/v1/clients/import \
  -F "csv_file=@sample_clients.csv"

# Get statistics
curl http://localhost:8000/api/v1/clients/stats

# Export duplicates only
curl "http://localhost:8000/api/v1/clients/export?duplicates_only=true" \
  -o duplicates.csv

# Get API monitoring statistics
curl http://localhost:8000/api/v1/monitoring/stats

# Get system health
curl http://localhost:8000/api/v1/monitoring/health

# Get API dashboard
curl http://localhost:8000/api/v1/monitoring/dashboard
```

## 🔍 Testing Coverage

### Backend Tests
- **Unit Tests**: Client model functionality, scopes, and relationships
- **Feature Tests**: API endpoints, CSV import/export, duplicate detection

### Test Categories
- Model validation and relationships
- CSV import with various scenarios (valid data, duplicates, errors)
- CSV export with filtering
- API endpoint functionality
- Duplicate detection and management

## 🛡️ Security Considerations

- **File Upload Validation**: Strict file type and size validation (10MB limit)
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries + pattern detection
- **XSS Protection**: Proper output escaping in responses + security headers
- **CORS Configuration**: Configured for development environment with proper credentials handling
- **Rate Limiting**: Per-IP rate limiting to prevent API abuse and DDoS attacks
- **Bot Detection**: Automatic blocking of common bot user agents
- **Request Size Validation**: 10MB request size limit to prevent resource exhaustion
- **Security Headers**: Comprehensive security headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, etc.)
- **Request Logging**: All requests logged for audit trail and security monitoring
- **IP Tracking**: Suspicious IP address monitoring and tracking
- **Error Handling**: Detailed error tracking without exposing sensitive information

For detailed information about security implementation, see `API_SECURITY_GUIDE.md`

---

## 📦 Database Seeding

To populate the database with sample data:

```bash
# Run all seeders (including the new ClientSeeder)
cd backend
php artisan db:seed

# Run only the ClientSeeder
php artisan db:seed --class=ClientSeeder

# Fresh migration with seeding
php artisan migrate:fresh --seed
```