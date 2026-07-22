<?php

declare(strict_types=1);

namespace AiSdk;

use AiSdk\Cohere\CohereOptions;
use AiSdk\Cohere\CohereProvider;
use AiSdk\Contracts\Model;

final class Cohere
{
    private static ?CohereProvider $default = null;
    /** @param array<string, mixed> $config */
    public static function create(array $config = []): CohereProvider
    {
        return self::$default = new CohereProvider(CohereOptions::fromArray($config));
    }
    public static function default(): CohereProvider
    {
        return self::$default ??= self::create();
    }
    public static function reset(): void
    {
        self::$default = null;
    }
    public static function model(string $modelId): Model
    {
        return self::default()->model($modelId);
    }
}
