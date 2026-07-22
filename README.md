# Cohere provider for PHP AI SDK

Use Cohere's native v2 Chat and Embed APIs with the framework-agnostic PHP AI SDK.

## Install

```bash
composer require aisdk/cohere
```

## Text generation

```php
use AiSdk\Cohere;
use AiSdk\Generate;

Cohere::create(['apiKey' => $_ENV['COHERE_API_KEY']]);

$result = Generate::text('Explain vector databases in one sentence.')
    ->model(Cohere::model('command-a-03-2025'))
    ->run();
```

## Embeddings

```php
$result = Generate::embedding(['A document to index'])
    ->model(Cohere::model('embed-v4.0'))
    ->providerOptions('cohere', ['inputType' => 'search_document'])
    ->run();
```

The provider supports Cohere v2 text generation, streaming, tools, structured output, reasoning, image input, and embeddings. Pass Cohere-specific request fields through `providerOptions('cohere', ['raw' => [...]])` for options outside the portable SDK surface.

See [PHP AI SDK documentation](https://phpaisdk.com/docs/cohere) and [Cohere's API reference](https://docs.cohere.com/v2/reference/chat).
