<?php

namespace App\Services;

use Illuminate\Support\Str;

class ProductNameNormalizer
{
    private array $abreviacoes = [
        'far' => 'farinha', 'trig' => 'trigo', 'fub' => 'fuba',
        'acuc' => 'acucar', 'caf' => 'cafe', 'leit' => 'leite',
        'queij' => 'queijo', 'marg' => 'margarina', 'sab' => 'sabao',
        'bisc' => 'biscoito', 'bol' => 'bolo', 'refr' => 'refrigerante',
        'cer' => 'cerveja', 'ag' => 'agua', 'frang' => 'frango',
        'carn' => 'carne', 'peix' => 'peixe', 'suc' => 'suco',
        'temp' => 'tempero', 'molh' => 'molho', 'det' => 'detergente',
        'shamp' => 'shampoo', 'cond' => 'condicionador', 'crem' => 'creme',
        'dent' => 'dental', 'choc' => 'chocolate', 'limp' => 'limpeza',
        'amac' => 'amaciante', 'hig' => 'higiene', 'cd' => 'creme dental',
        'pct' => 'pacote', 'cx' => 'caixa', 'fd' => 'fardo',
    ];

    private array $brandMap = [
        'tixan' => ['tixan', 'tixam', 'tix'],
        'ype' => ['ype', 'ipe'],
        'omo' => ['omo'],
        'confort' => ['confort', 'conforto'],
        'downy' => ['downy', 'dawny'],
        'veja' => ['veja'],
        'coca' => ['coca', 'cola', 'cocacola'],
        'pepsi' => ['pepsi', 'peps'],
        'nestle' => ['nestle', 'nest'],
        'parmalat' => ['parmalat', 'parmalate'],
        'piracanjuba' => ['piracanjuba', 'piracan'],
        'italac' => ['italac', 'italic'],
        'sadia' => ['sadia'],
        'perdigao' => ['perdigao', 'perdig'],
        'friboi' => ['friboi', 'fribo'],
        'swift' => ['swift'],
        'aurora' => ['aurora'],
        'colgate' => ['colgate', 'colg'],
        'oralb' => ['oralb', 'oral-b', 'oral b'],
        'bombril' => ['bombril', 'bombr'],
        'hellmanns' => ['hellmanns', 'hellmans', 'helmans'],
        'renata' => ['renata'],
        'dona' => ['dona', 'donana', 'don'],
        'camil' => ['camil'],
        'gallo' => ['gallo', 'galo'],
        'barilla' => ['barilla'],
        'bauducco' => ['bauducco', 'bauduco'],
        'visconti' => ['visconti', 'viscositi'],
        'marilan' => ['marilan'],
        'rancheiro' => ['rancheiro', 'ranch'],
        'fanta' => ['fanta'],
        'sprite' => ['sprite'],
        'kuat' => ['kuat'],
        'guarana' => ['guarana'],
        'anniela' => ['anniela', 'aniela', 'ann'],
        'dona benta' => ['dona benta', 'donabenta'],
        'dalia' => ['dalia'],
        'yoki' => ['yoki'],
        'qualy' => ['qualy'],
        'doriana' => ['doriana'],
        'knorr' => ['knorr'],
        'maggi' => ['maggi', 'mag'],
        'sazon' => ['sazon'],
        'arisco' => ['arisco'],
        'kitano' => ['kitano'],
        'ajinomoto' => ['ajinomoto', 'ajin'],
        'sol' => ['sol'],
        'cristal' => ['cristal'],
    ];

    private array $productTypeMap = [
        'farinha de trigo' => ['farinha', 'trigo', 'far trigo'],
        'fuba' => ['fuba', 'farinha milho'],
        'lava roupas' => ['sabao em po', 'sabao po', 'sab em po', 'sab po', 'sabao liquido'],
        'amaciante' => ['amac', 'amaciante'],
        'detergente' => ['detergente', 'deterg', 'lava loucas'],
        'sabonete' => ['sabonete'],
        'shampoo' => ['shampoo', 'shampo'],
        'creme dental' => ['creme dental', 'creme dent', 'cd ', 'pasta dental', 'pasta dente'],
        'arroz' => ['arroz', 'agulinha', 'parboilizado'],
        'feijao' => ['feijao', 'feija', 'carioca', 'preto'],
        'macarrao' => ['macarrao', 'espaguete', 'penne', 'fusilli', 'parafuso', 'semola'],
        'cafe' => ['cafe', 'caf', 'almofada', 'almof'],
        'leite' => ['leite', 'leit', 'condensado'],
        'refrigerante' => ['refri', 'refrigerante', 'refrig'],
        'cerveja' => ['cerveja', 'cervej', 'chopp'],
        'agua' => ['agua', 'mineral'],
        'carne' => ['carne', 'bovina', 'contrafile', 'picanha', 'alcatra', 'costela'],
        'frango' => ['frango', 'frang', 'sobrecoxa', 'coxinha', 'peito'],
        'suina' => ['suina', 'pernil', 'paleta'],
        'peixe' => ['peixe', 'tilapia'],
        'queijo' => ['queijo', 'queij', 'mussarela', 'mozarela'],
        'presunto' => ['presunto', 'presunt', 'apresuntado', 'mortadela'],
        'pao' => ['pao', 'frances', 'integral', 'forma'],
        'bolo' => ['bolo', 'mistura bolo'],
        'biscoito' => ['biscoito', 'bisc', 'rosquinha'],
        'margarina' => ['margarina', 'marg'],
        'maionese' => ['maionese', 'maion'],
        'molho' => ['molho', 'molho tomate'],
        'acucar' => ['acucar', 'acuc'],
        'oleo' => ['oleo'],
        'tempero' => ['tempero', 'temper'],
    ];

