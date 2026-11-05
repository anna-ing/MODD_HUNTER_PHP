<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;
use Throwable;

class LlmService
{
    public function generarResumen(string $titulo, string $descripcion): string
    {
        try {
            $prompt = "
                Eres un asistente especializado en gestión de solicitudes.

                Analiza la siguiente solicitud y genera un resumen
                claro, breve y profesional, máximo 50 palabras.

                Título: {$titulo}

                Descripción: {$descripcion}

                Devuelve únicamente el resumen.
            ";

            $response = Gemini::generativeModel(
                model: env('GEMINI_MODEL')
            )->generateContent($prompt);

            return trim($response->text());

        } catch (Throwable $e) {

            Log::error(
                'Error generando resumen con Gemini: ' . $e->getMessage()
            );

            return '';
        }
    }
}
