# Laravel + React Full-Stack Project

This is a full-stack web application built with Laravel (PHP) as the backend API and React as the frontend. The project is structured with separate directories for backend and frontend applications.

## Project Structure

```
JoBins-Assignment/
├── backend/                 # Laravel API Backend
│   ├── app/                 # Application logic
│   ├── config/              # Configuration files
│   ├── database/            # Database migrations and seeders
│   ├── routes/              # API routes
│   ├── storage/             # File storage
│   ├── tests/               # Backend tests
│   ├── vendor/              # Composer dependencies
│   ├── .env                 # Environment variables
│   ├── artisan              # Laravel command-line tool
│   └── composer.json        # PHP dependencies
├── frontend/                # React Frontend
│   ├── public/              # Static assets
│   ├── src/                 # React source code
│   ├── package.json         # Node.js dependencies
│   └── README.md            # React app documentation
└── README.md                # This file
```

## Prerequisites

Before you begin, ensure you have the following installed on your system:

### Backend Requirements
- **PHP 8.1 or higher**
- **Composer** (PHP dependency manager)
- **SQLite** (or MySQL/PostgreSQL if you prefer)

### Frontend Requirements
- **Node.js 14.0 or higher**
- **npm** (comes with Node.js)

### Optional but Recommended
- **Git** for version control
- **VS Code** or any code editor
- **Postman** or similar API testing tool

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd JoBins-Assignment
```

### 2. Backend Setup (Laravel)

Navigate to the backend directory and install dependencies:

```bash
cd backend
composer install
```

#### Environment Configuration

The `.env` file is already configured with default settings. Key configurations:

- **Database**: SQLite (database/database.sqlite)
- **App URL**: http://localhost:8000
- **Debug Mode**: Enabled for development

If you want to use MySQL or PostgreSQL instead of SQLite, update the `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Database Setup

The SQLite database is already created and migrations are run. If you need to reset:

```bash
php artisan migrate:fresh
```

#### Generate Application Key (if needed)

```bash
php artisan key:generate
```

### 3. Frontend Setup (React)

Navigate to the frontend directory and install dependencies:

```bash
cd ../frontend
npm install
```

## Running the Application

### Development Mode

You'll need to run both the backend and frontend servers simultaneously.

#### Terminal 1 - Backend Server

```bash
cd backend
php artisan serve
```

The Laravel API will be available at: `http://localhost:8000`

#### Terminal 2 - Frontend Server

```bash
cd frontend
npm start
```

The React app will be available at: `http://localhost:3000`

### Production Mode

#### Backend

```bash
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Frontend

```bash
cd frontend
npm run build
```

## API Endpoints

The Laravel backend provides the following API endpoints:

### Base URL
```
http://localhost:8000/api
```

### Available Endpoints

- **GET** `/api/health` - Health check endpoint
- **GET** `/api/v1/test` - Test endpoint
- **GET** `/api/user` - Get authenticated user (requires authentication)

### Example API Calls

```bash
# Health check
curl http://localhost:8000/api/health

# Test endpoint
curl http://localhost:8000/api/v1/test
```

## Development Scripts

### Backend Scripts

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Run tests
php artisan test

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate new migration
php artisan make:migration create_table_name

# Generate new controller
php artisan make:controller Api/ControllerName
```

### Frontend Scripts

```bash
# Start development server
npm start

# Build for production
npm run build

# Run tests
npm test

# Install new package
npm install package-name

# Install development dependency
npm install --save-dev package-name
```

## Configuration

### CORS Configuration

The Laravel backend is configured to allow CORS requests from the React frontend. Configuration is in `backend/config/cors.php`.

### Database Configuration

The project uses SQLite by default for easy setup. Database configuration is in `backend/.env`:

```env
DB_CONNECTION=sqlite
# Database file: database/database.sqlite
```

## Troubleshooting

### Common Issues

1. **Port already in use**
   - Backend: Change port with `php artisan serve --port=8001`
   - Frontend: React will prompt to use a different port

2. **Permission issues**
   - Make sure storage and bootstrap/cache directories are writable
   - Run: `chmod -R 775 storage bootstrap/cache`

3. **Composer issues**
   - Clear composer cache: `composer clear-cache`
   - Reinstall: `rm -rf vendor && composer install`

4. **Node modules issues**
   - Clear npm cache: `npm cache clean --force`
   - Delete node_modules: `rm -rf node_modules && npm install`

### Database Issues

If you encounter database issues:

```bash
# Reset database
cd backend
php artisan migrate:fresh

# Or create new database
touch database/database.sqlite
php artisan migrate
```

## Project Features

### Backend (Laravel)
- ✅ RESTful API structure
- ✅ CORS configuration for frontend communication
- ✅ SQLite database setup
- ✅ API routes configured
- ✅ Middleware setup
- ✅ Basic authentication structure (ready for Sanctum if needed)

### Frontend (React)
- ✅ Modern React setup with Create React App
- ✅ Development server with hot reload
- ✅ Production build configuration
- ✅ Testing framework ready

## Next Steps

1. **Add Authentication**: Implement user registration and login
2. **Create Models**: Add your business logic models
3. **API Development**: Build your specific API endpoints
4. **Frontend Components**: Create React components for your UI
5. **State Management**: Add Redux or Context API if needed
6. **Styling**: Add CSS framework or styled-components
7. **Testing**: Write unit and integration tests
8. **Deployment**: Deploy to your preferred hosting platform

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

If you encounter any issues or have questions, please:

1. Check the troubleshooting section above
2. Search existing issues
3. Create a new issue with detailed information

---

**Happy Coding! 🚀**
