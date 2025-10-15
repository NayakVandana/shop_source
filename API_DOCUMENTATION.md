# E-Commerce API Documentation

## Overview
This is a comprehensive e-commerce API built with Laravel, featuring separate admin and user APIs for managing products, orders, users, categories, and discounts.

## Base URLs
- **Main API**: `/api`
- **Admin API**: `/api/admin`
- **User API**: `/api/user`

## Authentication
- Uses Laravel Sanctum for API token authentication
- Admin routes require admin verification
- User routes require user verification

## API Endpoints

### Health Check
- **GET** `/api/health` - Check API status

### Admin API Endpoints

#### Authentication
- **POST** `/api/admin/login` - Admin login

#### Dashboard
- **GET** `/api/admin/dashboard/stats` - Get dashboard statistics

#### Products Management
- **GET** `/api/admin/products` - List all products (with filters)
- **POST** `/api/admin/products` - Create new product
- **GET** `/api/admin/products/{id}` - Get product details
- **PUT** `/api/admin/products/{id}` - Update product
- **DELETE** `/api/admin/products/{id}` - Delete product
- **PATCH** `/api/admin/products/{id}/toggle-status` - Toggle product status
- **PATCH** `/api/admin/products/{id}/stock` - Update product stock

#### Categories Management
- **GET** `/api/admin/categories` - List all categories
- **POST** `/api/admin/categories` - Create new category
- **GET** `/api/admin/categories/{id}` - Get category details
- **PUT** `/api/admin/categories/{id}` - Update category
- **DELETE** `/api/admin/categories/{id}` - Delete category
- **PATCH** `/api/admin/categories/{id}/toggle-status` - Toggle category status

#### Orders Management
- **GET** `/api/admin/orders` - List all orders (with filters)
- **GET** `/api/admin/orders/{id}` - Get order details
- **DELETE** `/api/admin/orders/{id}` - Delete order (pending only)
- **PATCH** `/api/admin/orders/{id}/status` - Update order status
- **PATCH** `/api/admin/orders/{id}/payment-status` - Update payment status
- **GET** `/api/admin/orders/stats` - Get order statistics

#### Discounts Management
- **GET** `/api/admin/discounts` - List all discounts
- **POST** `/api/admin/discounts` - Create new discount
- **GET** `/api/admin/discounts/{id}` - Get discount details
- **PUT** `/api/admin/discounts/{id}` - Update discount
- **DELETE** `/api/admin/discounts/{id}` - Delete discount
- **PATCH** `/api/admin/discounts/{id}/toggle-status` - Toggle discount status
- **POST** `/api/admin/discounts/validate` - Validate discount code

#### Users Management
- **GET** `/api/admin/users` - List all users
- **POST** `/api/admin/users` - Create new user
- **GET** `/api/admin/users/{id}` - Get user details
- **PUT** `/api/admin/users/{id}` - Update user
- **DELETE** `/api/admin/users/{id}` - Delete user
- **PATCH** `/api/admin/users/{id}/toggle-status` - Toggle user status
- **GET** `/api/admin/users/stats` - Get user statistics

### User API Endpoints

#### Authentication
- **POST** `/api/user/login` - User login
- **POST** `/api/user/register` - User registration
- **POST** `/api/user/logout` - User logout (protected)
- **POST** `/api/user/profile` - Get user profile (protected)

#### Products (Public)
- **GET** `/api/user/products` - List products (with filters)
- **GET** `/api/user/products/{id}` - Get product details
- **GET** `/api/user/products/featured/list` - Get featured products
- **GET** `/api/user/products/{id}/related` - Get related products

#### Categories (Public)
- **GET** `/api/user/categories` - List active categories
- **GET** `/api/user/categories/{id}` - Get category details
- **GET** `/api/user/categories/{id}/products` - Get category products

#### Cart (Protected)
- **GET** `/api/user/cart` - Get cart items
- **POST** `/api/user/cart` - Add item to cart
- **PUT** `/api/user/cart/{id}` - Update cart item quantity
- **DELETE** `/api/user/cart/{id}` - Remove item from cart
- **POST** `/api/user/cart/clear` - Clear cart
- **GET** `/api/user/cart/count` - Get cart item count

#### Orders (Protected)
- **GET** `/api/user/orders` - Get user orders
- **POST** `/api/user/orders` - Create new order
- **GET** `/api/user/orders/{id}` - Get order details
- **PATCH** `/api/user/orders/{id}/cancel` - Cancel order
- **GET** `/api/user/orders/stats` - Get user order statistics

#### Discounts (Public)
- **GET** `/api/user/discounts` - List active discounts
- **POST** `/api/user/discounts/validate` - Validate discount code

## Request/Response Format

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

## Query Parameters

### Product Filters
- `search` - Search in name, description, SKU
- `category_id` - Filter by category
- `min_price` - Minimum price filter
- `max_price` - Maximum price filter
- `in_stock` - Stock availability filter
- `featured` - Featured products only
- `sort_by` - Sort field (price, created_at, name)
- `sort_order` - Sort direction (asc, desc)
- `per_page` - Items per page (default: 15)

### Order Filters
- `search` - Search in order number, user name/email
- `status` - Order status filter
- `payment_status` - Payment status filter
- `date_from` - Start date filter
- `date_to` - End date filter
- `sort_by` - Sort field
- `sort_order` - Sort direction
- `per_page` - Items per page

## Models

### Product
- `id`, `name`, `slug`, `description`, `short_description`
- `price`, `sale_price`, `sku`, `stock_quantity`
- `manage_stock`, `in_stock`, `weight`, `dimensions`
- `images`, `is_featured`, `is_active`, `category_id`
- `created_at`, `updated_at`

### Category
- `id`, `name`, `slug`, `description`, `image`
- `is_active`, `sort_order`, `created_at`, `updated_at`

### Order
- `id`, `order_number`, `user_id`, `subtotal`
- `discount_amount`, `tax_amount`, `shipping_amount`, `total_amount`
- `status`, `payment_status`, `payment_method`
- `shipping_address`, `billing_address`, `notes`
- `created_at`, `updated_at`

### User
- `id`, `name`, `email`, `mobile`, `password`
- `role`, `is_registered`, `is_active`, `is_admin`
- `created_at`, `updated_at`

### Discount
- `id`, `name`, `code`, `type`, `value`
- `minimum_amount`, `usage_limit`, `used_count`
- `starts_at`, `expires_at`, `is_active`
- `created_at`, `updated_at`

## Installation & Setup

1. Run migrations:
```bash
php artisan migrate
```

2. Seed database (optional):
```bash
php artisan db:seed
```

3. Generate API documentation:
```bash
php artisan route:list --path=api
```

## Security Features

- API token authentication
- Role-based access control
- Input validation
- SQL injection protection
- XSS protection
- CSRF protection for web routes

## Rate Limiting

- Admin API: 100 requests per minute
- User API: 60 requests per minute
- Public routes: 30 requests per minute
