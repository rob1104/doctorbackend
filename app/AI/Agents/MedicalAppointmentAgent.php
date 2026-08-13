<?php

namespace App\AI\Agents;

use App\Models\AiConversation;
use App\Models\AiAgentLog;
use App\AI\Contracts\AIServiceInterface;
use App\AI\Tools\CheckAvailabilityTool;
use App\AI\Tools\GetPatientTool;
use App\AI\Tools\SendOtpTool;
use App\AI\Tools\CreateAppointmentTool;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MedicalAppointmentAgent
{
    protected AIServiceInterface $aiService;
    protected AiConversation $conversation;
    protected array $tools = [];

    public function __construct(AIServiceInterface $aiService, AiConversation $conversation)
    {
        $this->aiService = $aiService;
        $this->conversation = $conversation;

        $this->tools = [
            new CheckAvailabilityTool(),
            new GetPatientTool(),
            new SendOtpTool(),
            new CreateAppointmentTool(),
        ];
    }

    protected function getSystemPrompt(): string
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i');
        
        return <<<PROMPT
Eres el asistente virtual de la clínica médica del Dr. Sobrevilla.
Tu función es ayudar a los pacientes a consultar y gestionar sus citas.

Información de contexto:
- Fecha actual: {$currentDate}
- Hora actual: {$currentTime}

Reglas estrictas:
1. Puedes consultar disponibilidad y crear citas utilizando exclusivamente las herramientas proporcionadas.
2. NUNCA inventes horarios. Si un usuario pide una fecha, SIEMPRE usa la herramienta check_availability.
3. Si el paciente no está autenticado, para agendar una cita PRIMERO debes pedirle su nombre, apellido y teléfono y usar la herramienta set_patient_data.
4. Luego de tener sus datos, debes enviarle un código OTP usando send_otp.
5. Una vez que el paciente te dé el código OTP de 6 dígitos que recibió por WhatsApp, y te confirme explícitamente el horario, utiliza create_appointment.
6. Nunca confirmes una cita como creada hasta que la herramienta create_appointment haya respondido exitosamente.
7. Nunca crees una cita sin confirmación explícita del paciente.
8. Si el horario dejó de estar disponible al intentar crearlo, informa al paciente y busca otra alternativa.
9. Utiliza siempre fechas absolutas (YYYY-MM-DD) internamente para las herramientas. Entiende "mañana", "el próximo lunes", etc.
10. La clínica NO abre los domingos. Si el paciente pide cita un domingo, indícale amablemente que no hay servicio ese día y ofrécele fechas de lunes a sábado.
11. No proporciones diagnósticos médicos ni sustituyas la atención de un profesional.

Se amable, claro y conciso en tus respuestas.
PROMPT;
    }

    public function processMessage(string $userMessage, ?int $userId = null): array
    {
        $messages = $this->conversation->messages ?? [];

        // Inicializar prompt del sistema si es nueva conversación
        if (empty($messages)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ];
        }

        // Agregar mensaje del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        $toolDefinitions = array_map(fn($tool) => $tool::getDefinition(), $this->tools);
        $toolMap = [];
        foreach ($this->tools as $tool) {
            $def = $tool::getDefinition();
            $toolMap[$def['function']['name']] = $tool;
        }

        $maxIterations = 5;
        $iteration = 0;
        
        $requiresConfirmation = false;
        $actionTaken = null;
        $appointmentData = null;

        while ($iteration < $maxIterations) {
            $iteration++;
            
            try {
                $responseMessage = $this->aiService->chat($messages, $toolDefinitions);
            } catch (\Exception $e) {
                Log::error("Agent Error: " . $e->getMessage());
                return [
                    'message' => 'Lo siento, estoy teniendo problemas técnicos en este momento. Por favor intenta de nuevo más tarde.',
                    'action' => null,
                    'requires_confirmation' => false
                ];
            }

            $messages[] = $responseMessage; // Agregar respuesta (que puede ser texto o tool_call)

            if (isset($responseMessage['tool_calls']) && !empty($responseMessage['tool_calls'])) {
                foreach ($responseMessage['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $functionArgs = json_decode($toolCall['function']['arguments'], true) ?? [];

                    if (isset($toolMap[$functionName])) {
                        $tool = $toolMap[$functionName];
                        
                        // Si la herramienta es create_appointment, inyectar patient_id si existe
                        if ($functionName === 'create_appointment' && $this->conversation->patient_id) {
                            $functionArgs['patient_id'] = $this->conversation->patient_id;
                        }

                        $toolResult = $tool->execute($functionArgs);
                        
                        // Registrar en log
                        AiAgentLog::create([
                            'conversation_id' => $this->conversation->id,
                            'user_id' => $userId,
                            'action' => $functionName,
                            'tool' => get_class($tool),
                            'input' => $functionArgs,
                            'output' => $toolResult,
                            'status' => isset($toolResult['error']) ? 'error' : 'success',
                        ]);

                        if ($functionName === 'check_availability') {
                            $requiresConfirmation = true;
                        }
                        
                        if ($functionName === 'create_appointment' && !isset($toolResult['error'])) {
                            $actionTaken = 'appointment_created';
                            $appointmentData = $toolResult['appointment'] ?? null;
                        }

                        // Enviar resultado de la herramienta de vuelta al LLM
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'name' => $functionName,
                            'content' => json_encode($toolResult)
                        ];
                    }
                }
            } else {
                // Si no hay llamadas a herramientas, la IA respondió con texto. Fin del ciclo.
                break;
            }
        }

        // Guardar contexto actualizado
        $this->conversation->messages = $messages;
        $this->conversation->save();

        return [
            'message' => $responseMessage['content'] ?? '',
            'action' => $actionTaken,
            'requires_confirmation' => $requiresConfirmation,
            'appointment' => $appointmentData
        ];
    }
}
