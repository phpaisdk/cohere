<?php

declare(strict_types=1);

namespace AiSdk\Cohere\Converters;

use AiSdk\FinishReason;

final class MapsCohereFinishReason
{
    public static function fromApi(?string $reason): FinishReason
    {
        return match ($reason) {
            'COMPLETE', 'STOP_SEQUENCE' => FinishReason::Stop,
            'MAX_TOKENS' => FinishReason::Length,
            'TOOL_CALL' => FinishReason::ToolCalls,
            'ERROR' => FinishReason::Error,
            default => FinishReason::Unknown,
        };
    }
}
