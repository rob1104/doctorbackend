<?php

namespace App\AI\Tools;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\OtpVerification;
use App\Events\AppointmentCreated;
use Carbon\Carbon;

class CreateAppointmentTool extends BaseTool
{
    public static function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_appointment',
                'description' => 'Crea y confirma una cita médica. Debe invocarse ÚNICAMENTE cuando el paciente haya confirmado explícitamente el horario y, si se le solicitó, haya proporcionado el código OTP.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => [
                            'type' => 'string',
                            'description' => 'Fecha de la cita (YYYY-MM-DD).',
                        ],
                        'time' => [
                            'type' => 'string',
                            'description' => 'Hora de la cita (HH:MM).',
                        ],
                        'first_name' => [
                            'type' => 'string',
                            'description' => 'Nombre del paciente (requerido si no está autenticado).',
                        ],
                        'last_name' => [
                            'type' => 'string',
                            'description' => 'Apellido del paciente (requerido si no está autenticado).',
                        ],
                        'phone' => [
                            'type' => 'string',
                            'description' => 'Teléfono del paciente (requerido si no está autenticado).',
                        ],
                        'otp_code' => [
                            'type' => 'string',
                            'description' => 'Código OTP de 6 dígitos proporcionado por el paciente.',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['clinico', 'estetico'],
                            'description' => 'Tipo de consulta. Por defecto "clinico".',
                        ],
                        'patient_id' => [
                            'type' => 'integer',
                            'description' => 'ID del paciente si está autenticado. Opcional.',
                        ],
                    ],
                    'required' => ['date', 'time'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $date = $arguments['date'];
        $time = $arguments['time'];
        $patientId = $arguments['patient_id'] ?? null;
        $otpCode = $arguments['otp_code'] ?? null;
        $phone = $arguments['phone'] ?? null;
        $type = $arguments['type'] ?? 'clinico';

        // 1. Validar OTP si no hay patient_id
        if (!$patientId) {
            if (!$phone || !$otpCode) {
                return ['error' => 'Para agendar sin sesión iniciada, se requiere teléfono y código OTP.'];
            }

            $otp = OtpVerification::where('phone', $phone)
                ->where('code', $otpCode)
                ->where('is_verified', false)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$otp) {
                return ['error' => 'El código OTP es inválido o ha expirado. Por favor, solicita uno nuevo o verifica el código.'];
            }

            // Marcar OTP verificado
            $otp->is_verified = true;
            $otp->save();

            // Buscar o crear paciente
            $patient = Patient::firstOrCreate(
                ['phone' => $phone],
                [
                    'first_name' => $arguments['first_name'] ?? 'Paciente',
                    'last_name' => $arguments['last_name'] ?? 'AI',
                    'is_patient' => false,
                ]
            );
            $patientId = $patient->id;
        }

        // 2. Verificar empalmes (Doble Reserva)
        $isBooked = Appointment::where('appointment_date', $date)
            ->where('start_time', Carbon::parse($time)->format('H:i:s'))
            ->whereIn('status', ['approved', 'pending'])
            ->exists();

        if ($isBooked) {
            return [
                'error' => 'conflict',
                'message' => 'Lo sentimos, este horario acaba de ser ocupado. Por favor, consulta disponibilidad para buscar otro horario.'
            ];
        }

        // 3. Crear la Cita
        $endTime = Carbon::parse($time)->addMinutes(30)->format('H:i');

        $appointment = Appointment::create([
            'patient_id' => $patientId,
            'type' => $type,
            'appointment_date' => $date,
            'start_time' => $time,
            'end_time' => $endTime,
            'status' => 'pending', // Requiere confirmación manual del asistente
            'notes' => 'Agendada vía Asistente Virtual AI',
        ]);

        // 4. Notificaciones
        try {
            \App\Models\AdminNotification::create([
                'type' => 'appointment',
                'data' => $appointment->load('patient')->toArray(),
            ]);
            AppointmentCreated::dispatch($appointment);
        } catch (\Exception $e) {
            // Ignorar errores de notificaciones
        }

        return [
            'success' => true,
            'message' => 'Cita creada exitosamente.',
            'appointment' => [
                'id' => $appointment->id,
                'date' => $date,
                'time' => $time,
            ]
        ];
    }
}
