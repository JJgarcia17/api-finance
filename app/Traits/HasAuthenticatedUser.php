<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;

/**
 * Trait HasAuthenticatedUser
 * 
 * Proporciona métodos helper para trabajar con usuarios autenticados
 * con validación y logging integrado.
 */
trait HasAuthenticatedUser
{
    /**
     * Obtiene el ID del usuario autenticado
     * 
     * @return int
     * @throws AuthenticationException
     */
    protected function userId(): int
    {
        // Para APIs con Sanctum, usar auth() sin guard específico
        // o auth('sanctum') explícitamente
        $user = auth('sanctum')->user();
        
        if (!$user) {
            Log::warning('Intento de acceso sin autenticación', [
                'class' => static::class,
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
            
            throw new AuthenticationException('Usuario no autenticado');
        }
        
        Log::info('Acceso de usuario autenticado', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'class' => static::class,
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
        
        return $user->id;
    }
    
    /**
     * Obtiene el usuario autenticado completo
     * 
     * @return User
     * @throws AuthenticationException
     */
    protected function user(): User
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            Log::warning('Intento de acceso sin autenticación', [
                'class' => static::class,
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
            
            throw new AuthenticationException('Usuario no autenticado');
        }
        
        return $user;
    }
    
    /**
     * Verifica si hay un usuario autenticado
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        $isAuthenticated = auth('sanctum')->check();
        
        Log::debug('Verificación de autenticación', [
            'authenticated' => $isAuthenticated,
            'user_id' => $isAuthenticated ? auth('sanctum')->id() : null,
            'class' => static::class,
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
        
        return $isAuthenticated;
    }
    
    /**
     * Obtiene el ID del usuario autenticado de forma segura
     * Retorna null si no está autenticado en lugar de lanzar excepción
     * 
     * @return int|null
     */
    protected function safeUserId(): ?int
    {
        return auth('sanctum')->id();
    }
}