<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::all();
            return $this->sendJsonResponse(true, 'Products retrieved', $products);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}