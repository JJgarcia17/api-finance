<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Traits\HasLogging;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;

class AuthService
{
    use HasLogging;

    /**
     * Registrar nuevo usuario
     */
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Usuario registrado exitosamente'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error en registro de usuario', [], $e);
            throw new Exception('Error interno del servidor durante el registro');
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(array $credentials): array
    {
        try {
            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }

            // Revocar tokens anteriores (opcional)
            $user->tokens()->delete();

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Inicio de sesión exitoso'
            ];
        } catch (Exception $e) {
            $this->logError('Error en inicio de sesión', [], $e);
            throw new Exception('Error interno del servidor durante el inicio de sesión');
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(User $user): array
    {
        try {
            if ($user->currentAccessToken()) {
                $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
            }

            return [
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ];
        } catch (Exception $e) {
            $this->logError('Error al cerrar sesión', [], $e);
            throw new Exception('Error interno del servidor al cerrar sesión');
        }
    }

    /**
     * Refrescar token
     */
    public function refreshToken(User $user): array
    {
        try {
            // Revocar token actual
            if ($user->currentAccessToken()) {
                $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
            }

            // Crear nuevo token
            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'success' => true,
                'token' => $token,
                'message' => 'Token renovado exitosamente'
            ];
        } catch (Exception $e) {
            $this->logError('Error al renovar token', [], $e);
            throw new Exception('Error interno del servidor al renovar token');
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function getAuthenticatedUser(User $user): array
    {
        try {
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Usuario obtenido exitosamente'
            ];
        } catch (Exception $e) {
            $this->logError('Error al obtener usuario', [], $e);
            throw new Exception('Error interno del servidor al obtener usuario');
        }
    }
}