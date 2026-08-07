<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Events\AppointmentCreated;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    // Obtener días y horas disponibles (mockup o lógica real de empalmes)
    public function availability(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        // Solo las citas confirmadas bloquean el horario
        $appointments = Appointment::where('appointment_date', $date)
            ->where('status', 'approved')
            ->get();

        // Horario laboral de ejemplo: 09:00 a 17:00
        $workStart = Carbon::createFromTimeString('09:00');
        $workEnd = Carbon::createFromTimeString('17:00');
        $slotDuration = 30; // Minutos por cita

        $availableSlots = [];
        $currentSlot = $workStart->copy();

        while ($currentSlot < $workEnd) {
            $slotString = $currentSlot->format('H:i');

            // Checar si existe alguna cita que empalme
            $isBooked = $appointments->some(function ($app) use ($slotString) {
                // Lógica simple: Si la hora de inicio de la cita coincide con el slot
                return Carbon::parse($app->start_time)->format('H:i') === $slotString;
            });

            if (!$isBooked) {
                $availableSlots[] = $slotString;
            }

            $currentSlot->addMinutes($slotDuration);
        }

        return response()->json([
            'date' => $date,
            'available_slots' => $availableSlots
        ]);
    }

    // Registrar nueva cita con OTP
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:clinico,estetico',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
            'otp_code' => 'required|string|size:6'
        ]);

        // Verificar si la hora ya fue confirmada para otro paciente
        $isBooked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('start_time', \Carbon\Carbon::parse($request->start_time)->format('H:i:s'))
            ->where('status', 'approved')
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => 'Lo sentimos, este horario acaba de ser reservado. Por favor selecciona otro.'
            ], 422);
        }

        // Verificar OTP
        $otp = \App\Models\OtpVerification::where('phone', $request->phone)
            ->where('code', $request->otp_code)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return response()->json(['error' => 'Código OTP inválido o expirado.'], 400);
        }

        // Marcar OTP como verificado
        $otp->is_verified = true;
        $otp->save();

        // Registrar o actualizar paciente
        $patient = Patient::firstOrCreate(
            ['phone' => $request->phone],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
            ]
        );

        // Calcular hora de fin
        $endTime = Carbon::parse($request->start_time)->addMinutes(30)->format('H:i');

        // Registrar cita
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'type' => $request->type,
            'appointment_date' => $request->appointment_date,
            'start_time' => $request->start_time,
            'end_time' => $endTime,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // Emitir evento Reverb
        AppointmentCreated::dispatch($appointment);

        return response()->json([
            'message' => 'Cita agendada correctamente',
            'appointment' => $appointment->load('patient')
        ], 201);
    }

    // Registrar nueva cita directamente desde el admin
    public function adminStore(Request $request)
    {
        $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'first_name' => 'required_without:patient_id|string|max:255',
            'last_name' => 'required_without:patient_id|string|max:255',
            'phone' => 'required_without:patient_id|string|max:20',
            'type' => 'required|in:clinico,estetico',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $patientId = $request->patient_id;

        // Si no enviaron patient_id, creamos un prospecto al vuelo
        if (!$patientId) {
            // Checar si ya existe alguien con ese teléfono para evitar duplicidad
            $existingPatient = \App\Models\Patient::where('phone', $request->phone)->first();
            if ($existingPatient) {
                $patientId = $existingPatient->id;
            } else {
                $newProspect = \App\Models\Patient::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'is_patient' => false,
                ]);
                $patientId = $newProspect->id;
            }
        }

        // Verificar si la hora ya fue confirmada
        $isBooked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('start_time', \Carbon\Carbon::parse($request->start_time)->format('H:i:s'))
            ->where('status', 'approved')
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => 'Lo sentimos, este horario ya está ocupado.'
            ], 422);
        }

        $endTime = \Carbon\Carbon::parse($request->start_time)->addMinutes(30)->format('H:i');

        $appointment = Appointment::create([
            'patient_id' => $patientId,
            'type' => $request->type,
            'appointment_date' => $request->appointment_date,
            'start_time' => $request->start_time,
            'end_time' => $endTime,
            'status' => 'approved', // Aprobada por defecto
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Cita agendada y aprobada correctamente',
            'appointment' => $appointment->load('patient')
        ], 201);
    }

    // Enviar código OTP vía Bot de WhatsApp Node.js
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        \App\Models\OtpVerification::create([
            'phone' => $request->phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        // Llamar al microservicio de Node.js
        try {
            $botUrl = config('services.whatsapp.bot_url');
            $response = \Illuminate\Support\Facades\Http::post("{$botUrl}/api/send-message", [
                'number' => $request->phone,
                'message' => "Hola, tu código de verificación para agendar la cita con el Dr. Sobrevilla es: *{$code}*. \nExpira en 10 minutos."
            ]);

            if ($response->successful()) {
                return response()->json(['message' => 'Código enviado correctamente por WhatsApp.']);
            } else {
                // If the bot returns 503 (QR not scanned), forward the message
                return response()->json(['error' => 'Error del Bot WhatsApp: ' . $response->json('error', 'Desconocido')], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo conectar con el microservicio de WhatsApp.', 'details' => $e->getMessage(), 'url' => config('services.whatsapp.bot_url')], 500);
        }
    }

    // Obtener listado de citas para el Dashboard
    public function index(Request $request)
    {
        // Ordenar de mayor a menor (más recientes primero)
        $appointments = Appointment::with('patient')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($appointments);
    }

    // Cambiar estado de la cita
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,canceled'
        ]);

        $appointment = Appointment::with('patient')->findOrFail($id);

        if ($request->status === 'approved') {
            // Verificar que no haya otra cita aprobada en esa misma hora
            $isBooked = Appointment::where('appointment_date', $appointment->appointment_date)
                ->where('start_time', $appointment->start_time)
                ->where('status', 'approved')
                ->where('id', '!=', $appointment->id)
                ->exists();

            if ($isBooked) {
                return response()->json([
                    'message' => 'Ya existe otra cita confirmada para este horario.'
                ], 422);
            }
        }

        $appointment->status = $request->status;
        $appointment->save();

        // Enviar mensaje automático si es cancelada
        if ($request->status === 'canceled') {
            try {
                \Carbon\Carbon::setLocale('es');
                $dateStr = \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('l d \d\e F');
                $timeStr = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');

                $msgBody = "Hola {$appointment->patient->first_name}, lamentablemente su cita del {$dateStr} a las {$timeStr} ha sido cancelada.";

                if ($request->has('cancelation_reason') && !empty($request->cancelation_reason)) {
                    $msgBody .= "\n\nMotivo: " . $request->cancelation_reason;
                }

                $msgBody .= "\n\nLamentablemente no es posible atenderle en este momento. Por favor intente reagendando una nueva cita desde nuestro portal web. ¡Gracias por su comprensión!";

                $botUrl = config('services.whatsapp.bot_url');
                \Illuminate\Support\Facades\Http::post("{$botUrl}/api/send-message", [
                    'number' => $appointment->patient->phone,
                    'message' => $msgBody
                ]);

                // Guardar el mensaje en el historial
                $savedMsg = \App\Models\WhatsAppMessage::create([
                    'phone' => $appointment->patient->phone,
                    'message' => $msgBody,
                    'is_from_patient' => false
                ]);

                // Emitir evento para que aparezca en el chat en vivo si está abierto
                \App\Events\WhatsAppMessageReceived::dispatch($savedMsg);
            } catch (\Exception $e) {
            }
        } elseif ($request->status === 'approved') {
            try {
                \Carbon\Carbon::setLocale('es');
                $dateStr = \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('l d \d\e F');
                $timeStr = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');

                $msgBody = "Hola {$appointment->patient->first_name}, ¡su cita ha sido confirmada!\n\nTe esperamos el {$dateStr} a las {$timeStr}. ¡Nos vemos pronto!";

                $botUrl = config('services.whatsapp.bot_url');

                \Illuminate\Support\Facades\Http::post("{$botUrl}/api/send-message", [
                    'number' => $appointment->patient->phone,
                    'message' => $msgBody
                ]);

                // Guardar el mensaje en el historial
                $savedMsg = \App\Models\WhatsAppMessage::create([
                    'phone' => $appointment->patient->phone,
                    'message' => $msgBody,
                    'is_from_patient' => false
                ]);

                // Emitir evento para que aparezca en el chat en vivo si está abierto
                \App\Events\WhatsAppMessageReceived::dispatch($savedMsg);
            } catch (\Exception $e) {
            }
        }

        return response()->json([
            'message' => 'Estado actualizado',
            'appointment' => $appointment->load('patient')
        ]);
    }
}
