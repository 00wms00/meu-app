<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Carbon\Carbon;

class InvoiceParser
{
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
        $itens = $this->parseItens($xpath);
        $totais = $this->parseTotais($xpath);
        $dadosNota = $this->parseDadosNota($xpath, $dom);
        $consumidor = $this->parseConsumidor($xpath);

        return array_merge($estabelecimento, $dadosNota, $totais, [
            'itens' => $itens,
            'consumidor' => $consumidor,
        ]);
    }

    private function parseEstabelecimento(DOMXPath $xpath): array
    {
        $nomeNode = $xpath->query("//div[contains(@class, 'txtTopo')]");
        $nome = $nomeNode->length > 0 ? trim($nomeNode->item(0)->nodeValue) : '';

        $textDivs = $xpath->query("//div[contains(@class, 'txtCenter')]//div[contains(@class, 'text')]");
        
        $cnpj = '';
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
            'nome_estabelecimento' => $nome ?: 'Não encontrado',
            'cnpj' => $cnpj ?: 'Não encontrado',
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
            if ($item && !empty($item['nome'])) {
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

        // Nome
        $nome = '';
        $spanTit = $xpath->query(".//span[contains(@class, 'txtTit')]", $td1);
        if ($spanTit->length > 0) {
            $nome = trim($spanTit->item(0)->nodeValue);
        }

        // Código
        $codigo = '';
        $spanCod = $xpath->query(".//span[contains(@class, 'RCod')]", $td1);
        if ($spanCod->length > 0) {
            if (preg_match('/\(Código:\s*([^\)]+)\)/', $spanCod->item(0)->nodeValue, $m)) {
                $codigo = trim($m[1]);
            }
        }

        // Quantidade
        $quantidade = 0.0;
        $spanQtde = $xpath->query(".//span[contains(@class, 'Rqtd')]", $td1);
        if ($spanQtde->length > 0) {
            $txt = strip_tags($spanQtde->item(0)->nodeValue);
            $parts = explode(':', $txt);
            $quantidade = isset($parts[1]) ? $this->parseFloat($parts[1]) : 0;
        }

        // Unidade
        $unidade = '';
        $spanUN = $xpath->query(".//span[contains(@class, 'RUN')]", $td1);
        if ($spanUN->length > 0) {
            $txt = strip_tags($spanUN->item(0)->nodeValue);
            $parts = explode(':', $txt);
            $unidade = isset($parts[1]) ? trim($parts[1]) : '';
        }

        // Valor unitário
        $valorUnitario = 0.0;
        $spanVlUnit = $xpath->query(".//span[contains(@class, 'RvlUnit')]", $td1);
        if ($spanVlUnit->length > 0) {
            $txt = strip_tags($spanVlUnit->item(0)->nodeValue);
            $parts = explode(':', $txt);
            $valorUnitario = isset($parts[1]) ? $this->parseFloat($parts[1]) : 0;
        }

        // Valor total (da segunda célula)
        $valorTotal = 0.0;
        $spanValor = $xpath->query(".//span[contains(@class, 'valor')]", $td2);
        if ($spanValor->length > 0) {
            $valorTotal = $this->parseFloat(trim($spanValor->item(0)->nodeValue));
        }

        return [
            'nome' => $nome,
            'codigo' => $codigo,
            'quantidade' => $quantidade,
            'unidade' => $unidade,
            'valor_unitario' => $valorUnitario,
            'valor_total' => $valorTotal,
        ];
    }

    private function parseTotais(DOMXPath $xpath): array
    {
        $totais = [
            'total_itens' => 0,
            'valor_total' => 0.0,
            'descontos' => 0.0,
            'valor_pago' => 0.0,
            'forma_pagamento' => '',
        ];

        // Buscar div totalNota
        $divTotal = $xpath->query("//div[@id='totalNota']")->item(0);
        if (!$divTotal) {
            // Tentar achar de outra forma
            $divTotal = $xpath->query("//div[contains(@class, 'txtRight')]")->item(0);
        }
        if (!$divTotal) return $totais;

        // Percorrer todas as divs linhaTotal
        $linhas = $xpath->query(".//div[@id='linhaTotal']", $divTotal);
        
        foreach ($linhas as $linha) {
            $labelNode = $xpath->query(".//label", $linha)->item(0);
            if (!$labelNode) continue;
            
            $labelText = trim($labelNode->nodeValue);
            
            $spanNode = $xpath->query(".//span[contains(@class, 'totalNumb')]", $linha)->item(0);
            if (!$spanNode) continue;
            
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

        // Buscar forma de pagamento
        $linhaForma = $xpath->query(".//div[@id='linhaForma']", $divTotal)->item(0);
        if ($linhaForma) {
            // Procurar a div linhaTotal seguinte que tem label.tx
            $todasLinhas = $xpath->query(".//div[@id='linhaTotal']", $divTotal);
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
            'numero' => '',
            'serie' => '',
            'data_emissao' => null,
            'chave' => '',
        ];

        // Buscar todo o texto visível para extrair os dados
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) return $dados;
        
        $texto = $body->textContent;
        
        // Extrair número
        if (preg_match('/Número:\s*(\d+)/', $texto, $m)) {
            $dados['numero'] = $m[1];
        }
        
        // Extrair série
        if (preg_match('/Série:\s*(\d+)/', $texto, $m)) {
            $dados['serie'] = $m[1];
        }
        
        // Extrair data
        if (preg_match('/Emissão:\s*(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/', $texto, $m)) {
            try {
                $dados['data_emissao'] = Carbon::createFromFormat('d/m/Y H:i:s', $m[1]);
            } catch (\Exception $e) {
                $dados['data_emissao'] = $m[1];
            }
        }
        
        // Extrair chave
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
                if (!empty($nome)) $consumidor['nome'] = $nome;
            }
        }
        
        return $consumidor;
    }

    private function parseFloat(string $value): float
    {
        $value = trim($value);
        // Remover tudo exceto números, vírgula, ponto e sinal negativo
        $value = preg_replace('/[^\d,.\-]/', '', $value);
        
        if (empty($value)) return 0.0;
        
        // Se tem vírgula e ponto, remove os pontos (separador de milhar)
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        
        // Substitui vírgula por ponto
        $value = str_replace(',', '.', $value);
        
        return (float) $value;
    }
}
