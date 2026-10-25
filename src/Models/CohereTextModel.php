<?php

declare(strict_types=1);

namespace AiSdk\Cohere\Models;

use AiSdk\Capability;
use AiSdk\Cohere\CohereOptions;
use AiSdk\Cohere\Converters\ConvertsCohereMessages;
use AiSdk\Cohere\Converters\MapsCohereFinishReason;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Outputs\Output;
use AiSdk\Requests\TextModelRequest;
use AiSdk\Responses\Parts\ReasoningPart;
use AiSdk\Responses\Parts\TextPart;
use AiSdk\Responses\Parts\ToolCallPart;
use AiSdk\Responses\TextModelResponse;
use AiSdk\Streaming\FinishPart;
use AiSdk\Streaming\ReasoningDeltaPart;
use AiSdk\Streaming\TextDeltaPart;
use AiSdk\Streaming\ToolCallDeltaPart;
use AiSdk\Support\Usage;
use AiSdk\ToolChoice;
use AiSdk\Utils\Support\Url;
use Generator;

final class CohereTextModel extends BaseModel implements TextModelInterface
{
    private const array CAPABILITIES = [Capability::TextGeneration, Capability::Streaming, Capability::ToolCalling, Capability::StructuredOutput, Capability::Reasoning, Capability::TextInput, Capability::ImageInput];

    public function __construct(private readonly string $modelId, private readonly CohereOptions $options) {}
    public function provider(): string
    {
        return CohereOptions::PROVIDER_NAME;
    }
    public function modelId(): string
    {
        return $this->modelId;
    }

    public function generate(TextModelRequest $request): TextModelResponse
    {
        $this->ensureTextRequestSupported($request, self::CAPABILITIES);
        $payload = $this->runner($this->options->sdk)->postJson(Url::joinPath($this->options->baseUrl, '/chat'), $this->body($request, false), $this->options->authHeaders(), $this->provider());
        $parts = [];
        foreach ((array) ($payload['message']['content'] ?? []) as $content) {
            if (($content['type'] ?? null) === 'text' && is_string($content['text'] ?? null)) {
                $parts[] = new TextPart($content['text']);
            }
            if (($content['type'] ?? null) === 'thinking' && is_string($content['thinking'] ?? null)) {
                $parts[] = new ReasoningPart($content['thinking']);
            }
        }
        foreach ((array) ($payload['message']['tool_calls'] ?? []) as $call) {
            $function = is_array($call['function'] ?? null) ? $call['function'] : [];
            $arguments = json_decode((string) ($function['arguments'] ?? '{}'), true);
            $parts[] = new ToolCallPart((string) ($call['id'] ?? ''), (string) ($function['name'] ?? ''), is_array($arguments) ? $arguments : []);
        }
        $tokens = (array) ($payload['meta']['tokens'] ?? $payload['usage']['tokens'] ?? []);

        return new TextModelResponse($parts, MapsCohereFinishReason::fromApi(isset($payload['finish_reason']) ? (string) $payload['finish_reason'] : null), new Usage((int) ($tokens['input_tokens'] ?? 0), (int) ($tokens['output_tokens'] ?? 0)), $payload, ['cohere' => ['id' => $payload['id'] ?? null]]);
    }

    public function stream(TextModelRequest $request): Generator
    {
        $this->ensureTextRequestSupported($request, self::CAPABILITIES, streaming: true);
        $finishReason = MapsCohereFinishReason::fromApi(null);
        $usage = Usage::empty();
        foreach ($this->runner($this->options->sdk)->postStream(Url::joinPath($this->options->baseUrl, '/chat'), $this->body($request, true), $this->options->authHeaders(), $this->provider()) as $event) {
            $payload = json_decode((string) ($event['data'] ?? ''), true);
            if (! is_array($payload)) {
                continue;
            }
            $type = $payload['type'] ?? '';
            $delta = (array) ($payload['delta'] ?? []);
            $message = (array) ($delta['message'] ?? []);
            $content = (array) ($message['content'] ?? []);
            if ($type === 'content-delta' && is_string($content['text'] ?? null)) {
                yield new TextDeltaPart($content['text']);
            }
            if ($type === 'content-delta' && is_string($content['thinking'] ?? null)) {
                yield new ReasoningDeltaPart($content['thinking']);
            }
            if ($type === 'tool-call-start') {
                $toolCall = (array) ($message['tool_calls'] ?? []);
                $function = (array) ($toolCall['function'] ?? []);
                yield new ToolCallDeltaPart((int) ($toolCall['index'] ?? 0), (string) ($function['arguments'] ?? ''), (string) ($toolCall['id'] ?? ''), (string) ($function['name'] ?? ''));
            }
            if ($type === 'message-end') {
                $finishReason = MapsCohereFinishReason::fromApi(isset($delta['finish_reason']) ? (string) $delta['finish_reason'] : null);
                $tokens = (array) ($delta['usage']['tokens'] ?? []);
                $usage = new Usage((int) ($tokens['input_tokens'] ?? 0), (int) ($tokens['output_tokens'] ?? 0));
            }
        }
        yield new FinishPart($finishReason, $usage);
    }

    /** @return array<string, mixed> */
    private function body(TextModelRequest $request, bool $stream): array
    {
        $body = ['model' => $this->modelId, 'messages' => ConvertsCohereMessages::build($request->messages, $request->system), 'stream' => $stream];
        if ($request->maxTokens !== null) {
            $body['max_tokens'] = $request->maxTokens;
        }
        if ($request->temperature !== null) {
            $body['temperature'] = $request->temperature;
        }
        if ($request->topP !== null) {
            $body['p'] = $request->topP;
        }
        if ($request->tools !== [] && $request->toolChoice?->type !== ToolChoice::NONE) {
            $body['tools'] = array_map(static fn($tool) => ['type' => 'function', 'function' => ['name' => $tool->name(), 'description' => $tool->description(), 'parameters' => $tool->inputSchemaForProvider()]], $request->tools);
            $body['tool_choice'] = self::toolChoice($request->toolChoice);
        }
        if ($request->output?->kind === Output::KIND_OBJECT) {
            $body['response_format'] = ['type' => 'json_object', 'json_schema' => $request->output->schema?->jsonSchema()];
        }

        return self::mergeOptions($body, $request->providerOptionsFor($this->provider()));
    }

    /** @return string|array<string, mixed> */
    private static function toolChoice(?ToolChoice $choice): string|array
    {
        if ($choice === null) {
            return 'auto';
        }

        return match ($choice->type) {
            ToolChoice::TOOL => ['type' => 'function', 'function' => ['name' => (string) $choice->toolName]],
            default => $choice->type,
        };
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function mergeOptions(array $body, array $options): array
    {
        $raw = $options['raw'] ?? null;
        unset($options['raw']);

        $body = array_replace($body, $options);

        return is_array($raw) ? array_replace($body, $raw) : $body;
    }
}