    public function normalize(string $nome): string
    {
        $nome = Str::upper(trim($nome));
        $nome = $this->removeAccents($nome);
        $nome = preg_replace('/[^A-Za-z0-9\s]/', ' ', $nome);
        $nome = preg_replace('/\s+/', ' ', trim($nome));
        return $nome;
    }

    public function expandAbbreviations(string $nome): string
    {
        $tokens = explode(' ', $nome);
        $expanded = [];
        foreach ($tokens as $token) {
            $lower = strtolower($token);
            $expanded[] = $this->abreviacoes[$lower] ?? $token;
        }
        return implode(' ', $expanded);
    }

    public function extractSignature(string $nome): string
    {
        // 1. Expandir abreviações PRIMEIRO
        $nome = $this->expandAbbreviations($nome);
        // 2. Depois normalizar
        $nome = $this->normalize($nome);
        $tokens = explode(' ', $nome);
        $tipo = $this->detectProductType($tokens);
        $marca = $this->detectBrand($tokens);
        $caracteristica = $this->extractMainFeature($tokens, $tipo, $marca);
        $quantidade = $this->extractQuantity($nome);
        return implode('|', array_filter([$tipo, $marca, $caracteristica, $quantidade]));
    }

    private function detectProductType(array $tokens): string
    {
        $text = implode(' ', $tokens);
        foreach ($this->productTypeMap as $tipo => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) return $tipo;
            }
        }
        return 'outro';
    }

    private function detectBrand(array $tokens): string
    {
        $text = implode(' ', $tokens);
        foreach ($this->brandMap as $marca => $variations) {
            foreach ($variations as $var) {
                if (in_array($var, $tokens) || stripos($text, $var) !== false) return $marca;
            }
        }
        return '';
    }

    private function extractMainFeature(array $tokens, string $tipo, string $marca): string
    {
        $stopWords = ['kg','g','l','ml','un','cx','pc','pct','fd','lt','dz','de','da','do','e','a','o','com','sem','para','por','em','sache','saco','pacote','lata','garrafa','fardo','bandeja','po','sab','lav','roupas','pet','bdj','und'];
        $brandTokens = [];
        foreach ($this->brandMap as $vars) { $brandTokens = array_merge($brandTokens, $vars); }
        $features = [];
        foreach ($tokens as $token) {
            if (strlen($token) <= 2 || is_numeric($token) || in_array(strtolower($token), $stopWords) || in_array(strtolower($token), $brandTokens)) continue;
            $features[] = $token;
        }
        $featureWords = ['maciez','neutro','tradicional','lavagem','perfeita','conforto','suave','intenso','original','clean','fresh','floral','citrus','limpeza','profunda','extraforte','forte','integral','especial','premium'];
        $foundFeatures = array_intersect(array_map('strtolower', $features), $featureWords);
        if (!empty($foundFeatures)) return implode(' ', $foundFeatures);
        return $features ? implode(' ', array_slice($features, 0, 2)) : '';
    }

    private function extractQuantity(string $nome): string
    {
        // Corrigir espaços em números (ex: "2 6KG" → "2.6KG")
        $nome = preg_replace('/(\d+)\s+(\d+)\s*(kg|g|l|ml)/i', '$1.$2$3', $nome);
        
        $patterns = [
            '/(\d+[.,]?\d*)\s*(kg|quilo|kilo)/i',
            '/(\d+[.,]?\d*)\s*(g|gr|grama)/i',
            '/(\d+[.,]?\d*)\s*(l|lt|ltr|litro)/i',
            '/(\d+[.,]?\d*)\s*(ml|mililitro)/i',
            '/(\d+[.,]?\d*)\s*(un|und|unid)/i',
            '/(\d+[.,]?\d*)\s*(cx|caixa)/i',
            '/(\d+[.,]?\d*)\s*(pct|pacote|pac)/i',
            '/(\d+[.,]?\d*)\s*(fd|fardo)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $nome, $matches)) {
                return str_replace(',', '.', $matches[1]) . strtoupper(substr($matches[2], 0, 2));
            }
        }
        return '';
    }

    public function compare(string $nome1, string $nome2): float
    {
        $sig1 = $this->extractSignature($nome1);
        $sig2 = $this->extractSignature($nome2);
        if ($sig1 === $sig2) return 1.0;
        $parts1 = explode('|', $sig1); $parts2 = explode('|', $sig2);
        $score = 0; $weights = [0.35, 0.35, 0.20, 0.10];
        for ($i = 0; $i < min(count($parts1), count($parts2), 4); $i++) {
            if (!empty($parts1[$i]) && !empty($parts2[$i]) && $parts1[$i] === $parts2[$i]) $score += $weights[$i];
        }
        if (!empty($parts1[0]) && $parts1[0] === $parts2[0] && !empty($parts1[1]) && $parts1[1] === $parts2[1]) $score = max($score, 0.75);
        return $score;
    }

    private function removeAccents(string $text): string
    {
        $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
        return strtr(strtolower($text), $map);
    }
}
