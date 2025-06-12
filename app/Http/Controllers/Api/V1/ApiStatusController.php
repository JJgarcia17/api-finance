<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiStatusController extends Controller
{
    private $baseUrl;
    private $credentials;

    public function __construct()
    {
        // Detectar URL base automáticamente
        $configUrl = config('app.url');
        
        // Si la URL de configuración no es válida para el entorno actual, usar detección automática
        if ($this->isLocalUrl($configUrl) && !$this->isLocalEnvironment()) {
            $this->baseUrl = $this->detectBaseUrl();
        } else {
            $this->baseUrl = rtrim($configUrl, '/');
        }
        
        $this->credentials = [
            'email' => config('api_status.auth.email', 'admin@example.com'),
            'password' => config('api_status.auth.password', 'password')
        ];
    }
    
    private function isLocalUrl($url)
    {
        return strpos($url, '.test') !== false || 
               strpos($url, 'localhost') !== false || 
               strpos($url, '127.0.0.1') !== false ||
               strpos($url, '::1') !== false;
    }
    
    private function isLocalEnvironment()
    {
        return in_array(config('app.env'), ['local', 'development']) ||
               isset($_SERVER['HTTP_HOST']) && (
                   strpos($_SERVER['HTTP_HOST'], '.test') !== false ||
                   strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
               );
    }
    
    private function detectBaseUrl()
    {
        // Detectar protocolo
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
        
        // Detectar host
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        return $protocol . $host;
    }

    public function dashboard()
    {
        // Verificar que la vista existe
        if (!view()->exists('api-status.dashboard')) {
            return response()->json([
                'error' => 'Vista no encontrada',
                'message' => 'La vista api-status.dashboard no existe'
            ], 404);
        }
        
        return view('api-status.dashboard');
    }
    
    public function diagnostics()
    {
        return response()->json([
            'app_url_config' => config('app.url'),
            'detected_base_url' => $this->baseUrl,
            'server_info' => [
                'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'https' => $_SERVER['HTTPS'] ?? 'off',
                'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ],
            'environment' => config('app.env'),
            'auth_config' => [
                'email' => $this->credentials['email'],
                'password_configured' => !empty($this->credentials['password'])
            ],
            'test_urls' => [
                'login_url' => $this->baseUrl . '/api/v1/auth/login',
                'health_url' => $this->baseUrl . '/health'
            ]
        ]);
    }

    public function apiStatus()
    {
        try {
            // Verificar configuración
            if (empty($this->baseUrl) || $this->baseUrl === 'null') {
                throw new \Exception('APP_URL no está configurado correctamente');
            }
            
            // Login una sola vez y obtener token
            $token = $this->getAuthToken();
            
            // Definir endpoints a verificar
            $endpoints = $this->getEndpoints();
            
            // Verificar cada endpoint
            $results = [];
            foreach ($endpoints as $endpoint) {
                $results[] = $this->checkEndpoint($endpoint, $token);
            }
            
            // Calcular estadísticas
            $stats = $this->calculateStats($results);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'endpoints' => $results,
                    'stats' => $stats,
                    'auth_token_status' => $token ? 'active' : 'failed',
                    'last_updated' => now()->toISOString(),
                    'base_url' => $this->baseUrl,
                    'config_url' => config('app.url'),
                    'detected_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                    'environment' => config('app.env'),
                    'auth_credentials' => [
                        'email' => $this->credentials['email'],
                        'password_set' => !empty($this->credentials['password'])
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('API Status Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'base_url' => $this->baseUrl ?? 'undefined'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar endpoints',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'base_url' => $this->baseUrl ?? 'undefined'
            ], 500);
        }
    }

    private function getAuthToken()
    {
        $cacheKey = 'api_status_token';
        
        // Verificar si hay token en cache
        $token = Cache::get($cacheKey);
        if ($token && $this->isTokenValid($token)) {
            return $token;
        }
        
        // Hacer login para obtener nuevo token
        try {
            $loginUrl = $this->baseUrl . '/api/v1/auth/login';
            
            Log::info('Intentando login para API Status', [
                'url' => $loginUrl,
                'email' => $this->credentials['email'],
                'base_url' => $this->baseUrl
            ]);
            
            // Verificar que la URL login sea accesible primero
            $testResponse = Http::timeout(5)->get($this->baseUrl);
            if (!$testResponse->successful() && $testResponse->status() !== 404) {
                Log::error('Base URL no es accesible', [
                    'base_url' => $this->baseUrl,
                    'status' => $testResponse->status(),
                    'error' => $testResponse->body()
                ]);
                return null;
            }
            
            $response = Http::timeout(10)
                ->post($loginUrl, $this->credentials);
            
            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['data']['token'] ?? null;
                
                if ($newToken) {
                    // Guardar en cache por 50 minutos
                    Cache::put($cacheKey, $newToken, 50 * 60);
                    Log::info('Login exitoso para API Status');
                    return $newToken;
                }
            }
            
            Log::warning('Login failed para API Status', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $loginUrl,
                'credentials_email' => $this->credentials['email']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Login exception para API Status', [
                'error' => $e->getMessage(),
                'url' => $loginUrl ?? 'unknown',
                'base_url' => $this->baseUrl
            ]);
        }
        
        return null;
    }

    private function isTokenValid($token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->timeout(5)->get($this->baseUrl . '/api/v1/auth/me');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEndpoints()
    {
        return [
            // Auth endpoints
            ['name' => 'Login', 'method' => 'POST', 'url' => '/api/v1/auth/login', 'category' => 'Auth', 'auth' => false],
            ['name' => 'Me', 'method' => 'GET', 'url' => '/api/v1/auth/me', 'category' => 'Auth', 'auth' => true],
            ['name' => 'Logout', 'method' => 'POST', 'url' => '/api/v1/auth/logout', 'category' => 'Auth', 'auth' => true],
            ['name' => 'Refresh', 'method' => 'POST', 'url' => '/api/v1/auth/refresh', 'category' => 'Auth', 'auth' => true],
            
            // Categories
            ['name' => 'Get Categories', 'method' => 'GET', 'url' => '/api/v1/categories', 'category' => 'Categories', 'auth' => true],
            ['name' => 'Create Category', 'method' => 'POST', 'url' => '/api/v1/categories', 'category' => 'Categories', 'auth' => true],
            ['name' => 'Show Category', 'method' => 'GET', 'url' => '/api/v1/categories/1', 'category' => 'Categories', 'auth' => true],
            ['name' => 'Update Category', 'method' => 'PUT', 'url' => '/api/v1/categories/1', 'category' => 'Categories', 'auth' => true],
            ['name' => 'Delete Category', 'method' => 'DELETE', 'url' => '/api/v1/categories/1', 'category' => 'Categories', 'auth' => true],
            
            // Accounts
            ['name' => 'Get Accounts', 'method' => 'GET', 'url' => '/api/v1/accounts', 'category' => 'Accounts', 'auth' => true],
            ['name' => 'Account Stats', 'method' => 'GET', 'url' => '/api/v1/accounts/stats', 'category' => 'Accounts', 'auth' => true],
            ['name' => 'Create Account', 'method' => 'POST', 'url' => '/api/v1/accounts', 'category' => 'Accounts', 'auth' => true],
            ['name' => 'Show Account', 'method' => 'GET', 'url' => '/api/v1/accounts/1', 'category' => 'Accounts', 'auth' => true],
            ['name' => 'Update Account', 'method' => 'PUT', 'url' => '/api/v1/accounts/1', 'category' => 'Accounts', 'auth' => true],
            ['name' => 'Delete Account', 'method' => 'DELETE', 'url' => '/api/v1/accounts/1', 'category' => 'Accounts', 'auth' => true],
            
            // Transactions
            ['name' => 'Get Transactions', 'method' => 'GET', 'url' => '/api/v1/transactions', 'category' => 'Transactions', 'auth' => true],
            ['name' => 'Transaction Stats', 'method' => 'GET', 'url' => '/api/v1/transactions/stats', 'category' => 'Transactions', 'auth' => true],
            ['name' => 'Create Transaction', 'method' => 'POST', 'url' => '/api/v1/transactions', 'category' => 'Transactions', 'auth' => true],
            ['name' => 'Show Transaction', 'method' => 'GET', 'url' => '/api/v1/transactions/1', 'category' => 'Transactions', 'auth' => true],
            ['name' => 'Update Transaction', 'method' => 'PUT', 'url' => '/api/v1/transactions/1', 'category' => 'Transactions', 'auth' => true],
            ['name' => 'Delete Transaction', 'method' => 'DELETE', 'url' => '/api/v1/transactions/1', 'category' => 'Transactions', 'auth' => true],
            
            // Budgets
            ['name' => 'Get Budgets', 'method' => 'GET', 'url' => '/api/v1/budgets', 'category' => 'Budgets', 'auth' => true],
            ['name' => 'Create Budget', 'method' => 'POST', 'url' => '/api/v1/budgets', 'category' => 'Budgets', 'auth' => true],
            ['name' => 'Show Budget', 'method' => 'GET', 'url' => '/api/v1/budgets/1', 'category' => 'Budgets', 'auth' => true],
            ['name' => 'Update Budget', 'method' => 'PUT', 'url' => '/api/v1/budgets/1', 'category' => 'Budgets', 'auth' => true],
            ['name' => 'Delete Budget', 'method' => 'DELETE', 'url' => '/api/v1/budgets/1', 'category' => 'Budgets', 'auth' => true]
        ];
    }

    private function checkEndpoint($endpoint, $token)
    {
        $startTime = microtime(true);
        
        try {
            // Preparar headers
            $headers = [];
            if ($endpoint['auth'] && $token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $timeout = config('api_status.timeout.endpoint_check', 10);
            $client = Http::withHeaders($headers)->timeout($timeout);
            
            // Construir URL completa
            $fullUrl = $this->baseUrl . $endpoint['url'];
            
            // Solo verificar existencia - no enviar datos
            switch ($endpoint['method']) {
                case 'GET':
                    $response = $client->get($fullUrl);
                    break;
                case 'POST':
                    // Para POST, enviar petición vacía para verificar que existe
                    $response = $client->post($fullUrl, []);
                    break;
                case 'PUT':
                    $response = $client->put($fullUrl, []);
                    break;
                case 'DELETE':
                    $response = $client->delete($fullUrl);
                    break;
                default:
                    $response = $client->get($fullUrl);
            }
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $status = $this->determineStatus($response, $endpoint);
            
            return [
                'name' => $endpoint['name'],
                'method' => $endpoint['method'],
                'url' => $endpoint['url'],
                'full_url' => $fullUrl,
                'category' => $endpoint['category'],
                'status' => $status,
                'response_time' => $responseTime,
                'http_status' => $response->status(),
                'message' => $this->getStatusMessage($status, $response->status())
            ];
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Log del error para debugging
            Log::warning('Error verificando endpoint', [
                'endpoint' => $endpoint['name'],
                'url' => $endpoint['url'],
                'error' => $e->getMessage()
            ]);
            
            return [
                'name' => $endpoint['name'],
                'method' => $endpoint['method'],
                'url' => $endpoint['url'],
                'full_url' => $this->baseUrl . $endpoint['url'],
                'category' => $endpoint['category'],
                'status' => 'error',
                'response_time' => $responseTime,
                'http_status' => 0,
                'message' => 'Error de conexión: ' . $this->getSimpleErrorMessage($e)
            ];
        }
    }
    
    private function getSimpleErrorMessage(\Exception $e)
    {
        $message = $e->getMessage();
        
        // Simplificar mensajes de error comunes
        if (strpos($message, 'cURL error 6') !== false) {
            return 'No se pudo resolver el DNS';
        }
        if (strpos($message, 'cURL error 7') !== false) {
            return 'No se pudo conectar al servidor';
        }
        if (strpos($message, 'Connection timed out') !== false) {
            return 'Tiempo de conexión agotado';
        }
        
        return $message;
    }

    private function determineStatus($response, $endpoint)
    {
        $status = $response->status();
        
        // 200-299: Éxito
        if ($status >= 200 && $status < 300) {
            return 'active';
        }
        
        // 401: No autorizado
        if ($status === 401) {
            return $endpoint['auth'] ? 'requires_auth' : 'unauthorized';
        }
        
        // 422: Error de validación (endpoint existe pero faltan datos)
        if ($status === 422) {
            return 'active'; // Consideramos que existe
        }
        
        // 404: No encontrado
        if ($status === 404) {
            return 'not_found';
        }
        
        // 405: Método no permitido
        if ($status === 405) {
            return 'method_not_allowed';
        }
        
        // 500+: Error del servidor
        if ($status >= 500) {
            return 'server_error';
        }
        
        return 'error';
    }

    private function getStatusMessage($status, $httpStatus)
    {
        switch ($status) {
            case 'active':
                return $httpStatus === 422 ? 'Existe (requiere datos)' : 'Funcionando';
            case 'requires_auth':
                return 'Requiere autenticación';
            case 'unauthorized':
                return 'No autorizado';
            case 'not_found':
                return 'No encontrado (404)';
            case 'method_not_allowed':
                return 'Método no permitido (405)';
            case 'server_error':
                return 'Error del servidor (5xx)';
            case 'error':
                return 'Error HTTP ' . $httpStatus;
            default:
                return 'Desconocido';
        }
    }

    private function calculateStats($results)
    {
        $total = count($results);
        $active = 0;
        $errors = 0;
        $totalTime = 0;
        $validTimes = 0;
        
        foreach ($results as $result) {
            if (in_array($result['status'], ['active', 'requires_auth'])) {
                $active++;
            } else {
                $errors++;
            }
            
            if ($result['response_time'] > 0) {
                $totalTime += $result['response_time'];
                $validTimes++;
            }
        }
        
        return [
            'total_endpoints' => $total,
            'active_endpoints' => $active,
            'error_endpoints' => $errors,
            'average_response_time' => $validTimes > 0 ? round($totalTime / $validTimes) : 0,
            'success_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0
        ];
    }
}