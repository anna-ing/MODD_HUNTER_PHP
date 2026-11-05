<?php
namespace App\Services;

use App\Enums\EstadosSolicitud;
use App\Models\Solicitud;
use App\Services\LlmService;
use Illuminate\Support\Facades\Log;

class SolicitudService
{
    public function __construct(protected LlmService $llmService)
    {}
    /**
     * end point para listar las solicitudes.
     * @author:  Ana Huertas
     * @var array data
     * Fecha creación:  05/11/2026
     */
    public function createRequest(array $data): Solicitud
    {
        //cada soliciud nueva se crea con estado created.
        $data['status'] = EstadosSolicitud::CREATED->value;
        try {
            $data['llm_summary'] = $this->llmService->generarResumen(
                $data['title'],
                $data['description']
            );
        } catch (\Exception $e) {
            // Si gemini falla, la solicitud se guarda sin resumen.
            $data['llm_summary'] = null;

            Log::error('Error generando resumen con LLM: ' . $e->getMessage());
        }
        return Solicitud::create($data);
    }
    /**
     * end point para listar las solicitudes.
     * @author:  Ana Huertas
     * Fecha creación:  05/11/2026
     */
    public function listRequests(): \Illuminate\Database\Eloquent\Collection
    {
        $data = Solicitud::all();
        return $data;
    }
    /**
     * end point para actualizar titulo y descripcion de las solicitudes.
     * @author:  Ana Huertas
     *  @var array data , @var int id
     * Fecha creación:  05/11/2026
     */
    public function updateRequest(array $data, int $id): Solicitud
    {
        $solicitud = Solicitud::findOrFail($id);
        $solicitud->update($data);
        return $solicitud;
    }
    /**
     * end point para cambiar de estado una solicitud teniendo en cuenta las reglas de tansicion de estados.
     * @author:  Ana Huertas
     *  @var string nuevoEstado , @var int id
     * Fecha creación:  05/11/2026
     */
    public function change_status(int $id, string $nuevoEstado): Solicitud
    {
        $solicitud = Solicitud::findOrFail($id);

        // Validar si el cambio de estado es legal usando el Enum
        if (! $solicitud->status->puedeTransicionarA($nuevoEstado)) {
            throw new \InvalidArgumentException(
                "No es posible pasar del estado [{$solicitud->status->value}] al estado [{$nuevoEstado}]."
            );
        }

        $solicitud->update(['status' => $nuevoEstado]);

        return $solicitud;
    }
}
