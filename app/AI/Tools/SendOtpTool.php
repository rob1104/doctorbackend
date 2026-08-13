<?php

namespace App\AI\Tools;

use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SendOtpTool extends BaseTool
{
    public static function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'send_otp',
                'description' => 'Envía un código de verificación (OTP) por WhatsApp al teléfono proporcionado para verificar la identidad antes de agendar.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'phone' => [
                            'type' => 'string',
                            'description' => 'Teléfono a donde se enviará el OTP (10 dígitos).',
                        ],
                    ],
                    'required' => ['phone'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $phone = $arguments['phone'] ?? null;

        if (!$phone) {
            return ['error' => 'Se requiere el número de teléfono.'];
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        try {
            $botUrl = config('services.whatsapp.bot_url');
            $response = Http::post("{$botUrl}/api/send-message", [
                'number' => $phone,
                'message' => "Hola, tu código de verificación para agendar tu cita a través del Asistente Virtual es: *{$code}*. \nExpira en 10 minutos."
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Código enviado correctamente. Pídele al usuario que introduzca el código de 6 dígitos que recibió.'
                ];
            } else {
                return [
                    'error' => 'Error al enviar el mensaje de WhatsApp. Indica al usuario que intente más tarde.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => 'No se pudo conectar con el servicio de mensajería. Indica al usuario que hay problemas técnicos.'
            ];
        }
    }
}
