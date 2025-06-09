# API Finance Dashboard

## Descripción del Proyecto

API Finance Dashboard es una aplicación web desarrollada en Laravel que proporciona una API RESTful completa para la gestión de finanzas personales. El proyecto incluye funcionalidades para el manejo de usuarios, categorías, cuentas, transacciones y presupuestos, junto con un dashboard de monitoreo del estado de la API.

## Características Principales

- **Autenticación segura** con Laravel Sanctum
- **API RESTful** para gestión financiera
- **Dashboard de monitoreo** del estado de la API en tiempo real
- **Gestión de categorías** para organizar transacciones
- **Manejo de cuentas** bancarias y financieras
- **Registro de transacciones** con estadísticas
- **Sistema de presupuestos** con seguimiento
- **Soft deletes** y restauración de registros
- **Validación robusta** de datos

## Tecnologías Utilizadas

- **Laravel 12.x** - Framework PHP
- **Laravel Sanctum** - Autenticación API
- **MySQL/PostgreSQL** - Base de datos
- **Vite** - Build tool para assets
- **Chart.js** - Gráficos en el dashboard
- **Font Awesome** - Iconografía

## Endpoints de la API

### Base URL


### Autenticación

#### Públicos (sin autenticación)
- `POST /auth/register` - Registro de usuario
- `POST /auth/login` - Inicio de sesión

#### Protegidos (requieren token)
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/me` - Información del usuario actual
- `POST /auth/refresh` - Renovar token
- `GET /user` - Datos del usuario autenticado

### Categorías
- `GET /categories` - Listar todas las categorías
- `POST /categories` - Crear nueva categoría
- `GET /categories/{id}` - Obtener categoría específica
- `PUT /categories/{id}` - Actualizar categoría
- `DELETE /categories/{id}` - Eliminar categoría (soft delete)
- `POST /categories/{id}/restore` - Restaurar categoría eliminada
- `POST /categories/{id}/toggle-status` - Cambiar estado de categoría

### Cuentas
- `GET /accounts` - Listar todas las cuentas
- `POST /accounts` - Crear nueva cuenta
- `GET /accounts/{id}` - Obtener cuenta específica
- `PUT /accounts/{id}` - Actualizar cuenta
- `DELETE /accounts/{id}` - Eliminar cuenta (soft delete)
- `POST /accounts/{id}/restore` - Restaurar cuenta eliminada
- `PATCH /accounts/{id}/toggle-status` - Cambiar estado de cuenta
- `GET /accounts/stats` - Estadísticas de cuentas

### Transacciones
- `GET /transactions` - Listar todas las transacciones
- `POST /transactions` - Crear nueva transacción
- `GET /transactions/{id}` - Obtener transacción específica
- `PUT /transactions/{id}` - Actualizar transacción
- `DELETE /transactions/{id}` - Eliminar transacción (soft delete)
- `POST /transactions/{id}/restore` - Restaurar transacción eliminada
- `GET /transactions/stats` - Estadísticas de transacciones

### Presupuestos
- `GET /budgets` - Listar todos los presupuestos
- `POST /budgets` - Crear nuevo presupuesto
- `GET /budgets/{id}` - Obtener presupuesto específico
- `PUT /budgets/{id}` - Actualizar presupuesto
- `DELETE /budgets/{id}` - Eliminar presupuesto (soft delete)
- `POST /budgets/{id}/restore` - Restaurar presupuesto eliminado
- `PATCH /budgets/{id}/toggle-status` - Cambiar estado de presupuesto
- `GET /budgets-active` - Presupuestos activos
- `GET /budgets/current` - Presupuesto actual

### Dashboard de Monitoreo
- `GET /api-status` - Dashboard visual del estado de la API
- `GET /api-status/json` - Estado de la API en formato JSON

## Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd api-finance-dashboard
```
2. **Instalar dependencias**
```bash
composer install
npm install
```
3. **Configurar variables de entorno**
```bash
cp .env.example .env
```
Actualiza las variables de entorno en el archivo `.env` según tus necesidades.
4. **Generar clave de aplicación**
```bash
php artisan key:generate
```
5. **Ejecutar migraciones y seeders**
```bash
php artisan migrate --seed
```
6. **Compilar assets**
```bash
npm run build
```
7. **Iniciar servidor de desarrollo**
```bash
php artisan serve
```
