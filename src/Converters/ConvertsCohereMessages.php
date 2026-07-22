<?php

declare(strict_types=1);

namespace AiSdk\Cohere\Converters;

use AiSdk\Content;
use AiSdk\Message;

final class ConvertsCohereMessages
{
    /**
     * @param  array<int, Message>  $messages
     * @return array<int, array<string, mixed>>
     */
    public static function build(array $messages, ?string $system): array
    {
        $payload = $system !== null && $system !== '' ? [['role' => 'system', 'content' => $system]] : [];
        foreach ($messages as $message) {
            $content = [];
            foreach ($message->content as $part) {
                if ($part->type === Content::TYPE_TEXT && $part->textValue() !== null) {
                    $content[] = ['type' => 'text', 'text' => $part->textValue()];
                }
                if ($part->type === Content::TYPE_IMAGE) {
                    $content[] = ['type' => 'image_url', 'image_url' => ['url' => $part->source()->value === 'url' ? $part->url() : $part->dataUri()]];
                }
            }
            $value = count($content) === 1 && $content[0]['type'] === 'text' ? $content[0]['text'] : $content;
            $payload[] = match ($message->role) {
                Message::ROLE_TOOL => ['role' => 'tool', 'tool_call_id' => $message->toolCallId ?? '', 'content' => $value],
                default => ['role' => $message->role, 'content' => $value],
            };
        }

        return $payload;
    }
}
