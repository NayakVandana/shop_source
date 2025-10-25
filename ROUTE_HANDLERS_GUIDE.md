# Laravel Route Handlers Implementation Guide

This guide demonstrates various ways to implement route handlers in Laravel, from basic closures to advanced patterns with middleware, validation, and model binding.

## Table of Contents

1. [Basic Route Handlers](#basic-route-handlers)
2. [Controller-Based Handlers](#controller-based-handlers)
3. [Resource Route Handlers](#resource-route-handlers)
4. [API Resource Handlers](#api-resource-handlers)
5. [Route Model Binding](#route-model-binding)
6. [Middleware Integration](#middleware-integration)
7. [Validation Patterns](#validation-patterns)
8. [File Upload Handlers](#file-upload-handlers)
9. [Caching Strategies](#caching-strategies)
10. [Search and Filtering](#search-and-filtering)
11. [Bulk Operations](#bulk-operations)
12. [Export Functionality](#export-functionality)
13. [Statistics and Analytics](#statistics-and-analytics)
14. [Error Handling](#error-handling)
15. [Best Practices](#best-practices)

## Basic Route Handlers

### Closure-Based Handlers

```php
// Simple GET route
Route::get('/welcome', function () {
    return response()->json([
        'message' => 'Welcome to our API!',
        'timestamp' => now()
    ]);
});

// POST route with validation
Route::post('/contact', function (Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'message' => 'required|string|min:10'
    ]);
    
    // Process contact form
    
    return response()->json([
        'status' => true,
        'message' => 'Message sent successfully'
    ]);
});
```

### Controller-Based Handlers

```php
// Single action controller
Route::get('/dashboard', DashboardController::class);

// Controller with specific methods
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
```

## Controller-Based Handlers

### Single Action Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Exception;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
                'total_users' => User::count(),
                'featured_products' => Product::where('is_featured', true)->count(),
                'low_stock_products' => Product::where('stock_quantity', '<', 10)->count(),
            ];

            $recent_products = Product::with('category')
                ->latest()
                ->limit(5)
                ->get();

            $top_categories = Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(5)
                ->get();

            return $this->sendJsonResponse(true, 'Dashboard data retrieved successfully', [
                'stats' => $stats,
                'recent_products' => $recent_products,
                'top_categories' => $top_categories
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
```

### Multi-Method Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with('category');
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_featured' => 'boolean',
                'stock_quantity' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', [
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product = Product::create($data);
            $product->load('category');

            return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    // ... other methods
}
```

## Resource Route Handlers

### Full Resource Routes

```php
// Creates all CRUD routes
Route::resource('products', ProductController::class);
```

### Partial Resource Routes

```php
// Only specific methods
Route::resource('categories', CategoryController::class)->only([
    'index', 'show', 'store', 'update'
]);

// Exclude specific methods
Route::resource('users', UserController::class)->except([
    'create', 'edit'
]);
```

## API Resource Handlers

### API Resource Routes

```php
// API resource routes (no create/edit routes)
Route::apiResource('products', ApiProductController::class);

// Multiple API resources
Route::apiResources([
    'products' => ApiProductController::class,
    'categories' => ApiCategoryController::class,
]);
```

### API Controller Example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with('category');
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    // ... other methods
}
```

## Route Model Binding

### Implicit Model Binding

```php
// Basic implicit binding
Route::get('/products/{product}', function (Product $product) {
    return response()->json([
        'status' => true,
        'data' => $product->load('category')
    ]);
});

// Multiple model bindings
Route::get('/categories/{category}/products/{product}', function (Category $category, Product $product) {
    if ($product->category_id !== $category->id) {
        return response()->json([
            'status' => false,
            'message' => 'Product does not belong to this category'
        ], 404);
    }
    
    return response()->json([
        'status' => true,
        'data' => [
            'category' => $category,
            'product' => $product
        ]
    ]);
});
```

### Custom Key Model Binding

```php
// Bind by slug instead of ID
Route::get('/products/slug/{product:slug}', function (Product $product) {
    return response()->json([
        'status' => true,
        'data' => $product
    ]);
});

// Bind category by slug
Route::get('/categories/slug/{category:slug}', function (Category $category) {
    return response()->json([
        'status' => true,
        'data' => $category->loadCount('products')
    ]);
});
```

### Custom Resolution Logic

Add to `RouteServiceProvider::boot()` method:

```php
Route::bind('product', function ($value) {
    return Product::where('id', $value)
        ->orWhere('slug', $value)
        ->first() ?? abort(404);
});
```

## Middleware Integration

### Basic Middleware

```php
// Single middleware
Route::get('/protected', function () {
    return response()->json([
        'status' => true,
        'message' => 'This is protected content'
    ]);
})->middleware('auth:sanctum');

// Multiple middleware
Route::post('/admin-only', function () {
    return response()->json([
        'status' => true,
        'message' => 'Admin only content'
    ]);
})->middleware(['auth:sanctum', 'admin']);
```

### Middleware Groups

```php
// Group with middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', function () {
        return response()->json([
            'status' => true,
            'data' => auth()->user()
        ]);
    });
    
    Route::post('/logout', function () {
        auth()->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    });
});

// Group with prefix
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'status' => true,
            'message' => 'Admin Dashboard'
        ]);
    });
});

// Group with both prefix and middleware
Route::prefix('api/v1')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin-stats', function () {
        return response()->json([
            'status' => true,
            'data' => [
                'total_users' => User::count(),
                'total_products' => Product::count(),
                'total_categories' => Category::count()
            ]
        ]);
    });
});
```

## Validation Patterns

### Basic Validation

```php
Route::post('/products', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'stock_quantity' => 'required|integer|min:0'
    ]);
    
    $product = Product::create($validated);
    
    return response()->json([
        'status' => true,
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
});
```

### Custom Validation

```php
Route::post('/products/custom-validation', function (Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'custom_field' => 'required|string|min:5|max:50'
    ]);
    
    // Custom validation logic
    if ($request->price > 1000 && $request->custom_field !== 'premium') {
        return response()->json([
            'status' => false,
            'message' => 'Premium products require premium field'
        ], 422);
    }
    
    $product = Product::create($request->all());
    
    return response()->json([
        'status' => true,
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
});
```

## File Upload Handlers

### Basic File Upload

```php
Route::post('/upload', function (Illuminate\Http\Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
    ]);
    
    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    $path = $file->storeAs('uploads', $filename, 'public');
    
    return response()->json([
        'status' => true,
        'message' => 'File uploaded successfully',
        'data' => [
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
            'url' => asset('storage/' . $path)
        ]
    ]);
});
```

### Image Upload with Product

```php
Route::post('/products', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);
    
    // Handle image upload
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public');
        $validated['image'] = $imagePath;
    }
    
    $product = Product::create($validated);
    
    return response()->json([
        'status' => true,
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
});
```

## Caching Strategies

### Basic Caching

```php
Route::get('/products/cached', function () {
    $products = Cache::remember('products_list', 3600, function () {
        return Product::with('category')->get();
    });
    
    return response()->json([
        'status' => true,
        'data' => $products
    ]);
});
```

### Cached Featured Products

```php
Route::get('/products/featured/cached', function () {
    $products = Cache::remember('featured_products', 3600, function () {
        return Product::where('is_featured', true)
            ->with('category')
            ->limit(8)
            ->get();
    });
    
    return response()->json([
        'status' => true,
        'data' => $products
    ]);
});
```

## Search and Filtering

### Product Search

```php
Route::post('/products/search', function (Illuminate\Http\Request $request) {
    $request->validate([
        'query' => 'required|string|min:2',
        'category_id' => 'nullable|exists:categories,id',
        'min_price' => 'nullable|numeric|min:0',
        'max_price' => 'nullable|numeric|min:0'
    ]);
    
    $query = Product::with('category');
    
    // Search in name and description
    $query->where(function ($q) use ($request) {
        $q->where('name', 'like', '%' . $request->query . '%')
          ->orWhere('description', 'like', '%' . $request->query . '%');
    });
    
    // Apply filters
    if ($request->category_id) {
        $query->where('category_id', $request->category_id);
    }
    
    if ($request->min_price) {
        $query->where('price', '>=', $request->min_price);
    }
    
    if ($request->max_price) {
        $query->where('price', '<=', $request->max_price);
    }
    
    $products = $query->paginate(15);
    
    return response()->json([
        'status' => true,
        'message' => 'Search results retrieved successfully',
        'data' => $products
    ]);
});
```

## Bulk Operations

### Bulk Update

```php
Route::post('/products/bulk-update', function (Illuminate\Http\Request $request) {
    $request->validate([
        'product_ids' => 'required|array',
        'product_ids.*' => 'exists:products,id',
        'updates' => 'required|array'
    ]);
    
    $updated = Product::whereIn('id', $request->product_ids)
        ->update($request->updates);
    
    return response()->json([
        'status' => true,
        'message' => "Updated {$updated} products successfully"
    ]);
});
```

### Bulk Delete

```php
Route::post('/products/bulk-delete', function (Illuminate\Http\Request $request) {
    $request->validate([
        'product_ids' => 'required|array',
        'product_ids.*' => 'exists:products,id'
    ]);
    
    $deleted = Product::whereIn('id', $request->product_ids)->delete();
    
    return response()->json([
        'status' => true,
        'message' => "Deleted {$deleted} products successfully"
    ]);
});
```

## Export Functionality

### CSV Export

```php
Route::get('/products/export/csv', function () {
    $products = Product::with('category')->get();
    
    $csv = "ID,Name,Price,Category,Stock,Featured\n";
    foreach ($products as $product) {
        $csv .= "{$product->id},{$product->name},{$product->price},{$product->category->name},{$product->stock_quantity}," . ($product->is_featured ? 'Yes' : 'No') . "\n";
    }
    
    return response($csv)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="products.csv"');
});
```

### JSON Export

```php
Route::get('/products/export/json', function () {
    $products = Product::with('category')->get();
    
    return response()->json([
        'status' => true,
        'data' => $products
    ]);
});
```

## Statistics and Analytics

### Product Statistics

```php
Route::get('/products/stats', function () {
    $stats = [
        'total_products' => Product::count(),
        'featured_products' => Product::where('is_featured', true)->count(),
        'low_stock_products' => Product::where('stock_quantity', '<', 10)->count(),
        'out_of_stock_products' => Product::where('stock_quantity', 0)->count(),
        'average_price' => Product::avg('price'),
        'total_value' => Product::sum(DB::raw('price * stock_quantity'))
    ];
    
    return response()->json([
        'status' => true,
        'data' => $stats
    ]);
});
```

## Error Handling

### Custom Error Responses

```php
Route::get('/custom-error', function () {
    return response()->json([
        'status' => false,
        'message' => 'Something went wrong',
        'error_code' => 'CUSTOM_ERROR',
        'timestamp' => now()
    ], 500);
});
```

### Error Handling in Controllers

```php
public function store(Request $request): JsonResponse
{
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse(false, 'Validation failed', [
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
        
    } catch (Exception $e) {
        return $this->sendError($e);
    }
}
```

## Best Practices

### 1. Use Consistent Response Format

```php
// Always use the same response format
return response()->json([
    'status' => true,
    'message' => 'Success message',
    'data' => $data
]);
```

### 2. Implement Proper Validation

```php
// Always validate input
$request->validate([
    'field' => 'required|string|max:255'
]);
```

### 3. Use Middleware for Authentication

```php
// Protect routes with middleware
Route::middleware(['auth:sanctum'])->group(function () {
    // Protected routes
});
```

### 4. Implement Rate Limiting

```php
// Add rate limiting to prevent abuse
Route::middleware('throttle:60,1')->group(function () {
    // Rate limited routes
});
```

### 5. Use Model Binding

```php
// Use model binding instead of manual queries
Route::get('/products/{product}', function (Product $product) {
    return response()->json(['data' => $product]);
});
```

### 6. Implement Caching

```php
// Cache expensive operations
$data = Cache::remember('key', 3600, function () {
    return expensiveOperation();
});
```

### 7. Use Resource Controllers

```php
// Use resource controllers for CRUD operations
Route::apiResource('products', ProductController::class);
```

### 8. Implement Proper Error Handling

```php
// Always handle exceptions
try {
    // Your code
} catch (Exception $e) {
    return $this->sendError($e);
}
```

### 9. Use Pagination

```php
// Always paginate large datasets
$products = Product::paginate(15);
```

### 10. Implement Search and Filtering

```php
// Provide search and filtering capabilities
if ($request->has('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}
```

## Integration with Existing Routes

To integrate these route handlers into your existing application:

1. **Copy the relevant route patterns** from the example files
2. **Add them to your existing route files** (web.php, api.php, etc.)
3. **Create the necessary controllers** if they don't exist
4. **Update your middleware** as needed
5. **Test the routes** to ensure they work correctly

## File Structure

```
routes/
├── web.php                    # Main web routes
├── api.php                    # API routes
├── user-api.php              # User-specific API routes
├── admin-api.php             # Admin-specific API routes
├── examples.php              # Route handler examples
├── route-handlers.php        # Practical route implementations
├── model-binding-examples.php # Model binding examples
└── practical-implementation.php # Integration examples

app/Http/Controllers/
├── Controller.php             # Base controller
├── ProductController.php      # Product controller
├── CategoryController.php     # Category controller
├── UserController.php         # User controller
├── DashboardController.php    # Dashboard controller
└── Api/
    ├── ProductController.php  # API product controller
    └── CategoryController.php # API category controller
```

## Conclusion

This guide provides comprehensive examples of route handlers in Laravel. Choose the patterns that best fit your application's needs and implement them accordingly. Remember to always validate input, handle errors properly, and use appropriate middleware for security.
