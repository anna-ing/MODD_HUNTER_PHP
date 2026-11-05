<?php
namespace App\Http\Controllers;

use App\Http\Requests\GuardarSolicitud;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudController extends Controller
{
    public function __construct(
        protected SolicitudService $solicitudService
    ) {}

    /**
     * crea una nueva solicitud.
     */
    public function createRequest(GuardarSolicitud $request): JsonResponse
    {
        try {
            $data = $this->solicitudService->createRequest($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada correctamente.',
                'data'    => $data,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error'   => 'Error al crear el recurso.',
                'detalle' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Retorna lista de solicitudes
     */
    public function listRequests()
    {
        try {
            $data = $this->solicitudService->listRequests();
            return view('solicitudes', compact('data'));
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error'   => 'Error al listar la información.',
                'detalle' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Actualiza informacion de solicitudes
     */
    public function updateRequest(GuardarSolicitud $request, int $id)
    {
        try {
            $data = $this->solicitudService->updateRequest($request->validated(), $id);
            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada correctamente.',
                'data'    => $data,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error'   => 'Error al actualizar la información.',
                'detalle' => $e->getMessage(),
            ], 422);
        }
    }

    public function change_status(int $id, Request $request, ): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        try {
            $solicitud = $this->solicitudService->change_status((int) $id, $request->input('status'));

            return response()->json([
                'mensaje' => 'Transición ejecutada con éxito.',
                'data'    => $solicitud,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error'   => 'Transición inválida.',
                'detalle' => $e->getMessage(),
            ], 422);
        }
    }
}
