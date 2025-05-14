<?php
/**
 * Lightweight utility: makeEmbedding
 * ----------------------------------
 * Returns JSON‑encoded 1536‑dimensional vector (string).
 * Keeps a static OpenAI client instance for reuse.
 *
 * Usage:
 *   require_once __DIR__ . '/lib/embedding_util.php';
 *   $vecJson = makeEmbedding($pageUid, $text);
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// use OpenAI\Laravel\Facades\OpenAI;   // fallback when running inside Laravel
use OpenAI\Client as OpenAIClient;

/**
 * Create embedding via OpenAI and return JSON string.
 *
 * @param string $uid   – user / page identifier for OpenAI usage tracking
 * @param string $text  – text to embed
 * @return string       – JSON array (e.g. "[0.01, …]")
 * @throws \Throwable   – bubble up any client error
 */
function makeEmbedding(string $uid, string $text): string
{
    static $client = null;

    if ($client === null) {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY not set');
        }

        // openai-php/client v0.6
        $client = \OpenAI::client($apiKey);
        if (!$client instanceof OpenAIClient) {
            // In case the global helper is unavailable (non‑Laravel env)
            $client = \OpenAI\Client::factory()
                      ->withApiKey($apiKey)
                      ->make();
        }
    }

    $response = $client->embeddings()->create([
        'model' => 'text-embedding-3-small',
        'input' => $text,
        'user'  => $uid,
    ]);

    // openai-php/client v0.6: embeddings()->create() returns CreateResponse
    // ->embeddings is an array of Embedding objects
    $vec = $response->embeddings[0]->embedding ?? null;

    // var_dump($vec);
    if ($vec === null) {
        throw new RuntimeException('Embedding API returned empty vector');
    }


    // Encode as compact JSON without extra spaces
    return json_encode($vec, JSON_UNESCAPED_SLASHES);
}