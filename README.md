# Shop Source Backend - Simplified Version

This is the Laravel backend for the simplified shop application.

## Features

### Authentication
- User login with email/password
- User registration
- JWT token-based authentication
- User logout and profile management

### Product Management
- Product listing with filtering and pagination
- Single product details
- Featured products
- Related products
- Product categories

## Database Schema

### Users Table
- `id` - Primary key
- `uuid` - Unique identifier
- `name` - User name
- `email` - Email address (unique)
- `mobile` - Mobile number (unique, nullable)
- `password` - Hashed password (nullable)
- `role` - User role (default: 'user')
- `is_registered` - Registration status
- `is_active` - Account status
- `last_login_at` - Last login timestamp
- `last_login_ip` - Last login IP
- `created_at`, `updated_at` - Timestamps

### Products Table
- `id` - Primary key
- `uuid` - Unique identifier
- `name` - Product name
- `slug` - URL slug (unique)
- `description` - Product description
- `short_description` - Short description (nullable)
- `price` - Product price
- `sale_price` - Sale price (nullable)
- `sku` - Stock keeping unit (unique)
- `stock_quantity` - Stock quantity
- `manage_stock` - Stock management flag
- `in_stock` - Stock availability
- `weight` - Product weight (nullable)
- `dimensions` - Product dimensions (nullable)
- `images` - Product images (JSON)
- `videos` - Product videos (JSON)
- `is_featured` - Featured product flag
- `is_active` - Product status
- `category_id` - Foreign key to categories
- `created_at`, `updated_at` - Timestamps

### Categories Table
- `id` - Primary key
- `uuid` - Unique identifier
- `name` - Category name
- `slug` - URL slug (unique)
- `description` - Category description (nullable)
- `image` - Category image (nullable)
- `is_active` - Category status
- `sort_order` - Display order
- `created_at`, `updated_at` - Timestamps

### User Tokens Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `device_type` - Device type (nullable)
- `device_token` - Device token (nullable)
- `web_access_token` - Web access token (nullable)
- `app_access_token` - App access token (nullable)
- `created_at`, `updated_at` - Timestamps

## API Endpoints

### Public Routes
- `POST /api/login` - User login
- `POST /api/register` - User registration
- `POST /api/products/list` - Get products list
- `POST /api/products/show` - Get single product
- `POST /api/products/featured` - Get featured products
- `POST /api/products/related` - Get related products
- `POST /api/admin-login` - Admin login (requires user_id)

### Protected Routes (require authentication)
- `POST /api/logout` - User logout
- `POST /api/profile` - Get user profile
- `POST /api/admin-logout` - Admin logout
- `POST /api/admin-profile` - Admin profile

## Installation

1. Install dependencies:
   ```bash
   composer install
   ```

2. Set up environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure database in `.env`:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```

4. Run migrations:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. Start server:
   ```bash
   php artisan serve
   ```

## Test Users

After running the seeders, you'll have these test users:

### Admin User
- **User ID:** 1
- **Email:** admin@example.com
- **Password:** password123
- **Role:** Admin

### Regular User
- **User ID:** 2
- **Email:** user@example.com
- **Password:** password123
- **Role:** User

### Admin Login
To login as admin, use the admin login page with User ID: `1`

## Authentication

The API uses JWT tokens for authentication. After successful login, include the token in the Authorization header:

```
Authorization: Bearer {token}
```

## Product Filtering

The products list endpoint supports various filters:
- `search` - Search in name, description, SKU
- `category_id` - Filter by category
- `min_price` - Minimum price
- `max_price` - Maximum price
- `in_stock` - Stock availability
- `featured` - Featured products only
- `sort_by` - Sort field (created_at, price, name)
- `sort_order` - Sort direction (asc, desc)
- `per_page` - Items per page (default: 12)