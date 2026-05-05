<?php

namespace App\Services;

use Illuminate\Support\Str;

class ProductNameNormalizer
{
    private array $brandMap = [
        'tixan' => ['tixan', 'tixam', 'tix'],
        'ype' => ['ype', 'ipe', 'ypê'],
        'omo' => ['omo'],
        'confort' => ['confort', 'conforto'],
        'downy' => ['downy', 'dawny'],
        'veja' => ['veja'],
        'coca' => ['coca', 'cola', 'cocacola'],
        'pepsi' => ['pepsi', 'peps'],
        'nestle' => ['nestle', 'nestlé', 'nest'],
        'parmalat' => ['parmalat', 'parmalate'],
        'piracanjuba' => ['piracanjuba', 'piracan'],
        'italac' => ['italac', 'italaque'],
        'sadia' => ['sadia'],
        'perdigao' => ['perdigao', 'perdig', 'perdigão'],
        'friboi' => ['friboi', 'fribo'],
        'swift' => ['swift'],
        'aurora' => ['aurora'],
        'colgate' => ['colgate', 'colg'],
        'oralb' => ['oralb', 'oral-b', 'oral b'],
        'bombril' => ['bombril', 'bombr'],
        'hellmanns' => ['hellmanns', 'hellmans', 'helmans'],
        'renata' => ['renata'],
        'dona' => ['dona', 'donana'],
        'camil' => ['camil'],
        'prato fino' => ['prato fino', 'pratofino'],
        'tio joão' => ['tio joao', 'tio joão', 'tiojoao'],
        'gallo' => ['gallo', 'galo'],
        'barilla' => ['barilla'],
        'piraque' => ['piraque', 'piraquê'],
        'bauducco' => ['bauducco', 'bauduco'],
        'visconti' => ['visconti', 'viscositi'],
        'marilan' => ['marilan'],
        'rancheiro' => ['rancheiro', 'ranch'],
        'fanta' => ['fanta'],
        'sprite' => ['sprite'],
        'kuat' => ['kuat'],
        'guarana' => ['guarana', 'guaraná'],
        'elege' => ['elege'],
        'batavo' => ['batavo'],
        'del monte' => ['del monte', 'delmonte'],
        'dalia' => ['dalia', 'dália'],
        'yoki' => ['yoki'],
        'italac' => ['italac', 'italic'],
        'queiro' => ['queiro', 'queiró'],
        'renata' => ['renata'],
        'macarrao galo' => ['macarrao galo', 'macarrão galo', 'galo semola'],
    ];

    private array $productTypeMap = [
        'lava roupas' => ['lav', 'lava', 'roupas', 'roupa', 'sabao em po', 'sabão em pó', 'sabao po', 'sab em po', 'sab po'],
        'amaciante' => ['amac', 'amaciante', 'amaciant', 'confort', 'downy'],
        'detergente' => ['detergente', 'deterg', 'limpol', 'lava loucas', 'lava louça', 'loucas'],
        'sabonete' => ['sabonete', 'sabonet'],
        'shampoo' => ['shampoo', 'shampo', 'xampu'],
        'creme dental' => ['creme dental', 'creme dent', 'cd ', 'pasta dental', 'pasta de dente'],
        'arroz' => ['arroz', 'arrozagulinha', 'agulinha', 'parboilizado', 'parboil'],
        'feijao' => ['feijao', 'feijão', 'feija', 'carioca', 'preto'],
        'macarrao' => ['macarrao', 'macarrão', 'espaguete', 'penne', 'fusilli', 'parafuso', 'semola'],
        'cafe' => ['cafe', 'café', 'caf', 'almofada', 'almof'],
        'leite' => ['leite', 'leit', 'liquido', 'po', 'condensado'],
        'refrigerante' => ['refri', 'refrigerante', 'refrig'],
        'cerveja' => ['cerveja', 'cervej', 'chopp'],
        'agua' => ['agua', 'água', 'mineral'],
        'carne' => ['carne', 'bovina', 'bovino', 'contrafile', 'picanha', 'alcatra', 'costela'],
        'frango' => ['frango', 'frang', 'sobrecoxa', 'coxinha', 'peito', 'file'],
        'suina' => ['suina', 'suíno', 'pernil', 'paleta'],
        'peixe' => ['peixe', 'tilapia', 'tilápia'],
        'queijo' => ['queijo', 'queij', 'mussarela', 'mozarela'],
        'presunto' => ['presunto', 'presunt', 'apresuntado', 'mortadela'],
        'pao' => ['pao', 'pão', 'frances', 'integral', 'forma'],
        'bolo' => ['bolo', 'mistura bolo', 'mistura'],
        'biscoito' => ['biscoito', 'bisc', 'rosquinha'],
        'margarina' => ['margarina', 'marg'],
        'maionese' => ['maionese', 'maion'],
        'molho' => ['molho', 'tomate', 'molho tomate'],
        'farinha' => ['farinha', 'trigo', 'farinha trigo'],
        'acucar' => ['acucar', 'açúcar'],
        'sal' => ['sal'],
        'oleo' => ['oleo', 'óleo'],
        'vinagre' => ['vinagre'],
        'tempero' => ['tempero', 'temper'],
    ];

    private array $unitPatterns = [
        '/(\d+[.,]?\d*)\s*(kg|quilo|kilo|quilos|kilos)/i',
        '/(\d+[.,]?\d*)\s*(g|gr|grama|gramas)/i',
        '/(\d+[.,]?\d*)\s*(l|lt|ltr|litro|litros)/i',
        '/(\d+[.,]?\d*)\s*(ml|mililitro)/i',
        '/(\d+[.,]?\d*)\s*(un|und|unid|unidade)/i',
        '/(\d+[.,]?\d*)\s*(cx|caixa)/i',
        '/(\d+[.,]?\d*)\s*(pct|pacote|pac)/i',
        '/(\d+[.,]?\d*)\s*(fd|fardo)/i',
    ];

    public function normalize(string $nome): string
    {
        $nome = Str::upper(trim($nome));
        $nome = $this->removeAccents($nome);
        $nome = preg_replace('/[^A-Z0-9\s]/', ' ', $nome);
        $nome = preg_replace('/\s+/', ' ', trim($nome));
        return $nome;
    }

    public function extractSignature(string $nome): string
    {
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
        $bestMatch = ''; $bestScore = 0;
        foreach ($this->productTypeMap as $tipo => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) { if (in_array($keyword, $tokens)) $score += 3; }
            $text = implode(' ', $tokens);
            foreach ($keywords as $keyword) { if (stripos($text, $keyword) !== false) $score += 1; }
            if ($score > $bestScore) { $bestScore = $score; $bestMatch = $tipo; }
        }
        return $bestMatch ?: 'outro';
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
        $featureWords = ['maciez','neutro','tradicional','lavagem','perfeita','conforto','suave','intenso','original','clean','fresh','floral','citrus','limpeza','profunda','extraforte','forte'];
        $foundFeatures = array_intersect(array_map('strtolower', $features), $featureWords);
        if (!empty($foundFeatures)) return implode(' ', $foundFeatures);
        return $features ? implode(' ', array_slice($features, 0, 2)) : '';
    }

    private function extractQuantity(string $nome): string
    {
        foreach ($this->unitPatterns as $pattern) {
            if (preg_match($pattern, $nome, $matches)) return str_replace(',', '.', $matches[1]) . strtoupper(substr($matches[2], 0, 2));
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
