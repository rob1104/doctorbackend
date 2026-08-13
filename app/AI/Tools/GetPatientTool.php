<?php

namespace App\AI\Tools;

class GetPatientTool extends BaseTool
{
    public static function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'set_patient_data',
                'description' => 'Guarda los datos del paciente (nombre, apellido y teléfono) en el contexto cuando el paciente quiere agendar una cita pero no está autenticado.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'first_name' => [
                            'type' => 'string',
                            'description' => 'Nombre del paciente.',
                        ],
                        'last_name' => [
                            'type' => 'string',
                            'description' => 'Apellido del paciente.',
                        ],
                        'phone' => [
                            'type' => 'string',
                            'description' => 'Teléfono del paciente (10 dígitos).',
                        ],
                    ],
                    'required' => ['first_name', 'last_name', 'phone'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        // En el flujo real, el Agente guardará esto en el contexto de la conversación.
        // Aquí solo regresamos éxito para que el LLM sepa que se guardaron los datos.
        
        return [
            'success' => true,
            'message' => 'Datos del paciente guardados temporalmente. Ahora debes pedir que envíen el código OTP para verificar el teléfono usando send_otp.',
            'patient_data' => $arguments
        ];
    }
}
