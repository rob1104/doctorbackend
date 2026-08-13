<?php

namespace App\AI\Tools;

abstract class BaseTool
{
    /**
     * Get the tool definition for the AI provider (OpenAI tool structure).
     */
    abstract public static function getDefinition(): array;

    /**
     * Execute the tool with the provided arguments.
     */
    abstract public function execute(array $arguments): array;
}
