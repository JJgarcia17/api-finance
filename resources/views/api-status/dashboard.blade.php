<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Status Dashboard</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback CSS para producción -->
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header Bento Card -->
        <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-white/20 p-8 mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                            API Status Dashboard
                        </h1>
                        <p class="text-gray-600 mt-1 text-lg">Monitor en tiempo real del estado de todos los endpoints</p>
                    </div>
                </div>
                <div class="text-right space-y-2">
                    <div id="lastUpdated" class="text-sm text-gray-500 font-medium">Cargando...</div>
                    <div id="authStatus" class="text-sm font-semibold">
                        Estado: <span class="px-3 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Verificando...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bento Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6 mb-8">
            <!-- Total Endpoints - Large Card -->
            <div class="lg:col-span-2 xl:col-span-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl shadow-xl p-8 text-white relative overflow-hidden group hover:scale-105 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                            <i class="fas fa-server text-2xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-blue-100 text-sm font-medium">Total</p>
                            <p class="text-blue-50 text-xs">Endpoints</p>
                        </div>
                    </div>
                    <p id="totalEndpoints" class="text-5xl font-bold mb-2">-</p>
                    <p class="text-blue-100 text-sm">Endpoints monitoreados</p>
                </div>
            </div>

            <!-- Active Endpoints -->
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl shadow-xl p-6 text-white relative overflow-hidden group hover:scale-105 transition-all duration-300">
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <p id="activeEndpoints" class="text-3xl font-bold mb-1">-</p>
                    <p class="text-green-100 text-sm font-medium">Activos</p>
                </div>
            </div>

            <!-- Error Endpoints -->
            <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-3xl shadow-xl p-6 text-white relative overflow-hidden group hover:scale-105 transition-all duration-300">
                <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <p id="errorEndpoints" class="text-3xl font-bold mb-1">-</p>
                    <p class="text-red-100 text-sm font-medium">Con Errores</p>
                </div>
            </div>

            <!-- Response Time - Tall Card -->
            <div class="lg:col-span-2 xl:col-span-2 lg:row-span-2 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-3xl shadow-xl p-8 text-white relative overflow-hidden group hover:scale-105 transition-all duration-300">
                <div class="absolute top-0 left-0 w-40 h-40 bg-white/5 rounded-full -translate-x-20 -translate-y-20 group-hover:scale-110 transition-transform duration-700"></div>
                <div class="absolute bottom-0 right-0 w-32 h-32 bg-white/5 rounded-full translate-x-16 translate-y-16 group-hover:scale-110 transition-transform duration-700"></div>
                <div class="relative z-10 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <div class="p-4 bg-white/20 rounded-2xl backdrop-blur-sm">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-purple-100 text-sm font-medium">Tiempo</p>
                            <p class="text-purple-50 text-xs">Promedio</p>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col justify-center">
                        <p id="avgResponseTime" class="text-6xl font-bold mb-4">-</p>
                        <p class="text-purple-100 text-lg font-medium">milisegundos</p>
                        <div class="mt-6 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-200">Más rápido:</span>
                                <span id="fastestTime" class="font-semibold">- ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-200">Más lento:</span>
                                <span id="slowestTime" class="font-semibold">- ms</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Status Distribution Chart -->
            <div class="lg:col-span-1 bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-white/20 p-6 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center mb-6">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl text-white mr-4">
                        <i class="fas fa-pie-chart text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Distribución</h3>
                </div>
                <div class="relative h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Response Time Chart -->
            <div class="lg:col-span-2 bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-white/20 p-6 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center mb-6">
                    <div class="p-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl text-white mr-4">
                        <i class="fas fa-chart-bar text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Tiempos de Respuesta por Categoría</h3>
                </div>
                <div class="relative h-64">
                    <canvas id="responseTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Endpoints Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-white/20 overflow-hidden hover:shadow-2xl transition-all duration-300">
            <div class="px-8 py-6 border-b border-gray-200/50 bg-gradient-to-r from-gray-50 to-blue-50">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl text-white mr-4">
                        <i class="fas fa-list text-lg"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Estado Detallado de Endpoints</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Endpoint</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Método</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Categoría</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tiempo</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Mensaje</th>
                        </tr>
                    </thead>
                    <tbody id="endpointsTableBody" class="divide-y divide-gray-200/50">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                                    <p class="text-lg font-medium">Cargando datos...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Refresh Button -->
        <div class="mt-8 text-center space-y-4">
            <button id="refreshBtn" class="group bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-8 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-sync-alt mr-3 group-hover:rotate-180 transition-transform duration-500"></i>
                Actualizar Datos
            </button>
            
            <!-- Diagnostics Button -->
            <div>
                <button id="diagnosticsBtn" class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-medium py-2 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                    <i class="fas fa-diagnoses mr-2"></i>
                    Ver Diagnósticos
                </button>
            </div>
        </div>
    </div>

    <script>
        let statusChart = null;
        let responseTimeChart = null;
        let isLoading = false;

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadApiStatus();
            
            // Auto-refresh cada 30 segundos
            setInterval(loadApiStatus, 30000);
            
            // Botón de refresh manual
            document.getElementById('refreshBtn').addEventListener('click', function() {
                if (!isLoading) {
                    loadApiStatus();
                }
            });
            
            // Botón de diagnósticos
            document.getElementById('diagnosticsBtn').addEventListener('click', function() {
                showDiagnostics();
            });
        });

        async function loadApiStatus() {
            if (isLoading) return;
            
            isLoading = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Cargando...';
            refreshBtn.disabled = true;

            try {
                // Usar URL absoluta o relativa según el entorno
                const baseUrl = window.location.origin;
                const response = await fetch(`${baseUrl}/api-status/json`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();

                if (data.success) {
                    updateStats(data.data.stats);
                    updateEndpointsTable(data.data.endpoints);
                    updateCharts(data.data.endpoints, data.data.stats);
                    updateAuthStatus(data.data.auth_token_status);
                    updateLastUpdated(data.data.last_updated);
                } else {
                    showError('Error al cargar los datos: ' + (data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error en loadApiStatus:', error);
                showError('Error de conexión: ' + error.message);
            } finally {
                isLoading = false;
                refreshBtn.innerHTML = originalText;
                refreshBtn.disabled = false;
            }
        }

        function updateStats(stats) {
            document.getElementById('totalEndpoints').textContent = stats.total_endpoints;
            document.getElementById('activeEndpoints').textContent = stats.active_endpoints;
            document.getElementById('errorEndpoints').textContent = stats.error_endpoints;
            document.getElementById('avgResponseTime').textContent = stats.average_response_time + 'ms';
        }

        function updateEndpointsTable(endpoints) {
            const tbody = document.getElementById('endpointsTableBody');
            
            if (endpoints.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-medium">No hay endpoints para mostrar</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let fastestTime = Infinity;
            let slowestTime = 0;

            endpoints.forEach(endpoint => {
                if (endpoint.response_time > 0) {
                    fastestTime = Math.min(fastestTime, endpoint.response_time);
                    slowestTime = Math.max(slowestTime, endpoint.response_time);
                }
            });

            document.getElementById('fastestTime').textContent = fastestTime === Infinity ? '- ms' : fastestTime + ' ms';
            document.getElementById('slowestTime').textContent = slowestTime === 0 ? '- ms' : slowestTime + ' ms';

            tbody.innerHTML = endpoints.map(endpoint => `
                <tr class="hover:bg-blue-50/50 transition-colors duration-200">
                    <td class="px-6 py-4">
                        <div>
                            <div class="text-sm font-bold text-gray-900">${endpoint.name}</div>
                            <div class="text-xs text-gray-500 font-mono">${endpoint.url}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${getMethodColor(endpoint.method)}">
                            ${endpoint.method}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${getCategoryColor(endpoint.category)}">
                            ${endpoint.category}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${getStatusColor(endpoint.status)}">
                            <span class="w-2 h-2 rounded-full mr-2 ${getStatusDotColor(endpoint.status)}"></span>
                            ${getStatusText(endpoint.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-bold ${endpoint.response_time > 1000 ? 'text-red-600' : endpoint.response_time > 500 ? 'text-yellow-600' : 'text-green-600'}">
                            ${endpoint.response_time}ms
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                        ${endpoint.message}
                    </td>
                </tr>
            `).join('');
        }

        function updateCharts(endpoints, stats) {
            updateStatusChart(stats);
            updateResponseTimeChart(endpoints);
        }

        function updateStatusChart(stats) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            if (statusChart) {
                statusChart.destroy();
            }

            statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Con Errores'],
                    datasets: [{
                        data: [stats.active_endpoints, stats.error_endpoints],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateResponseTimeChart(endpoints) {
            const ctx = document.getElementById('responseTimeChart').getContext('2d');
            
            if (responseTimeChart) {
                responseTimeChart.destroy();
            }

            // Agrupar por categoría
            const categories = {};
            endpoints.forEach(endpoint => {
                if (!categories[endpoint.category]) {
                    categories[endpoint.category] = [];
                }
                categories[endpoint.category].push(endpoint.response_time);
            });

            // Calcular promedio por categoría
            const labels = Object.keys(categories);
            const data = labels.map(category => {
                const times = categories[category];
                return Math.round(times.reduce((a, b) => a + b, 0) / times.length);
            });

            const colors = [
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(236, 72, 153, 0.8)'
            ];

            responseTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tiempo Promedio (ms)',
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderColor: colors.slice(0, labels.length).map(color => color.replace('0.8', '1')),
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateAuthStatus(status) {
            const authStatusElement = document.getElementById('authStatus');
            const statusSpan = authStatusElement.querySelector('span');
            
            if (status === 'active') {
                statusSpan.className = 'px-3 py-1 rounded-full text-xs bg-green-100 text-green-700 font-bold';
                statusSpan.textContent = 'Activo';
            } else {
                statusSpan.className = 'px-3 py-1 rounded-full text-xs bg-red-100 text-red-700 font-bold';
                statusSpan.textContent = 'Error';
            }
        }

        function updateLastUpdated(timestamp) {
            const date = new Date(timestamp);
            const formatted = date.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('lastUpdated').textContent = `Última actualización: ${formatted}`;
        }

        function getMethodColor(method) {
            switch (method) {
                case 'GET': return 'bg-blue-100 text-blue-800';
                case 'POST': return 'bg-green-100 text-green-800';
                case 'PUT': return 'bg-yellow-100 text-yellow-800';
                case 'DELETE': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function getCategoryColor(category) {
            switch (category) {
                case 'Auth': return 'bg-purple-100 text-purple-800';
                case 'Categories': return 'bg-indigo-100 text-indigo-800';
                case 'Accounts': return 'bg-emerald-100 text-emerald-800';
                case 'Transactions': return 'bg-orange-100 text-orange-800';
                case 'Budgets': return 'bg-pink-100 text-pink-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function getStatusColor(status) {
            switch (status) {
                case 'active': return 'bg-green-100 text-green-800';
                case 'requires_auth': return 'bg-blue-100 text-blue-800';
                case 'server_error': return 'bg-red-100 text-red-800';
                case 'not_found': return 'bg-gray-100 text-gray-800';
                case 'method_not_allowed': return 'bg-yellow-100 text-yellow-800';
                default: return 'bg-red-100 text-red-800';
            }
        }

        function getStatusDotColor(status) {
            switch (status) {
                case 'active': return 'bg-green-500 animate-pulse-slow';
                case 'requires_auth': return 'bg-blue-500';
                case 'server_error': return 'bg-red-500 animate-pulse';
                case 'not_found': return 'bg-gray-500';
                case 'method_not_allowed': return 'bg-yellow-500';
                default: return 'bg-red-500';
            }
        }

        function getStatusText(status) {
            switch (status) {
                case 'active': return 'Activo';
                case 'requires_auth': return 'Requiere Auth';
                case 'server_error': return 'Error Servidor';
                case 'not_found': return 'No Encontrado';
                case 'method_not_allowed': return 'Método No Permitido';
                default: return 'Error';
            }
        }

        function showError(message) {
            // Crear notificación de error moderna
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 transform translate-x-full transition-transform duration-300';
            errorDiv.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <div>
                        <p class="font-bold">Error</p>
                        <p class="text-sm opacity-90">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-red-200 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(errorDiv);

            // Animar entrada
            setTimeout(() => {
                errorDiv.classList.remove('translate-x-full');
            }, 100);

            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    errorDiv.classList.add('translate-x-full');
                    setTimeout(() => errorDiv.remove(), 300);
                }
            }, 5000);
        }
        
        async function showDiagnostics() {
            try {
                const baseUrl = window.location.origin;
                const response = await fetch(`${baseUrl}/api-status/diagnostics`);
                const data = await response.json();
                
                // Crear modal de diagnósticos
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
                modal.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-96 overflow-auto">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-gray-800">
                                    <i class="fas fa-diagnoses mr-2 text-blue-600"></i>
                                    Diagnósticos del Sistema
                                </h3>
                                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <pre class="bg-gray-100 p-4 rounded-lg text-sm overflow-auto font-mono">${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Cerrar modal al hacer clic fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
                
            } catch (error) {
                showError('Error al cargar diagnósticos: ' + error.message);
            }
        }
    </script>
</body>
</html>