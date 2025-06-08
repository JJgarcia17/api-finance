<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AuthService;
use App\Traits\HasLogging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AuthController extends Controller
{
    use HasLogging;

    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Registrar nuevo usuario
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'success' => true,
                'status' => 201,
                'message' => $result['message'],
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token']
                ]
            ], 201);
        } catch (Exception $e) {
            $this->logError('Error en registro', [], $e);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => $result['message']
                ], 401);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => $result['message'],
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token']
                ]
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error en login', [], $e);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->logout($request->user());

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => $result['message']
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error en logout', [], $e);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->getAuthenticatedUser($request->user());

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => $result['message'],
                'data' => [
                    'user' => new UserResource($result['user'])
                ]
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al obtener usuario', [], $e);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refrescar token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken($request->user());

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => $result['message'],
                'data' => [
                    'token' => $result['token']
                ]
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al renovar token', [], $e);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}