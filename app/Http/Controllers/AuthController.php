<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success(
            ['user' => $result['user'], 'token' => $result['token']],
            'User registered successfully',
            201,
        );
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success(
            ['user' => $result['user'], 'token' => $result['token']],
            'Login successful',
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout successful');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return ApiResponse::success(
            [
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions(),
            ],
            'User profile retrieved successfully',
        );
    }
}
