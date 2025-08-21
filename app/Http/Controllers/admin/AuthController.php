<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'user_id' => ['required', 'numeric', 'exists:users,id']
            ]);

            if ($validation->fails()) {
                return $this->sendJsonResponse(false, 'Invalid data', $validation->errors()->getMessages(), 200);
            }

            $admin = User::where('id', $request->input('user_id'))
                         ->where('is_admin', true)
                         ->where('is_registered', true)
                         ->first();

            if (!$admin) {
                return $this->sendJsonResponse(false, 'Admin not active', null, 200);
            }

            $admin->access_token = $admin->createAdminToken();

            // Placeholder: Dispatch AdminLogin event
            // AdminLogin::dispatch($admin);

            return $this->sendJsonResponse(true, 'Admin successfully logged in', $admin, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}