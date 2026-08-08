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
        // Si el número entra como 521XXXXXXXXXX, lo normalizamos a 10 dígitos (México) para compararlo
        // Esto depende de cómo lo guarda el doctor, pero asumamos que se compara directo con la DB
        // En Node.js le quitamos el @c.us

        $msg = WhatsAppMessage::create([
            'phone' => $phone,
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
        // Normalizar teléfono
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

        $phone = preg_replace('/\D/', '', $request->phone);
        
        // Asegurar que tenga el código de país (521 para México por defecto si son 10 dígitos)
        if (strlen($phone) === 10) {
            $phone = '521' . $phone;
        }

        // Llamamos al bot
        try {
            $botUrl = config('services.whatsapp.bot_url');
            $response = Http::post("{$botUrl}/api/send-message", [
                'number' => $phone,
                'message' => $request->message
            ]);

            if ($response->successful()) {
                $msg = WhatsAppMessage::create([
                    'phone' => $phone,
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
