<?php

namespace App\Facades;

use App\Contracts\Llm\LlmClientInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string generateText(string $prompt, array $options = [])
 * @method static mixed generateStructuredOutput(string $prompt, string $format, array $options = [])
 * @method static array generateEmbeddings(string $text)
 *
 * @see \App\Contracts\Llm\LlmClientInterface
 */
class Llm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LlmClientInterface::class;
    }
}
