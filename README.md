# PHP AI SDK — Cohere

Text generation, streaming, tools, structured output, and embeddings with Cohere. Part of the framework-agnostic [PHP AI SDK](https://phpaisdk.com).

## Installation

```bash
composer require aisdk/cohere
```

Set `COHERE_API_KEY` in your environment.

## Usage

```php
use AiSdk\Cohere;
use AiSdk\Generate;

$result = Generate::text('Explain closures in PHP in one sentence.')
    ->model(Cohere::model('command-a-plus-05-2026'))
    ->run();

echo $result->text;
```

## Documentation

[Read the documentation](https://phpaisdk.com/docs/0.x/providers/cohere) for configuration, supported features, and more examples.

[Contributing](https://github.com/phpaisdk/.github/blob/main/CONTRIBUTING.md) · [Support](https://github.com/phpaisdk/.github/blob/main/SUPPORT.md)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
