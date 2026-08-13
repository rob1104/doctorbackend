<?php

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Patient;
use App\Models\AiConversation;
use App\Models\AiAgentLog;
use App\Models\OtpVerification;
use App\Models\Appointment;
use Carbon\Carbon;
use Mockery;
use App\AI\Contracts\AIServiceInterface;
use App\AI\Agents\MedicalAppointmentAgent;
use Illuminate\Support\Facades\Http;

class MedicalAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_check_availability()
    {
        // Mock AIService
        $aiMock = Mockery::mock(AIServiceInterface::class);
        $aiMock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [
                    [
                        'id' => 'call_123',
                        'type' => 'function',
                        'function' => [
                            'name' => 'check_availability',
                            'arguments' => json_encode(['date' => Carbon::tomorrow()->format('Y-m-d')])
                        ]
                    ]
                ]
            ]);

        $aiMock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'role' => 'assistant',
                'content' => 'Tengo disponibilidad mañana a las 09:00. ¿Deseas agendar?'
            ]);

        $this->app->instance(AIServiceInterface::class, $aiMock);
        
        $conversation = AiConversation::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
        ]);
        
        $agent = new MedicalAppointmentAgent($aiMock, $conversation);
        $response = $agent->processMessage("¿Hay citas para mañana?");

        $this->assertTrue($response['requires_confirmation']);
        $this->assertEquals('Tengo disponibilidad mañana a las 09:00. ¿Deseas agendar?', $response['message']);
        
        $logsCount = AiAgentLog::where('conversation_id', $conversation->id)
            ->where('action', 'check_availability')
            ->count();
        $this->assertEquals(1, $logsCount);
    }
}
