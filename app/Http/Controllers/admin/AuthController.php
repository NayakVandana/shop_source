<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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

    public function adminLogout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $userToken = UserToken::where('user_id', $user->id)->first();
                if ($userToken) {
                    $userToken->delete();
                }
            }

            // Logout admin guard as well
            \Illuminate\Support\Facades\Auth::guard('admin')->logout();
            // In API context the session may not be started; still attempt invalidation
            try { $request->session()->invalidate(); } catch (\Throwable $e) {}
            try { $request->session()->regenerateToken(); } catch (\Throwable $e) {}

            // Also explicitly forget possible cookies used by our app and the session cookie
            $response = $this->sendJsonResponse(true, 'Admin logged out successfully', null, 200);
            $response->headers->clearCookie(config('session.cookie'));
            $response->headers->clearCookie('auth_token');
            $response->headers->clearCookie('admin_token');

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function adminProfile()
    {
        try {
            $user = auth()->user();
            return $this->sendJsonResponse(true, 'Admin profile', $user, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
