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
