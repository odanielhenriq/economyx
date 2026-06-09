<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportStatementService
{
    private const MODEL   = 'claude-haiku-4-5-20251001';
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function parse(string $content, Collection $categories): array
    {
        $apiKey = config('services.anthropic.key');

        if (! $apiKey) {
            throw new \RuntimeException('Chave da API Anthropic não configurada (ANTHROPIC_API_KEY).');
        }

        $categoryList = $categories
            ->map(fn ($c) => "- ID {$c->id}: {$c->name}")
            ->implode("\n");

        $system = <<<PROMPT
Você é um assistente financeiro especializado em interpretar extratos bancários brasileiros.
Analise o texto fornecido e retorne APENAS um JSON válido, sem markdown, sem texto extra.

Formato obrigatório:
{
  "transactions": [
    {
      "date": "YYYY-MM-DD",
      "description": "Descrição limpa em português",
      "amount": 0.00,
      "type": "expense",
      "category_id": null
    }
  ]
}

Categorias disponíveis (use o ID numérico exato):
{$categoryList}

Regras gerais:
- type: "income" para entradas/créditos/receitas; "expense" para saídas/débitos/despesas
- amount: número positivo (ex: 39.90)
- date: formato YYYY-MM-DD; se não houver ano, use o ano atual
- description: nome limpo, sem códigos internos do banco
- category_id: ID numérico da categoria mais adequada ou null
- Não inclua transferências entre contas próprias como despesa

IGNORE COMPLETAMENTE estas linhas (não inclua no JSON):
- Qualquer linha com "PAGAMENTO DE FATURA" ou "PAGTO FATURA"
- Linhas de "IOF DESPESA NO EXTERIOR" (o IOF já está embutido no valor convertido da compra)
- Linhas de "COTAÇÃO DOLAR"
- Linhas com "VALOR TOTAL", "Saldo Anterior", "Saldo Desta Fatura", "Total Despesas", "Total Créditos", "Total de pagamentos"
- Cabeçalhos de seção como "Parcelamentos", "Despesas", "Pagamento e Demais Créditos"
- Linhas de identificação de cartão (ex: "DANIEL H O DA SILVA - 4108 XXXX")

PARCELAS:
- Inclua o número da parcela na descrição no formato "(atual/total)"
- Exemplo: "Disney Plus (2/2)", "Spotify (2/10)"
- O formato no extrato costuma ser: [número] [data] [descrição] [parcela atual/total] [valor]

PIX E TRANSFERÊNCIAS PARA PESSOAS:
- Se a descrição for apenas um nome de pessoa, prefixar com "PIX - "
- Exemplo: "MP*DANIEL" → "PIX - Daniel"
PROMPT;

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->withOptions([
                    'verify' => app()->isProduction(),
                ])
                ->post(self::API_URL, [
                    'model'      => self::MODEL,
                    'max_tokens' => 4096,
                    'system'     => $system,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => "Analise este extrato bancário:\n\n" . $content,
                        ],
                    ],
                ]);

            Log::info('Anthropic response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Erro na API: ' . $response->status() . ' — ' . $response->body()
                );
            }

            $rawText = $response->json('content.0.text');

            if (! $rawText) {
                throw new \RuntimeException(
                    'Resposta vazia da API. Body: ' . $response->body()
                );
            }

            // Extrai o JSON mesmo que a IA inclua texto ao redor
            preg_match('/\{[\s\S]*\}/m', $rawText, $matches);
            $jsonStr = $matches[0] ?? $rawText;
            $parsed  = json_decode($jsonStr, true);

            if (! isset($parsed['transactions']) || ! is_array($parsed['transactions'])) {
                throw new \RuntimeException(
                    'JSON inválido. Conteúdo recebido: ' . $rawText
                );
            }
        } catch (\RuntimeException $e) {
            Log::error('ImportStatementService error', ['message' => $e->getMessage()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('ImportStatementService error', ['message' => $e->getMessage()]);
            throw new \RuntimeException($e->getMessage());
        }

        $validIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_map(function (array $tx, int $i) use ($validIds): array {
            $catId = isset($tx['category_id']) ? (int) $tx['category_id'] : null;

            if ($catId !== null && ! in_array($catId, $validIds, true)) {
                $catId = null;
            }

            $description        = $tx['description'] ?? 'Sem descrição';
            $installmentNumber  = null;
            $installmentTotal   = null;

            // Extrair padrão "(N/M)" da descrição e remover do texto
            if (preg_match('/\((\d+)\/(\d+)\)/', $description, $matches)) {
                $installmentNumber = (int) $matches[1];
                $installmentTotal  = (int) $matches[2];
                $description       = trim(preg_replace('/\s*\(\d+\/\d+\)/', '', $description));
            }

            return [
                'temp_id'            => 'tx_' . $i,
                'date'               => $tx['date'] ?? now()->format('Y-m-d'),
                'description'        => $description,
                'amount'             => abs((float) ($tx['amount'] ?? 0)),
                'type'               => \in_array($tx['type'] ?? '', ['income', 'expense'], true)
                                            ? $tx['type']
                                            : 'expense',
                'category_id'        => $catId,
                'installment_number' => $installmentNumber,
                'installment_total'  => $installmentTotal,
            ];
        }, $parsed['transactions'], array_keys($parsed['transactions'])));
    }
}
