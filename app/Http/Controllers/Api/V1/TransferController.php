<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\StoreTransferRequest;
use App\Http\Resources\Transfer\TransferResource;
use App\Services\Transfer\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private TransferService $transferService
    ) {}

    /**
     * Display a listing of transfers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $transfers = $this->transferService->getUserTransfers(
                $request->get('start_date'),
                $request->get('end_date'),
                $request->get('sort_by', 'transaction_date'),
                $request->get('sort_direction', 'desc'),
                $request->get('per_page', 15)
            );

            return response()->json([
                'success' => true,
                'data' => TransferResource::collection($transfers),
                'message' => 'Transferencias obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las transferencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created transfer.
     */
    public function store(StoreTransferRequest $request): JsonResponse
    {
        try {
            $transfer = $this->transferService->createTransfer($request->validated());

            return response()->json([
                'success' => true,
                'data' => new TransferResource($transfer),
                'message' => 'Transferencia realizada exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transfer.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->getTransfer($id);

            return response()->json([
                'success' => true,
                'data' => new TransferResource($transfer),
                'message' => 'Transferencia obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transferencia no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified transfer.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->transferService->deleteTransfer($id);

            return response()->json([
                'success' => true,
                'message' => 'Transferencia eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->transferService->getTransferStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de transferencias obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available accounts for transfers.
     */
    public function accounts(): JsonResponse
    {
        try {
            // Get user's active accounts
            $accounts = auth()->user()->accounts()
                ->where('is_active', true)
                ->select('id', 'name', 'type', 'current_balance', 'currency')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accounts,
                'message' => 'Cuentas disponibles obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cuentas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}