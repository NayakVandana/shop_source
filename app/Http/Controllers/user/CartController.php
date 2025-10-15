<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;

class CartController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $cartItems = CartItem::with('product.category')
                ->where('user_id', $user->id)
                ->get();

            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item->product->current_price * $item->quantity;
            }

            return $this->sendJsonResponse(true, 'Cart retrieved successfully', [
                'items' => $cartItems,
                'total' => $total,
                'item_count' => $cartItems->count()
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function add(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $user = auth()->user();
            $product = Product::findOrFail($data['product_id']);

            // Check if product is active and in stock
            if (!$product->is_active) {
                return $this->sendJsonResponse(false, 'Product is not available', null, 400);
            }

            if ($product->manage_stock && $product->stock_quantity < $data['quantity']) {
                return $this->sendJsonResponse(false, 'Insufficient stock', null, 400);
            }

            // Check if item already exists in cart
            $cartItem = CartItem::where('user_id', $user->id)
                ->where('product_id', $data['product_id'])
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $data['quantity'];
                
                if ($product->manage_stock && $product->stock_quantity < $newQuantity) {
                    return $this->sendJsonResponse(false, 'Insufficient stock', null, 400);
                }

                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity']
                ]);
            }

            return $this->sendJsonResponse(true, 'Item added to cart successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer',
                'quantity' => 'required|integer|min:1'
            ]);

            $user = auth()->user();
            $cartItem = CartItem::where('user_id', $user->id)
                ->where('id', $data['id'])
                ->firstOrFail();

            $product = $cartItem->product;

            if ($product->manage_stock && $product->stock_quantity < $data['quantity']) {
                return $this->sendJsonResponse(false, 'Insufficient stock', null, 400);
            }

            $cartItem->update(['quantity' => $data['quantity']]);

            return $this->sendJsonResponse(true, 'Cart item updated successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer'
            ]);

            $user = auth()->user();
            $cartItem = CartItem::where('user_id', $user->id)
                ->where('id', $data['id'])
                ->firstOrFail();

            $cartItem->delete();

            return $this->sendJsonResponse(true, 'Item removed from cart successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function clear()
    {
        try {
            $user = auth()->user();
            CartItem::where('user_id', $user->id)->delete();

            return $this->sendJsonResponse(true, 'Cart cleared successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function count()
    {
        try {
            $user = auth()->user();
            $count = CartItem::where('user_id', $user->id)->sum('quantity');

            return $this->sendJsonResponse(true, 'Cart count retrieved successfully', ['count' => $count]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}