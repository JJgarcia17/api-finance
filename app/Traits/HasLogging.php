<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Trait HasLogging
 * 
 * Proporciona métodos estandarizados para logging con formato consistente
 * siguiendo el patrón establecido en el proyecto.
 */
trait HasLogging
{
    /**
     * Log de error con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @param Exception|null $exception
     * @return void
     */
    protected function logError(string $message, array $context = [], ?Exception $exception = null): void
    {
        $logContext = $this->buildLogContext($context);
        
        if ($exception) {
            $logContext = array_merge($logContext, [
                'error_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
        }
        
        Log::error($message, $logContext);
    }
    
    /**
     * Log de info con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $this->buildLogContext($context));
    }
    
    /**
     * Log de warning con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $this->buildLogContext($context));
    }
    
    /**
     * Log de debug con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($message, $this->buildLogContext($context));
    }
    
    /**
     * Construye el contexto base para los logs
     * 
     * @param array $additionalContext
     * @return array
     */
    private function buildLogContext(array $additionalContext = []): array
    {
        $baseContext = [];
        
        // Agregar user_id si está disponible
        if (auth('sanctum')->check()) {
            $baseContext['user_id'] = auth('sanctum')->id();
        }
        
        // Agregar información de clase y método
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        if (isset($trace[2])) {
            $baseContext['class'] = $trace[2]['class'] ?? static::class;
            $baseContext['method'] = $trace[2]['function'] ?? 'unknown';
        }
        
        return array_merge($baseContext, $additionalContext);
    }
    
    /**
     * Helper para logs de operaciones CRUD exitosas
     * 
     * @param string $operation
     * @param string $entity
     * @param int|string $entityId
     * @param array $additionalContext
     * @return void
     */
    protected function logCrudSuccess(string $operation, string $entity, $entityId, array $additionalContext = []): void
    {
        $message = ucfirst($entity) . ' ' . $operation . ' successfully';
        $context = array_merge([
            $entity . '_id' => $entityId
        ], $additionalContext);
        
        $this->logInfo($message, $context);
    }
    
    /**
     * Helper para logs de errores en operaciones CRUD
     * 
     * @param string $operation
     * @param string $entity
     * @param string $controllerMethod
     * @param Exception $exception
     * @param array $additionalContext
     * @return void
     */
    protected function logCrudError(string $operation, string $entity, string $controllerMethod, Exception $exception, array $additionalContext = []): void
    {
        $message = 'Error ' . $operation . ' ' . $entity . ' en ' . $controllerMethod;
        
        $this->logError($message, $additionalContext, $exception);
    }
}