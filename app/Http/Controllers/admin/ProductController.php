<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $products = Product::all();
            return $this->sendJsonResponse(true, 'Products retrieved', $products);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric'
            ]);

            $product = Product::create($data);
            return $this->sendJsonResponse(true, 'Product created', $product, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function manage(Request $request)
    {
        try {
            $data = $request->validate([
                'action' => 'required|in:update,delete',
                'id' => 'required_if:action,update,delete|exists:products,id',
                'name' => 'required_if:action,update|string',
                'description' => 'required_if:action,update|string',
                'price' => 'required_if:action,update|numeric'
            ]);

            if ($data['action'] === 'update') {
                $product = Product::find($data['id']);
                $product->update([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price']
                ]);
                return $this->sendJsonResponse(true, 'Product updated', $product);
            }

            if ($data['action'] === 'delete') {
                $product = Product::find($data['id']);
                $product->delete();
                return $this->sendJsonResponse(true, 'Product deleted');
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}