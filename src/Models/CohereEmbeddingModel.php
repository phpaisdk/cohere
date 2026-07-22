<?php

declare(strict_types=1);

namespace AiSdk\Cohere\Models;

use AiSdk\Cohere\CohereOptions;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Requests\EmbeddingRequest;
use AiSdk\Responses\EmbeddingResponse;
use AiSdk\Results\EmbeddingData;
use AiSdk\Support\Usage;
use AiSdk\Utils\Support\Url;

final class CohereEmbeddingModel extends BaseModel implements EmbeddingModelInterface
{
    public function __construct(private readonly string $modelId, private readonly CohereOptions $options) {}
    public function provider(): string
    {
        return CohereOptions::PROVIDER_NAME;
    }
    public function modelId(): string
    {
        return $this->modelId;
    }

    public function generate(EmbeddingRequest $request): EmbeddingResponse
    {
        $options = $request->providerOptionsFor($this->provider());
        $body = ['model' => $this->modelId, 'texts' => $request->inputs, 'input_type' => (string) ($options['inputType'] ?? 'search_document'), 'embedding_types' => ['float']];
        if ($request->dimensions !== null) {
            $body['output_dimension'] = $request->dimensions;
        }
        if (isset($options['truncate'])) {
            $body['truncate'] = $options['truncate'];
        }
        $payload = $this->runner($this->options->sdk)->postJson(Url::joinPath($this->options->baseUrl, '/embed'), $body, $this->options->authHeaders(), $this->provider());
        $embeddings = [];
        foreach ((array) ($payload['embeddings']['float'] ?? []) as $index => $vector) {
            $embeddings[] = new EmbeddingData(array_map(static fn($item): float => (float) $item, (array) $vector), $index);
        }
        $tokens = (array) ($payload['meta']['billed_units'] ?? []);

        return new EmbeddingResponse($embeddings, new Usage((int) ($tokens['input_tokens'] ?? 0)), $payload, ['cohere' => []]);
    }
}
