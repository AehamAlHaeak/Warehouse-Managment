# Warehouse Management System

A backend system built with Laravel to manage warehouse operations including products, cargos, vehicles, employees, and inventory, with secure authentication, background processing, and real-time notifications.

## Tech Stack
- PHP (Laravel)
- MySQL
- JWT Authentication
- Laravel Queues (Database)
- Laravel Scheduler
- Pusher (Real-time Notifications)
- File Storage (Public Disk)
- Linux Environment

## Features
- JWT-based authentication
- Product & inventory management
- Cargo and vehicle management
- Employee management
- File uploads with structured storage
- Background jobs using queues
- Scheduled tasks
- Real-time notifications

## Requirements
- PHP >= 8.0
- Composer
- MySQL
- Laravel CLI
- Pusher account

## Installation & Setup
```bash
composer install
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
cp .env.example .env
