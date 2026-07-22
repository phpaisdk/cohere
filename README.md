# aisdk/cohere

<a href="https://github.com/phpaisdk/cohere/actions"><img alt="GitHub Workflow Status" src="https://img.shields.io/github/actions/workflow/status/phpaisdk/cohere/tests.yml?branch=main&label=Tests"></a>
<a href="https://packagist.org/packages/aisdk/cohere"><img alt="Latest Version" src="https://img.shields.io/packagist/v/aisdk/cohere"></a>
<a href="https://packagist.org/packages/aisdk/cohere"><img alt="License" src="https://img.shields.io/packagist/l/aisdk/cohere"></a>

------

Official Cohere provider for the PHP AI SDK. Uses Cohere's native v2 Chat and Embed APIs.

## Installation

```bash
composer require aisdk/cohere
```

## Basic Usage

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

## Configuration

| Variable | Description | Default |
| --- | --- | --- |
| `COHERE_API_KEY` | API key for authentication | Required |
| `COHERE_BASE_URL` | Base URL for API requests | `https://api.cohere.com` |

## Supported Capabilities

| Capability | Support |
| --- | --- |
| Text generation and streaming | Native v2 Chat API |
| Tools, structured output, reasoning, image input | Native where supported by the selected model |
| Embeddings | Native v2 Embed API |

Use `providerOptions('cohere', ['raw' => [...]])` for Cohere fields outside the portable SDK surface. Model IDs are opaque strings and are validated by Cohere.

## Documentation

- [Cohere v2 Chat API reference](https://docs.cohere.com/v2/reference/chat)
- [PHP AI SDK documentation](https://phpaisdk.com/docs/cohere)
