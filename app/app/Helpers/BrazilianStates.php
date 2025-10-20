<?php

namespace App\Helpers;

class BrazilianStates
{
    private const STATE_MAP = [
        'acre' => 'AC',
        'alagoas' => 'AL',
        'amapa' => 'AP',
        'amazonas' => 'AM',
        'bahia' => 'BA',
        'ceara' => 'CE',
        'distrito federal' => 'DF',
        'espirito santo' => 'ES',
        'goias' => 'GO',
        'maranhao' => 'MA',
        'mato grosso' => 'MT',
        'mato grosso do sul' => 'MS',
        'minas gerais' => 'MG',
        'para' => 'PA',
        'paraiba' => 'PB',
        'parana' => 'PR',
        'pernambuco' => 'PE',
        'piaui' => 'PI',
        'rio de janeiro' => 'RJ',
        'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS',
        'rondonia' => 'RO',
        'roraima' => 'RR',
        'santa catarina' => 'SC',
        'sao paulo' => 'SP',
        'sergipe' => 'SE',
        'tocantins' => 'TO',
    ];


    private const VALID_ABBREVIATIONS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
        'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];


    public static function toAbbreviation(string $state): ?string
    {
        $state = trim($state);


        if (empty($state)) {
            return null;
        }

        $stateUpper = strtoupper($state);
        if (strlen($state) === 2 && in_array($stateUpper, self::VALID_ABBREVIATIONS)) {
            return $stateUpper;
        }

        $stateNormalized = self::removeAccents(mb_strtolower($state));


        return self::STATE_MAP[$stateNormalized] ?? null;
    }

    public static function isValidAbbreviation(string $abbreviation): bool
    {
        return in_array(strtoupper($abbreviation), self::VALID_ABBREVIATIONS);
    }


    public static function getFullName(string $abbreviation): ?string
    {
        $abbreviation = strtoupper($abbreviation);

        $flipped = array_flip(self::STATE_MAP);

        return $flipped[$abbreviation] ?? null;
    }


    public static function allAbbreviations(): array
    {
        return self::VALID_ABBREVIATIONS;
    }


    public static function all(): array
    {
        return self::STATE_MAP;
    }


    private static function removeAccents(string $string): string
    {
        $unwanted = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];

        return strtr($string, $unwanted);
    }
}
