# POST-Only API Documentation

## Overview
Your e-commerce API now uses **POST methods exclusively** for all operations. This approach provides enhanced security, consistency, and simplicity across all endpoints.

## 🔐 Security Benefits

### 1. **Enhanced Security**
- All requests go through POST method
- No URL parameter exposure for sensitive operations
- Consistent request handling across all endpoints
- Better protection against CSRF attacks

### 2. **Simplified Integration**
- Single HTTP method to remember
- Consistent request/response format
- Easier client-side implementation
- Reduced complexity in API calls

### 3. **Better Data Handling**
- All parameters sent in request body
- No URL length limitations
- Better support for complex data structures
- Consistent validation approach

## 📊 API Endpoints

### Base URLs
- **Admin API**: `/api/admin`
- **User API**: `/api/user`

### Admin API Endpoints (All POST)

#### Authentication
```http
POST /api/admin/admin-login
Content-Type: application/json

{
    "email": "admin@shop.com",
    "password": "password"
}
```

#### Dashboard & Statistics
```http
POST /api/admin/dashboard/stats
Authorization: Bearer {token}
```

#### Product Management
```http
POST /api/admin/products/list
POST /api/admin/products/create
POST /api/admin/products/show
POST /api/admin/products/update
POST /api/admin/products/delete
POST /api/admin/products/toggle-status
POST /api/admin/products/update-stock
```

#### Category Management
```http
POST /api/admin/categories/list
POST /api/admin/categories/create
POST /api/admin/categories/show
POST /api/admin/categories/update
POST /api/admin/categories/delete
POST /api/admin/categories/toggle-status
```

#### Order Management
```http
POST /api/admin/orders/list
POST /api/admin/orders/show
POST /api/admin/orders/delete
POST /api/admin/orders/update-status
POST /api/admin/orders/update-payment-status
POST /api/admin/orders/stats
```

#### Discount Management
```http
POST /api/admin/discounts/list
POST /api/admin/discounts/create
POST /api/admin/discounts/show
POST /api/admin/discounts/update
POST /api/admin/discounts/delete
POST /api/admin/discounts/toggle-status
POST /api/admin/discounts/validate
```

#### User Management
```http
POST /api/admin/users/list
POST /api/admin/users/create
POST /api/admin/users/show
POST /api/admin/users/update
POST /api/admin/users/delete
POST /api/admin/users/toggle-status
POST /api/admin/users/stats
```

### User API Endpoints (All POST)

#### Authentication
```http
POST /api/user/login
POST /api/user/register
POST /api/user/logout
POST /api/user/profile
```

#### Products (Public)
```http
POST /api/user/products/list
POST /api/user/products/show
POST /api/user/products/featured
POST /api/user/products/related
```

#### Categories (Public)
```http
POST /api/user/categories/list
POST /api/user/categories/show
POST /api/user/categories/products
```

#### Cart Management (Protected)
```http
POST /api/user/cart/list
POST /api/user/cart/add
POST /api/user/cart/update
POST /api/user/cart/remove
POST /api/user/cart/clear
POST /api/user/cart/count
```

#### Order Management (Protected)
```http
POST /api/user/orders/list
POST /api/user/orders/show
POST /api/user/orders/create
POST /api/user/orders/cancel
POST /api/user/orders/stats
```

#### Discounts (Public)
```http
POST /api/user/discounts/list
POST /api/user/discounts/validate
```

## 📝 Request Examples

### 1. Get Product Details
```http
POST /api/user/products/show
Content-Type: application/json

{
    "id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 2. Create Product (Admin)
```http
POST /api/admin/products/create
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "New Product",
    "description": "Product description",
    "price": 99.99,
    "sku": "PROD-001",
    "stock_quantity": 100,
    "category_id": 1
}
```

### 3. Update Product (Admin)
```http
POST /api/admin/products/update
Authorization: Bearer {token}
Content-Type: application/json

{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Updated Product Name",
    "price": 149.99
}
```

### 4. Add to Cart (User)
```http
POST /api/user/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "quantity": 2
}
```

### 5. Create Order (User)
```http
POST /api/user/orders/create
Authorization: Bearer {token}
Content-Type: application/json

{
    "shipping_address": "123 Main St, City, State",
    "payment_method": "credit_card",
    "discount_code": "WELCOME10"
}
```

### 6. Get Products with Filters
```http
POST /api/user/products/list
Content-Type: application/json

{
    "search": "smartphone",
    "category_id": 1,
    "min_price": 100,
    "max_price": 500,
    "sort_by": "price",
    "sort_order": "asc",
    "per_page": 10
}
```

## 🔍 Response Format

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Product Name",
        "price": "99.99"
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "price": ["The price must be a number."]
    }
}
```

## 🛡️ Security Features

### 1. **UUID-Based Security**
- All resources identified by UUIDs
- No sequential ID exposure
- Prevents enumeration attacks

### 2. **Request Validation**
- All parameters validated in request body
- UUID format validation
- Input sanitization

### 3. **Authentication & Authorization**
- Token-based authentication
- Role-based access control
- Admin vs User API separation

### 4. **Middleware Protection**
- UUID validation middleware
- User verification middleware
- Admin verification middleware

## 📋 Common Request Parameters

### Pagination
```json
{
    "page": 1,
    "per_page": 15
}
```

### Filtering
```json
{
    "search": "search term",
    "category_id": 1,
    "min_price": 100,
    "max_price": 500,
    "is_active": true
}
```

### Sorting
```json
{
    "sort_by": "created_at",
    "sort_order": "desc"
}
```

### UUID Parameters
```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## 🚀 Usage Examples

### cURL Examples

#### Get Product List
```bash
curl -X POST http://localhost:8000/api/user/products/list \
  -H "Content-Type: application/json" \
  -d '{"search": "phone", "per_page": 10}'
```

#### Create Product (Admin)
```bash
curl -X POST http://localhost:8000/api/admin/products/create \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Product",
    "description": "Product description",
    "price": 99.99,
    "sku": "PROD-001"
  }'
```

#### Add to Cart
```bash
curl -X POST http://localhost:8000/api/user/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "quantity": 2
  }'
```

## 🎯 Benefits Summary

✅ **Enhanced Security** - All operations use POST method  
✅ **Consistent Interface** - Single HTTP method for all operations  
✅ **Better Data Handling** - Complex data in request body  
✅ **UUID Protection** - Secure resource identification  
✅ **Simplified Integration** - Easier client implementation  
✅ **Comprehensive Validation** - All inputs properly validated  
✅ **Role-Based Access** - Admin and User API separation  

Your e-commerce API now provides a secure, consistent, and easy-to-use interface with POST-only methods! 🚀🔒
