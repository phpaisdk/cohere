<?php

declare(strict_types=1);

namespace AiSdk\Cohere;

use AiSdk\Cohere\Models\CohereEmbeddingModel;
use AiSdk\Cohere\Models\CohereTextModel;
use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Contracts\TextProviderInterface;

final class CohereProvider extends BaseProvider implements EmbeddingProviderInterface, TextProviderInterface
{
    public function __construct(public readonly CohereOptions $options) {}

    public function name(): string
    {
        return CohereOptions::PROVIDER_NAME;
    }
    protected function textModel(string $modelId): TextModelInterface
    {
        return new CohereTextModel($modelId, $this->options);
    }
    protected function embeddingModel(string $modelId): EmbeddingModelInterface
    {
        return new CohereEmbeddingModel($modelId, $this->options);
    }
}
