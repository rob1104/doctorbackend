<?php

namespace App\AI\Tools;

use App\Models\Appointment;
use Carbon\Carbon;

class CheckAvailabilityTool extends BaseTool
{
    public static function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'check_availability',
                'description' => 'Consulta la disponibilidad de horarios para citas en una fecha específica.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => [
                            'type' => 'string',
                            'description' => 'Fecha en formato YYYY-MM-DD.',
                        ],
                        'min_time' => [
                            'type' => 'string',
                            'description' => 'Hora mínima en formato HH:MM (ej. 16:00) si el paciente prefiere por la tarde. Opcional.',
                        ]
                    ],
                    'required' => ['date'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $date = $arguments['date'];
        $minTime = $arguments['min_time'] ?? null;

        try {
            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return ['error' => 'Formato de fecha inválido. Usa YYYY-MM-DD.'];
        }

        if ($parsedDate->isSunday()) {
            return [
                'available' => false,
                'slots' => [],
                'message' => 'La clínica permanece cerrada los domingos. Por favor selecciona un día de lunes a sábado.'
            ];
        }

        // Obtener citas confirmadas o pendientes para ese día
        $appointments = Appointment::where('appointment_date', $date)
            ->whereIn('status', ['approved', 'pending'])
            ->get();

        $workStart = Carbon::parse($date . ' 09:00:00');
        $workEnd = Carbon::parse($date . ' 17:00:00');
        $slotDuration = 30; // Minutos

        $availableSlots = [];
        $currentSlot = $workStart->copy();
        
        $cutoffTime = Carbon::now()->addHour();

        while ($currentSlot < $workEnd) {
            $slotString = $currentSlot->format('H:i');

            if ($parsedDate->isToday() && $currentSlot < $cutoffTime) {
                $currentSlot->addMinutes($slotDuration);
                continue;
            }

            if ($minTime && $slotString < $minTime) {
                $currentSlot->addMinutes($slotDuration);
                continue;
            }

            $isBooked = $appointments->some(function ($app) use ($slotString) {
                return Carbon::parse($app->start_time)->format('H:i') === $slotString;
            });

            if (!$isBooked) {
                $availableSlots[] = [
                    'date' => $date,
                    'time' => $slotString,
                ];
            }

            $currentSlot->addMinutes($slotDuration);
        }

        return [
            'available' => count($availableSlots) > 0,
            'slots' => array_slice($availableSlots, 0, 5), // Regresar los próximos 5 para no saturar al LLM
            'message' => count($availableSlots) > 0 ? 'Horarios encontrados.' : 'No hay horarios disponibles para esta fecha.',
        ];
    }
}
