<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Carbon\Carbon;

class InvoiceParser
{
    /**
     * Palavras-chave nos nomes de produto que indicam NFC-e de combustível.
     */
    private const FUEL_KEYWORDS = [
        'gasolina', 'etanol', 'diesel', 'gnv', 'alcool',
        'combustivel', 'combustível', 'flex',
    ];

    /**
     * Mapeamento de palavra-chave para o valor padronizado de tipo_combustivel.
     */
    private const FUEL_TYPE_MAP = [
        'gasolina'    => 'gasolina',
        'etanol'      => 'etanol',
        'alcool'      => 'etanol',
        'álcool'      => 'etanol',
        'diesel'      => 'diesel',
        'gnv'         => 'gnv',
    ];

    public function parse(string $html): array
    {
        $dom = new DOMDocument();
        $html = preg_replace('/<!DOCTYPE[^>]+>/', '', $html);
        $html = '<?xml encoding="utf-8" ?>' . $html;

        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $estabelecimento = $this->parseEstabelecimento($xpath);
        $itens           = $this->parseItens($xpath);
        $totais          = $this->parseTotais($xpath);
        $dadosNota       = $this->parseDadosNota($xpath, $dom);
        $consumidor      = $this->parseConsumidor($xpath);

        $data = array_merge($estabelecimento, $dadosNota, $totais, [
            'itens'      => $itens,
            'consumidor' => $consumidor,
        ]);

        // ---------- detecção de NFC-e de combustível ----------
        $data = $this->detectFuel($data, $xpath, $dom);

        return $data;
    }

    // ============================================================
    // DETECÇÃO DE COMBUSTÍVEL
    // ============================================================

    private function detectFuel(array $data, DOMXPath $xpath, DOMDocument $dom): array
    {
        $data['is_combustivel'] = false;
        $data['fuel'] = null;

        if (empty($data['itens'])) {
            return $data;
        }

        // Verifica se algum item é combustível
        $fuelItem = null;
        foreach ($data['itens'] as $item) {
            $nomeLower = mb_strtolower($item['nome'] ?? '');
            foreach (self::FUEL_KEYWORDS as $kw) {
                if (str_contains($nomeLower, $kw)) {
                    $fuelItem = $item;
                    break 2;
                }
            }
        }

        if (! $fuelItem) {
            return $data;
        }

        $data['is_combustivel'] = true;

        // Determina tipo de combustível
        $nomeLower = mb_strtolower($fuelItem['nome']);
        $tipoComb  = null;
        foreach (self::FUEL_TYPE_MAP as $kw => $tipo) {
            if (str_contains($nomeLower, $kw)) {
                $tipoComb = $tipo;
                break;
            }
        }
        if (! $tipoComb) {
            // Tenta inferir por "aditivada" => ainda gasolina
            $tipoComb = str_contains($nomeLower, 'aditivada') ? 'gasolina_aditivada' : 'gasolina';
        }
        // "v power", "aditivada", etc. — mantém gasolina_aditivada
        if ($tipoComb === 'gasolina' && (
            str_contains($nomeLower, 'power') ||
            str_contains($nomeLower, 'aditivada') ||
            str_contains($nomeLower, 'premium')
        )) {
            $tipoComb = 'gasolina_aditivada';
        }

        // Litros: unidade LT  => quantidade já é os litros
        $litros = null;
        if (strtoupper(trim($fuelItem['unidade'] ?? '')) === 'LT') {
            $litros = (float) $fuelItem['quantidade'];
        }

        // Tenta extrair KM das informações de interesse do contribuinte
        $km = null;
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $texto = $body->textContent;
            // Formato na NFC-e MS: "Placa: / KM: 0" ou "Placa: AAA-0000 / KM: 12345"
            if (preg_match('/KM:\s*(\d+)/', $texto, $m) && (int) $m[1] > 0) {
                $km = (int) $m[1];
            }
        }

        $data['fuel'] = [
            'nome_produto'    => $fuelItem['nome'],
            'tipo_combustivel' => $tipoComb,
            'litros'          => $litros,
            'valor'           => (float) $data['valor_pago'],
            'data'            => $data['data_emissao'] instanceof Carbon
                ? $data['data_emissao']->toDateString()
                : null,
            'posto'           => $data['nome_estabelecimento'],
            'km'              => $km,
        ];

