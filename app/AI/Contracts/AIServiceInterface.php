<?php

namespace App\AI\Contracts;

interface AIServiceInterface
{
    /**
     * Send a prompt to the AI and get a response.
     *
     * @param array $messages The conversation history
     * @param array $tools Definitions of available tools
     * @return array The AI's response (can include tool calls)
     */
    public function chat(array $messages, array $tools = []): array;
}
