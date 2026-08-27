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
        $parsedDate = Carbon::parse($date);
        
        $settings = \App\Models\AgendaSetting::getSettings();
        $workingDays = $settings->working_days ?? []; // Array of days, e.g. [1,2,3,4,5]

        // Carbon dayOfWeek: 0 = Sunday, 1 = Monday, etc.
        if (!in_array($parsedDate->dayOfWeek, $workingDays)) {
            return response()->json([
                'date' => $date,
                'available_slots' => []
            ]);
        }

        // Las citas confirmadas y pendientes bloquean el horario
        $appointments = Appointment::where('appointment_date', $date)
            ->whereIn('status', ['approved', 'pending'])
            ->get();

        // Bloqueos manuales
        $blockedSlots = \App\Models\BlockedTimeSlot::where('date', $date)->get();

        // Horario laboral desde configuración
        $workStart = Carbon::createFromTimeString($settings->start_time);
        $workEnd = Carbon::createFromTimeString($settings->end_time);
        $slotDuration = $settings->slot_duration; // Minutos por cita
        
        $hasBreak = $settings->break_start_time && $settings->break_end_time;
        if ($hasBreak) {
            $breakStart = Carbon::createFromTimeString($settings->break_start_time);
            $breakEnd = Carbon::createFromTimeString($settings->break_end_time);
        }

        $availableSlots = [];
        $currentSlot = $workStart->copy();
        
        $cutoffTime = Carbon::now()->addHour();

        while ($currentSlot < $workEnd) {
            $slotString = $currentSlot->format('H:i');
            $slotEnd = $currentSlot->copy()->addMinutes($slotDuration);

            // Si es la hora de descanso, omitir (si el slot choca con el descanso)
            if ($hasBreak) {
                // Checar si el currentSlot cae dentro del break
                if ($currentSlot >= $breakStart && $currentSlot < $breakEnd) {
                    $currentSlot->addMinutes($slotDuration);
                    continue;
                }
            }

            // Si es hoy, ignorar las horas pasadas y la siguiente hora
            if ($parsedDate->isToday() && $currentSlot < $cutoffTime) {
                $currentSlot->addMinutes($slotDuration);
                continue;
            }

            // Checar si existe alguna cita que empalme
            $isBooked = $appointments->some(function ($app) use ($slotString) {
                // Lógica simple: Si la hora de inicio de la cita coincide con el slot
                return Carbon::parse($app->start_time)->format('H:i') === $slotString;
            });

            // Checar si el slot está bloqueado manual/reagendamiento
            $isBlocked = $blockedSlots->some(function ($block) use ($slotString) {
                return Carbon::parse($block->start_time)->format('H:i') === $slotString;
            });

            if (!$isBooked && !$isBlocked) {
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

        // Verificar si la hora ya fue confirmada o está pendiente para otro paciente
        $isBooked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('start_time', \Carbon\Carbon::parse($request->start_time)->format('H:i:s'))
            ->whereIn('status', ['approved', 'pending'])
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

        // Guardar notificacion en BD
        \App\Models\AdminNotification::create([
            'type' => 'appointment',
            'data' => $appointment->load('patient')->toArray(),
        ]);

        // Enviar WhatsApp al paciente notificando que está en revisión
        try {
            $botUrl = config('services.whatsapp.bot_url');
            \Carbon\Carbon::setLocale('es');
            $dateStr = \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('l d \d\e F');
            $timeStr = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');
            
            $msgBody = "Hola {$patient->first_name}, hemos recibido tu solicitud de cita para el {$dateStr} a las {$timeStr}.\n\nTu cita se encuentra *en revisión*. Te notificaremos por este medio en cuanto sea confirmada. ¡Gracias!";

            \Illuminate\Support\Facades\Http::post("{$botUrl}/api/send-message", [
                'number' => $patient->phone,
                'message' => $msgBody
            ]);

            \App\Models\WhatsAppMessage::create([
                'patient_id' => $patient->id,
                'message' => $msgBody,
                'direction' => 'outbound',
                'status' => 'sent'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando mensaje de revisión: ' . $e->getMessage());
        }

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

        // Verificar si la hora ya fue confirmada o está pendiente
        $isBooked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('start_time', \Carbon\Carbon::parse($request->start_time)->format('H:i:s'))
            ->whereIn('status', ['approved', 'pending'])
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
            // Verificar que no haya otra cita aprobada o pendiente en esa misma hora
            $isBooked = Appointment::where('appointment_date', $appointment->appointment_date)
                ->where('start_time', $appointment->start_time)
                ->whereIn('status', ['approved', 'pending'])
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

                $msgBody = "Hola {$appointment->patient->first_name}, lamentablemente su cita del {$dateStr} a las {$timeStr} tuvo que ser reagendada.";

                if ($request->has('cancelation_reason') && !empty($request->cancelation_reason)) {
                    $msgBody .= "\n\nMotivo: " . $request->cancelation_reason;
                }

                $msgBody .= "\n\nPor favor, selecciona un nuevo horario en este enlace: https://doctor.xiserp.mx/#/\n¡Gracias por tu comprensión!";

                // Bloquear el slot permanentemente
                \App\Models\BlockedTimeSlot::firstOrCreate([
                    'date' => $appointment->appointment_date,
                    'start_time' => $appointment->start_time,
                ], [
                    'end_time' => \Carbon\Carbon::parse($appointment->start_time)->addMinutes(30)->format('H:i:s'),
                    'reason' => 'Cita reagendada de ' . $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
                ]);

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

        \App\Events\AppointmentUpdated::dispatch($appointment);

        return response()->json([
            'message' => 'Estado actualizado',
            'appointment' => $appointment->load('patient')
        ]);
    }

    public function getBlockedSlots(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $blockedSlots = \App\Models\BlockedTimeSlot::where('date', $date)->get();
        return response()->json($blockedSlots);
    }

    public function toggleBlockedSlot(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'is_blocked' => 'required|boolean'
        ]);

        if ($request->is_blocked) {
            $slot = \App\Models\BlockedTimeSlot::firstOrCreate([
                'date' => $request->date,
                'start_time' => $request->start_time,
            ], [
                'end_time' => \Carbon\Carbon::parse($request->start_time)->addMinutes(30)->format('H:i:s'),
                'reason' => 'Bloqueo manual',
            ]);
        } else {
            \App\Models\BlockedTimeSlot::where('date', $request->date)
                ->where('start_time', $request->start_time)
                ->delete();
        }

        return response()->json(['message' => 'Disponibilidad actualizada']);
    }

    public function toggleAllBlockedSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'is_blocked' => 'required|boolean',
            'times' => 'nullable|array'
        ]);

        if (!$request->is_blocked) {
            \App\Models\BlockedTimeSlot::where('date', $request->date)->delete();
        } else {
            if ($request->has('times')) {
                foreach ($request->times as $time) {
                    \App\Models\BlockedTimeSlot::firstOrCreate([
                        'date' => $request->date,
                        'start_time' => $time,
                    ], [
                        'end_time' => \Carbon\Carbon::parse($time)->addMinutes(30)->format('H:i:s'),
                        'reason' => 'Bloqueo manual masivo',
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Disponibilidad masiva actualizada']);
    }
}
