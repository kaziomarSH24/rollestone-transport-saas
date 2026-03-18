# 🚍 Bus Ticket Management System
> 📚 **Complete System Architecture & Technical Documentation:** > [👉 View / Download Rollestone 50-Page PDF Documentation](./Rollestone_Documentation.pdf)
>
A comprehensive multi-tenant bus ticket management system built with Laravel and Docker. This system provides complete functionality for bus companies to manage routes, trips, drivers, passengers, and real-time operations.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation & Setup](#installation--setup)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [API Documentation](#api-documentation)
- [System Architecture](#system-architecture)
- [User Roles & Permissions](#user-roles--permissions)
- [Key Components](#key-components)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

## 🎯 Overview

The Bus Ticket Management System is a modern, scalable solution for bus transportation companies. It features a multi-tenant architecture where each bus company operates independently with their own data and settings.

### Key Technologies

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Real-time**: Laravel Reverb (WebSockets)
- **Payment**: Stripe Integration
- **Authentication**: Laravel Sanctum
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx

## ✨ Features

### 🏢 Multi-Tenant Architecture

- Each bus company has independent data and settings
- Subdomain-based or company identification
- Isolated payment configurations per company

### 👥 User Management

- **Admin**: Complete system control
- **Drivers**: Route management and passenger processing
- **Passengers**: Mobile app functionality

### 🚌 Operations Management

- **Route Management**: Create and manage bus routes with stops
- **Trip Scheduling**: Schedule trips with departure times
- **Real-time Tracking**: Live journey tracking and updates
- **Driver Dashboard**: Start/end journeys, process payments

### 💳 Payment System

- **Stripe Integration**: Secure payment processing
- **Digital Wallet**: Passenger wallet system with auto top-up
- **Multiple Payment Methods**: Card payments and cash
- **Refund Management**: Automated refund processing

### 📱 Mobile Features

- **Route Information**: View available routes and schedules
- **Trip Alerts**: Get notifications for trip updates
- **Payment History**: Transaction tracking
- **Contact Support**: Built-in support system

### 📊 Analytics & Reporting

- **Live Dashboard**: Real-time journey monitoring
- **Revenue Reports**: Route-wise revenue analysis
- **Cash Reconciliation**: Driver cash management
- **Passenger Analytics**: Usage patterns and trends

### 🔔 Notifications

- **Real-time Alerts**: Firebase push notifications
- **Email Notifications**: Automated email system
- **In-app Notifications**: System notifications

## 🖥️ System Requirements

### Production Environment

- **OS**: Linux (Ubuntu 20.04+ recommended)
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 20GB minimum
- **Docker**: 20.10+
- **Docker Compose**: 2.0+

### Development Environment

- **OS**: Windows/macOS/Linux
- **RAM**: 8GB minimum
- **Docker Desktop**: Latest version
- **Git**: Latest version

## 🚀 Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-repo/bus-ticket.git
cd bus-ticket
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit the environment variables
nano .env
```

### 3. Configure Environment Variables

Update the `.env` file with your settings:

```env
# Database Configuration
DB_DATABASE=bus_ticket_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
MYSQL_ROOT_PASSWORD=your_root_password

# Firebase Configuration
FIREBASE_CREDENTIALS=path/to/firebase-credentials.json

# Reverb (WebSocket) Configuration
REVERB_APP_ID=your_reverb_app_id
REVERB_APP_KEY=your_reverb_key
REVERB_APP_SECRET=your_reverb_secret

# Redis Configuration
REDIS_HOST=bus-ticket-redis
REDIS_PORT=6379
```

### 4. Build and Start Services

#### For Development (with local builds):

```bash
# Build and start all services
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build

# Or simply
docker-compose up -d --build
```

#### For Production (using pre-built images):

```bash
# Start services with production images
docker-compose -f docker-compose.yml up -d
```

### 5. Initialize the Application

```bash
# Enter the application container
docker exec -it bus-ticket-app bash

# Install dependencies (if not using pre-built image)
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Generate Reverb app credentials
php artisan reverb:install

# Run database migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed

# Create storage link
php artisan storage:link

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## ⚙️ Configuration

### Company Setup

1. **Create a Company**:

   ```bash
   php artisan tinker

   Company::create([
       'company_name' => 'Your Bus Company',
       'contact_email' => 'admin@yourcompany.com',
       'subdomain' => 'yourcompany',
       'status' => 'active'
   ]);
   ```

2. **Create Admin User**:
   ```bash
   User::create([
       'name' => 'Admin User',
       'email' => 'admin@yourcompany.com',
       'password' => Hash::make('your_password'),
       'company_id' => 1,
       'user_type' => 'admin'
   ]);
   ```

### Stripe Configuration

Configure Stripe keys in the admin panel or directly in company settings:

- **Public Key**: For frontend payment processing
- **Secret Key**: For backend payment processing
- **Webhook Endpoint**: `/api/v1/stripe/webhook`

### Firebase Setup

1. Download Firebase service account credentials
2. Place the JSON file in `backend/firebase-credentials.json`
3. Configure Firebase in the admin settings

## 🏃 Running the Application

### Service URLs

After successful setup, access these services:

- **Main Application**: http://localhost:82
- **API Base URL**: http://localhost:82/api/v1
- **Admin Panel**: http://localhost:82/admin
- **PHPMyAdmin**: http://localhost:8082
- **WebSocket (Reverb)**: ws://localhost:8989

### Service Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart bus-ticket-app

# View service logs
docker-compose logs -f bus-ticket-app

# Check service status
docker-compose ps
```

## 📡 API Documentation

### Authentication Endpoints

```
POST /api/v1/auth/register          # User registration
POST /api/v1/auth/login            # User login
POST /api/v1/auth/logout           # User logout
POST /api/v1/auth/forgot-password  # Password reset
```

### Admin Endpoints

```
GET  /api/v1/admin/dashboard/stats     # Dashboard statistics
GET  /api/v1/admin/dashboard/live-data # Live journey data
CRUD /api/v1/admin/drivers             # Driver management
CRUD /api/v1/admin/passengers          # Passenger management
CRUD /api/v1/admin/routes              # Route management
CRUD /api/v1/admin/trips               # Trip management
```

### Driver Endpoints

```
GET  /api/v1/driver/journeys/driver-schedule    # Driver schedule
POST /api/v1/driver/journeys/{journey}/start    # Start journey
POST /api/v1/driver/journeys/{journey}/end      # End journey
POST /api/v1/driver/journeys/process-payment    # Process payment
```

### Passenger Endpoints

```
GET  /api/v1/passenger/routes                    # Available routes
POST /api/v1/passenger/payment/top-up           # Wallet top-up
GET  /api/v1/passenger/payment/transactions     # Transaction history
GET  /api/v1/passenger/alerts                   # Trip alerts
```

### API Authentication

All protected routes require Bearer token authentication:

```bash
curl -H "Authorization: Bearer your_token_here" \
     -H "Content-Type: application/json" \
     http://localhost:82/api/v1/protected-endpoint
```

## 🏗️ System Architecture

*For an in-depth understanding of the Data Flow Diagrams (DFD), Database Schema, and DevOps Configuration, please refer to the **[Technical Documentation PDF](./Rollestone_Documentation.pdf)**.*

### Container Services

1. **bus-ticket-app**: Laravel application (PHP-FPM)
2. **backend-webserver**: Nginx web server
3. **bus-ticket-db**: MySQL database
4. **nginx-proxy**: Main reverse proxy
5. **queue-worker**: Laravel queue processing
6. **scheduler**: Laravel task scheduler
7. **bus-ticket-redis**: Redis cache/sessions
8. **reverb**: WebSocket server
9. **phpmyadmin**: Database administration

### Data Flow

1. **Client Request** → Nginx Proxy → Backend Webserver → Laravel App
2. **Real-time Updates** → Reverb WebSocket Server → Client
3. **Background Jobs** → Queue Worker → Redis/Database
4. **Payments** → Stripe API → Webhook → Laravel

## 👤 User Roles & Permissions

### Admin

- Complete system access
- Company management
- User management
- Route and trip configuration
- Payment settings
- Analytics and reporting

### Driver

- View assigned routes
- Start/end journeys
- Process passenger payments
- Update journey status
- Cash reconciliation

### Passenger

- View routes and schedules
- Top-up wallet
- View transaction history
- Set trip alerts
- Contact support

## 🔧 Key Components

### Models

- **Company**: Multi-tenant company management
- **User**: System users (Admin, Driver, Passenger)
- **Route**: Bus routes with stops
- **Trip**: Scheduled trips
- **Journey**: Active trip instances
- **Transaction**: Payment transactions
- **PassengerWallet**: Digital wallet system

### Services

- **DashboardService**: Dashboard analytics
- **PaymentService**: Stripe payment processing
- **NotificationService**: Push notifications
- **JourneyService**: Trip management

### Jobs & Queues

- Payment processing
- Notification sending
- Data synchronization
- Report generation

## 🔧 Development

### Local Development Setup

```bash
# Clone and enter directory
git clone https://github.com/your-repo/bus-ticket.git
cd bus-ticket

# Start development environment
docker-compose up -d

# Install dependencies
docker exec -it bus-ticket-app composer install

# Run migrations with sample data
docker exec -it bus-ticket-app php artisan migrate --seed

# Generate IDE helper (optional)
docker exec -it bus-ticket-app php artisan ide-helper:generate
```

### Database Management

```bash
# Fresh migration (⚠️ destroys data)
docker exec -it bus-ticket-app php artisan migrate:fresh --seed

# Rollback migration
docker exec -it bus-ticket-app php artisan migrate:rollback

# Create new migration
docker exec -it bus-ticket-app php artisan make:migration create_your_table
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Container Won't Start

```bash
# Check container logs
docker-compose logs -f bus-ticket-app

# Rebuild containers
docker-compose down
docker-compose up -d --build
```

#### 2. Database Connection Issues

```bash
# Verify database container is running
docker-compose ps

# Check database logs
docker-compose logs -f bus-ticket-db

# Test database connection
docker exec -it bus-ticket-app php artisan tinker
DB::connection()->getPdo();
```

#### 3. Permission Issues

```bash
# Fix Laravel permissions
docker exec -it bus-ticket-app chmod -R 775 storage bootstrap/cache
docker exec -it bus-ticket-app chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Queue Jobs Not Processing

```bash
# Restart queue worker
docker-compose restart queue-worker

# Check queue status
docker exec -it bus-ticket-app php artisan queue:work --once
```

#### 5. WebSocket Connection Issues

```bash
# Check Reverb service
docker-compose logs -f reverb

# Test WebSocket connection
# Visit: http://localhost:82/reverb-test.html
```

### Performance Optimization

#### Production Optimizations

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable OPcache (in custom.ini)
opcache.enable=1
opcache.memory_consumption=256
```

### Monitoring & Logs

#### Application Logs

```bash
# View Laravel logs
docker exec -it bus-ticket-app tail -f storage/logs/laravel.log

# View Nginx access logs
docker exec -it bus-ticket-nginx-server tail -f /var/log/nginx/access.log

# View queue worker logs
docker-compose logs -f queue-worker
```

#### System Monitoring

```bash
# Check container resource usage
docker stats

# Monitor disk usage
docker system df

# Clean up unused resources
docker system prune
```

## 🆘 Support

### Getting Help

1. **Documentation**: Check this README and inline code comments
2. **Logs**: Always check application and container logs first
3. **Issues**: Create detailed GitHub issues with:
   - Error messages
   - Steps to reproduce
   - Environment details
   - Container logs

### Maintenance

- **Backups**: Regular database and file backups
- **Updates**: Keep Docker images and dependencies updated
- **Security**: Regular security updates and monitoring
- **Monitoring**: Set up application and infrastructure monitoring

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Note**: This documentation covers the core functionality and setup. For specific customizations or advanced configurations, please refer to the Laravel documentation and individual service documentation.