        return $data;
    }

    // ============================================================
    // PARSERS ORIGINAIS
    // ============================================================

    private function parseEstabelecimento(DOMXPath $xpath): array
    {
        $nomeNode = $xpath->query("//div[contains(@class, 'txtTopo')]");
        $nome = $nomeNode->length > 0 ? trim($nomeNode->item(0)->nodeValue) : '';

        $textDivs = $xpath->query("//div[contains(@class, 'txtCenter')]//div[contains(@class, 'text')]");

        $cnpj    = '';
        $endereco = '';

        foreach ($textDivs as $div) {
            $txt = trim(preg_replace('/\s+/', ' ', $div->nodeValue));

            if (strpos($txt, 'CNPJ:') !== false || preg_match('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', $txt)) {
                $cnpj = trim(str_replace('CNPJ:', '', $txt));
                $cnpj = preg_replace('/\s+/', '', $cnpj);
            } else {
                $endereco = $txt;
            }
        }

        return [
            'nome_estabelecimento'     => $nome ?: 'Não encontrado',
            'cnpj'                     => $cnpj ?: 'Não encontrado',
            'endereco_estabelecimento' => $endereco ?: 'Não encontrado',
        ];
    }

    private function parseItens(DOMXPath $xpath): array
    {
        $itens = [];

        $rows = $xpath->query("//table[@id='tabResult']//tr[contains(@id, 'Item')]");

        if ($rows->length === 0) {
            $rows = $xpath->query("//tr[starts-with(@id, 'Item')]");
        }

        foreach ($rows as $row) {
            $item = $this->parseItemRow($xpath, $row);
            if ($item && ! empty($item['nome'])) {
                $itens[] = $item;
            }
        }

        return $itens;
    }

    private function parseItemRow(DOMXPath $xpath, \DOMElement $row): ?array
    {
        $tds = $row->getElementsByTagName('td');
        if ($tds->length < 2) return null;

        $td1 = $tds->item(0);
        $td2 = $tds->item(1);

        $nome = '';
        $spanTit = $xpath->query(".//span[contains(@class, 'txtTit')]", $td1);
        if ($spanTit->length > 0) {
            $nome = trim($spanTit->item(0)->nodeValue);
        }

        $codigo = '';
        $spanCod = $xpath->query(".//span[contains(@class, 'RCod')]", $td1);
        if ($spanCod->length > 0) {
            if (preg_match('/\(Código:\s*([^\)]+)\)/', $spanCod->item(0)->nodeValue, $m)) {
                $codigo = trim($m[1]);
            }
        }

        $quantidade = 0.0;
        $spanQtde = $xpath->query(".//span[contains(@class, 'Rqtd')]", $td1);
        if ($spanQtde->length > 0) {
            $txt    = strip_tags($spanQtde->item(0)->nodeValue);
            $parts  = explode(':', $txt);
            $quantidade = isset($parts[1]) ? $this->parseFloat($parts[1]) : 0;
        }

        // Unidade: o HTML traz "UN UN0001" ou "KG KG0001" — pega só a primeira palavra
        $unidade = '';
        $spanUN = $xpath->query(".//span[contains(@class, 'RUN')]", $td1);
        if ($spanUN->length > 0) {
            $txt   = strip_tags($spanUN->item(0)->nodeValue);
            $parts = explode(':', $txt);
            if (isset($parts[1])) {
                // Extrai apenas a primeira sequência de letras (UN, KG, LT, PC, etc.)
                preg_match('/^\s*([A-Za-z]+)/', $parts[1], $m);
                $unidade = isset($m[1]) ? strtoupper(trim($m[1])) : trim($parts[1]);
            }
        }

        $valorUnitario = 0.0;
        $spanVlUnit = $xpath->query(".//span[contains(@class, 'RvlUnit')]", $td1);
        if ($spanVlUnit->length > 0) {
            $txt   = strip_tags($spanVlUnit->item(0)->nodeValue);
            $parts = explode(':', $txt);
            $valorUnitario = isset($parts[1]) ? $this->parseFloat($parts[1]) : 0;
        }

        $valorTotal = 0.0;
        $spanValor = $xpath->query(".//span[contains(@class, 'valor')]", $td2);
        if ($spanValor->length > 0) {
            $valorTotal = $this->parseFloat(trim($spanValor->item(0)->nodeValue));
        }

        return [
            'nome'           => $nome,
            'codigo'         => $codigo,
            'quantidade'     => $quantidade,
            'unidade'        => $unidade,
            'valor_unitario' => $valorUnitario,
            'valor_total'    => $valorTotal,
        ];
    }

    private function parseTotais(DOMXPath $xpath): array
    {
        $totais = [
            'total_itens'     => 0,
            'valor_total'     => 0.0,
            'descontos'       => 0.0,
            'valor_pago'      => 0.0,
            'forma_pagamento' => '',
        ];

        $divTotal = $xpath->query("//div[@id='totalNota']")->item(0);
        if (! $divTotal) {
            $divTotal = $xpath->query("//div[contains(@class, 'txtRight')]")->item(0);
        }
        if (! $divTotal) return $totais;

        $linhas = $xpath->query(".//div[@id='linhaTotal']", $divTotal);

        foreach ($linhas as $linha) {
            $labelNode = $xpath->query(".//label", $linha)->item(0);
            if (! $labelNode) continue;

            $labelText = trim($labelNode->nodeValue);

            $spanNode = $xpath->query(".//span[contains(@class, 'totalNumb')]", $linha)->item(0);
            if (! $spanNode) continue;

            $valor = $this->parseFloat(trim($spanNode->nodeValue));

            if (stripos($labelText, 'Qtd. total de itens') !== false) {
                $totais['total_itens'] = (int) $valor;
            } elseif (stripos($labelText, 'Valor total R$') !== false) {
                $totais['valor_total'] = $valor;
            } elseif (stripos($labelText, 'Descontos R$') !== false) {
                $totais['descontos'] = $valor;
            } elseif (stripos($labelText, 'Valor a pagar R$') !== false) {
                $totais['valor_pago'] = $valor;
            }
        }

        $linhaForma = $xpath->query(".//div[@id='linhaForma']", $divTotal)->item(0);
        if ($linhaForma) {
            $todasLinhas  = $xpath->query(".//div[@id='linhaTotal']", $divTotal);
            $encontrouForma = false;

            foreach ($todasLinhas as $linha) {
                if ($linha->isSameNode($linhaForma)) {
                    $encontrouForma = true;
                    continue;
                }
                if ($encontrouForma) {
                    $labelTx = $xpath->query(".//label[contains(@class, 'tx')]", $linha)->item(0);
                    if ($labelTx) {
                        $totais['forma_pagamento'] = trim($labelTx->nodeValue);
                        break;
                    }
                }
            }
        }

        return $totais;
    }

    private function parseDadosNota(DOMXPath $xpath, DOMDocument $dom): array
    {
        $dados = [
            'numero'      => '',
            'serie'       => '',
            'data_emissao' => null,
            'chave'       => '',
        ];

        $body  = $dom->getElementsByTagName('body')->item(0);
        if (! $body) return $dados;

        $texto = $body->textContent;

        if (preg_match('/Número:\s*(\d+)/', $texto, $m)) {
            $dados['numero'] = $m[1];
        }

        if (preg_match('/Série:\s*(\d+)/', $texto, $m)) {
            $dados['serie'] = $m[1];
        }

        if (preg_match('/Emissão:\s*(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/', $texto, $m)) {
            try {
                $dados['data_emissao'] = Carbon::createFromFormat('d/m/Y H:i:s', $m[1]);
            } catch (\Exception $e) {
                $dados['data_emissao'] = $m[1];
            }
        }

        $spanChave = $xpath->query("//span[contains(@class, 'chave')]")->item(0);
        if ($spanChave) {
            $chave = preg_replace('/\s+/', '', $spanChave->nodeValue);
            $dados['chave'] = $chave;
        }

        return $dados;
    }

    private function parseConsumidor(DOMXPath $xpath): array
    {
        $consumidor = ['cpf' => '', 'nome' => ''];
        $lis = $xpath->query("//li");

        foreach ($lis as $li) {
            $texto = $li->nodeValue;
            if (preg_match('/CPF:\s*([\d.]+-?\d*)/', $texto, $m)) {
                $consumidor['cpf'] = trim($m[1]);
            }
            if (preg_match('/Nome:\s*(.+)/', $texto, $m)) {
                $nome = trim($m[1]);
                if (! empty($nome)) $consumidor['nome'] = $nome;
            }
        }

        return $consumidor;
    }

    private function parseFloat(string $value): float
    {
        $value = trim($value);
        $value = preg_replace('/[^\d,.\-]/', '', $value);

        if (empty($value)) return 0.0;

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}
