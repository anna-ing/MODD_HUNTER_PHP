<?php

namespace App\Enums;

enum EstadosSolicitud: string
{
    case CREATED = 'created';
    case IN_REVIEW = 'in_review';
    case IN_PROGRESS = 'in_progress';
    case PAUSED = 'paused';
    case DONE = 'done';

    /**
     * Transiciones permitidas desde el estado actual.
     * Retorna un array con los strings de los estados destino válidos.
     */
    public function transicionesPermitidas(): array
    {
        return match($this) {
            // Secuencia principal: created -> in_review
            self::CREATED => [self::IN_REVIEW->value],

            // Secuencia principal: in_review -> in_progress (Opcional: ir a paused)
            self::IN_REVIEW => [self::IN_PROGRESS->value, self::PAUSED->value],

            // Secuencia principal: in_progress -> done (Opcional: ir a paused)
            self::IN_PROGRESS => [self::DONE->value, self::PAUSED->value],

            // Desde paused SOLO puede volver a in_progress o in_review
            self::PAUSED => [self::IN_PROGRESS->value, self::IN_REVIEW->value],

            // Estado final. No tiene salidas.
            self::DONE => [],
        };
    }

    /**
     * Valida si la transición solicitada es lícita.
     */
    public function puedeTransicionarA(string $nuevoEstado): bool
    {
        return in_array($nuevoEstado, $this->transicionesPermitidas());
    }
}
