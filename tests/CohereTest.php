<?php

declare(strict_types=1);

use AiSdk\Cohere;
use AiSdk\Cohere\Tests\Fakes\FakeHttpClient;
use AiSdk\Generate;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

afterEach(function (): void {
    Generate::reset();
    Cohere::reset();
});

function configureCohere(FakeHttpClient $client): void
{
    $factory = new Psr17Factory();
    Generate::configure(new Sdk($client, $factory, $factory));
}

it('generates text through Cohere v2 chat', function (): void {
    configureCohere(new FakeHttpClient(200, json_encode(['id' => 'cohere-response', 'message' => ['content' => [['type' => 'text', 'text' => 'Hello from Cohere']]], 'finish_reason' => 'COMPLETE', 'meta' => ['tokens' => ['input_tokens' => 5, 'output_tokens' => 3]]])));
    Cohere::create(['apiKey' => 'cohere-test']);
    $result = Generate::text('Hi')->model(Cohere::model('command-a-03-2025'))->run();
    expect($result->text)->toBe('Hello from Cohere')->and($result->usage->inputTokens)->toBe(5);
});

it('sends direct text options and portable tool choices', function (): void {
    $client = new FakeHttpClient(200, json_encode(['id' => 'cohere-response', 'message' => ['content' => [['type' => 'text', 'text' => 'Done']]], 'finish_reason' => 'COMPLETE']));
    configureCohere($client);
    Cohere::create(['apiKey' => 'cohere-test']);

    Generate::text('Hi')
        ->model(Cohere::model('command-a-03-2025'))
        ->tool(\AiSdk\Tool::make('weather'))
        ->toolChoice(\AiSdk\ToolChoice::tool('weather'))
        ->providerOptions('cohere', ['safety_mode' => 'strict'])
        ->run();

    expect($client->sentBody()['safety_mode'])->toBe('strict')
        ->and($client->sentBody()['tool_choice'])->toBe([
            'type' => 'function',
            'function' => ['name' => 'weather'],
        ]);
});

it('uses Cohere v2 embeddings', function (): void {
    $client = new FakeHttpClient(200, json_encode(['embeddings' => ['float' => [[0.1, 0.2]]], 'meta' => ['billed_units' => ['input_tokens' => 4]]]));
    configureCohere($client);
    Cohere::create(['apiKey' => 'cohere-test']);
    $result = Generate::embedding('Document')->model(Cohere::model('embed-v4.0'))->providerOptions('cohere', ['inputType' => 'search_query'])->run();
    expect($result->output->vector)->toBe([0.1, 0.2])->and($client->lastRequest?->getUri()->getPath())->toBe('/v2/embed')->and($client->sentBody()['input_type'])->toBe('search_query');
});

it('accepts opaque Cohere model identifiers', function (): void {
    Cohere::create(['apiKey' => 'cohere-test']);
    expect(Cohere::model('private-cohere-model')->modelId())->toBe('private-cohere-model');
});

it('rejects a successful response that has no message', function (string $body): void {
    configureCohere(new FakeHttpClient(200, $body));
    Cohere::create(['apiKey' => 'cohere-test']);

    expect(fn() => Generate::text('Hi')->model(Cohere::model('command-a-03-2025'))->run())->toThrow(AiSdk\Exceptions\InvalidResponseException::class);
})->with(['{}', '[1,2]', '{"message":"quota exceeded"}']);
