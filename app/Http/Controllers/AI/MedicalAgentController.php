<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiConversation;
use App\AI\Services\OpenAIService;
use App\AI\Agents\MedicalAppointmentAgent;
use Illuminate\Support\Str;

class MedicalAgentController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'conversation_id' => 'nullable|string',
            'message' => 'required|string|max:1000',
        ]);

        $conversationId = $request->input('conversation_id');
        $user = auth('sanctum')->user();
        
        $patientId = null;
        if ($user && $user->patient) {
            $patientId = $user->patient->id;
        }

        if (!$conversationId) {
            $conversation = AiConversation::create([
                'id' => Str::uuid()->toString(),
                'patient_id' => $patientId,
                'session_id' => session()->getId(),
                'messages' => [],
                'status' => 'active',
            ]);
            $conversationId = $conversation->id;
        } else {
            $conversation = AiConversation::find($conversationId);
            
            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }
        }

        $aiService = new OpenAIService();
        
        $agent = new MedicalAppointmentAgent($aiService, $conversation);
        
        $response = $agent->processMessage($request->input('message'), $user ? $user->id : null);

        return response()->json([
            'conversation_id' => $conversationId,
            'message' => $response['message'],
            'action' => $response['action'],
            'requires_confirmation' => $response['requires_confirmation'],
            'appointment' => $response['appointment'] ?? null
        ]);
    }
}
