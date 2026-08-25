<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppMessage;
use App\Events\WhatsAppMessageReceived;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    // Webhook que recibe mensajes desde el Bot Node.js
    public function webhook(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $request->phone);
        // Estandarizar a formato E.164 (+) para guardar en BD
        if (str_starts_with($phone, '521') && strlen($phone) == 13) {
            $normalizedPhone = '+' . substr($phone, 0, 2) . substr($phone, 3); // quitar el 1
        } elseif (str_starts_with($phone, '52') && strlen($phone) >= 12) {
            $normalizedPhone = '+' . $phone;
        } elseif (str_starts_with($phone, '1') && strlen($phone) == 11) {
            $normalizedPhone = '+' . $phone;
        } else {
            // legacy fallback
            $normalizedPhone = '+' . $phone;
        }

        $msg = WhatsAppMessage::create([
            'phone' => $normalizedPhone,
            'message' => $request->message,
            'is_from_patient' => true
        ]);

        // Guardar notificación si es del paciente
        if ($msg->is_from_patient) {
            $searchPhone = substr($phone, -10);
            $patient = \App\Models\Patient::where('phone', 'LIKE', "%{$searchPhone}%")->first();
            $data = $msg->toArray();
            if ($patient) {
                $data['patient_name'] = $patient->first_name . ' ' . $patient->last_name;
            } else {
                $data['patient_name'] = 'Desconocido';
            }

            \App\Models\AdminNotification::create([
                'type' => 'whatsapp',
                'data' => $data,
            ]);
        }

        // Emitimos el evento a Reverb para actualizar el Dashboard
        WhatsAppMessageReceived::dispatch($msg);

        return response()->json(['status' => 'success']);
    }

    // Obtener historial de mensajes de un número
    public function history($phone)
    {
        // Normalizar teléfono buscando los últimos 10 dígitos (seguro para US y MX)
        $phone = preg_replace('/\D/', '', $phone);
        $searchPhone = substr($phone, -10);

        $messages = WhatsAppMessage::where('phone', 'LIKE', "%{$searchPhone}%")
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Enviar mensaje manual desde el Dashboard
    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        // Guardar el formato normalizado para la BD local (ej. +521234567890 o +11234567890)
        // El bot ya maneja la conversión a formato baileys (521... / 1...)
        $normalizedPhone = $request->phone;
        if (!str_starts_with($normalizedPhone, '+')) {
            $clean = preg_replace('/\D/', '', $normalizedPhone);
            if (strlen($clean) == 10) {
                $normalizedPhone = '+52' . $clean;
            }
        }

        // Llamamos al bot pasándole el número (el bot quita el + y convierte a formato Baileys)
        try {
            $botUrl = config('services.whatsapp.bot_url');
            $response = Http::post("{$botUrl}/api/send-message", [
                'number' => preg_replace('/\D/', '', $normalizedPhone), // El bot espera digitos
                'message' => $request->message
            ]);

            if ($response->successful()) {
                $msg = WhatsAppMessage::create([
                    'phone' => $normalizedPhone,
                    'message' => $request->message,
                    'is_from_patient' => false
                ]);

                // Emitimos el evento a nosotros mismos para mantener sincrónica si se requiere
                WhatsAppMessageReceived::dispatch($msg);

                return response()->json(['status' => 'success', 'message' => $msg]);
            }

            return response()->json(['error' => 'No se pudo enviar el mensaje desde el bot.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fallo la conexión con el bot de Node.', 'details' => $e->getMessage(), 'url' => config('services.whatsapp.bot_url')], 500);
        }
    }
}
