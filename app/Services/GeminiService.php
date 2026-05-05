<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private int $retryAfter = 0;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
    }

    /**
     * Analisa uma imagem de encarte
     */
    public function analisarEncarte(string $imagePath): array
    {
        if (!$this->apiKey || $this->apiKey === 'sua-chave-aqui') {
            throw new \Exception('⚠️ Chave da API Gemini não configurada. Acesse https://aistudio.google.com/app/apikey para obter uma chave gratuita.');
        }

        // Verificar tamanho da imagem (max 4MB)
        $fileSize = filesize($imagePath);
        if ($fileSize > 4 * 1024 * 1024) {
            throw new \Exception('⚠️ Imagem muito grande (máximo 4MB). Reduza o tamanho da imagem.');
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

        $prompt = <<<PROMPT
Analise esta imagem de encarte de supermercado e extraia TODOS os produtos visíveis.

REGRAS IMPORTANTES:
1. Extraia apenas produtos que tenham NOME e PREÇO claramente visíveis
2. Ignore textos de cabeçalho, endereços, telefones
3. Se o produto tiver quantidade (ex: 1KG, 500G, 2L), use o campo quantidade
4. Detecte a unidade: KG, G, L, ML, UN, PCT, CX, FD, LT, DZ
5. Se não conseguir identificar a unidade, use "UN"
6. Converta preços para número (ex: "R$ 14,99" → 14.99)

Retorne APENAS um JSON válido no seguinte formato, SEM nenhum texto adicional:
{
    "estabelecimento": "Nome do mercado se visível, ou string vazia",
    "validade_texto": "Texto sobre validade se visível, ou string vazia",
    "produtos": [
        {
            "nome": "Nome do produto",
            "preco": 14.99,
            "quantidade": 1,
            "unidade": "KG",
            "observacao": "Qualquer observação adicional"
        }
    ]
}

IMPORTANTE: Retorne APENAS o JSON. Não inclua explicações, markdown, ou qualquer texto fora do JSON.
PROMPT;

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if ($response->status() === 429) {
                $body = $response->json();
                $retryInfo = $body['error']['details'][0]['retryDelay'] ?? '60s';
                $this->retryAfter = (int) filter_var($retryInfo, FILTER_SANITIZE_NUMBER_INT);
                throw new \Exception('⏳ Limite de requisições excedido. Tente novamente em ' . $this->retryAfter . ' segundos.');
            }

            if ($response->status() === 403) {
                throw new \Exception('🔑 Chave da API inválida ou sem permissão. Verifique a GEMINI_API_KEY no arquivo .env');
            }

            if (!$response->successful()) {
                Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \Exception('❌ Erro na API Gemini (HTTP ' . $response->status() . '). Tente novamente mais tarde.');
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if (!$text) {
                throw new \Exception('❌ Resposta vazia do Gemini. Tente com outra imagem.');
            }

            // Limpar o texto
            $text = trim($text);
            $text = preg_replace('/^```json\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            
            $result = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini JSON Parse Error', ['text' => $text]);
                throw new \Exception('❌ Não foi possível interpretar a resposta. A imagem pode não ser um encarte válido.');
            }

            return $result;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '⏳') || 
                str_contains($e->getMessage(), '🔑') ||
                str_contains($e->getMessage(), '⚠️') ||
                str_contains($e->getMessage(), '❌')) {
                throw $e;
            }
            Log::error('Gemini Service Error: ' . $e->getMessage());
            throw new \Exception('❌ Erro ao processar imagem: ' . $e->getMessage());
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'sua-chave-aqui';
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
