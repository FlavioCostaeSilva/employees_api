<?php

namespace Tests\Unit\Helpers;

use App\Helpers\BrazilianStates;
use Tests\TestCase;

class BrazilianStatesTest extends TestCase
{
    /** @test */
    public function it_converts_full_name_to_abbreviation()
    {
        $this->assertEquals('SP', BrazilianStates::toAbbreviation('São Paulo'));
        $this->assertEquals('RJ', BrazilianStates::toAbbreviation('Rio de Janeiro'));
        $this->assertEquals('MG', BrazilianStates::toAbbreviation('Minas Gerais'));
        $this->assertEquals('PE', BrazilianStates::toAbbreviation('Pernambuco'));
    }

    /** @test */
    public function it_converts_name_without_accents()
    {
        $this->assertEquals('SP', BrazilianStates::toAbbreviation('Sao Paulo'));
        $this->assertEquals('GO', BrazilianStates::toAbbreviation('Goias'));
        $this->assertEquals('CE', BrazilianStates::toAbbreviation('Ceara'));
        $this->assertEquals('PA', BrazilianStates::toAbbreviation('Para'));
    }

    /** @test */
    public function it_accepts_abbreviations_directly()
    {
        $this->assertEquals('SP', BrazilianStates::toAbbreviation('SP'));
        $this->assertEquals('RJ', BrazilianStates::toAbbreviation('rj'));
        $this->assertEquals('MG', BrazilianStates::toAbbreviation('Mg'));
    }

    /** @test */
    public function it_handles_mixed_case()
    {
        $this->assertEquals('SP', BrazilianStates::toAbbreviation('SÃO PAULO'));
        $this->assertEquals('RJ', BrazilianStates::toAbbreviation('rio de janeiro'));
        $this->assertEquals('PE', BrazilianStates::toAbbreviation('PeRnAmBuCo'));
    }

    /** @test */
    public function it_returns_null_for_invalid_states()
    {
        $this->assertNull(BrazilianStates::toAbbreviation('Invalid State'));
        $this->assertNull(BrazilianStates::toAbbreviation('XY'));
        $this->assertNull(BrazilianStates::toAbbreviation(''));
    }

    /** @test */
    public function it_validates_abbreviations()
    {
        $this->assertTrue(BrazilianStates::isValidAbbreviation('SP'));
        $this->assertTrue(BrazilianStates::isValidAbbreviation('sp'));
        $this->assertTrue(BrazilianStates::isValidAbbreviation('RJ'));

        $this->assertFalse(BrazilianStates::isValidAbbreviation('XY'));
        $this->assertFalse(BrazilianStates::isValidAbbreviation('ABC'));
    }

    /** @test */
    public function it_gets_full_name_from_abbreviation()
    {
        $this->assertEquals('sao paulo', BrazilianStates::getFullName('SP'));
        $this->assertEquals('rio de janeiro', BrazilianStates::getFullName('RJ'));
        $this->assertEquals('minas gerais', BrazilianStates::getFullName('MG'));

        $this->assertNull(BrazilianStates::getFullName('XY'));
    }

    /** @test */
    public function it_returns_all_abbreviations()
    {
        $abbreviations = BrazilianStates::allAbbreviations();

        $this->assertIsArray($abbreviations);
        $this->assertCount(27, $abbreviations);
        $this->assertContains('SP', $abbreviations);
        $this->assertContains('RJ', $abbreviations);
        $this->assertContains('DF', $abbreviations);
    }

    /** @test */
    public function it_returns_all_states_map()
    {
        $states = BrazilianStates::all();

        $this->assertIsArray($states);
        $this->assertCount(27, $states);
        $this->assertEquals('SP', $states['sao paulo']);
        $this->assertEquals('RJ', $states['rio de janeiro']);
    }

    /** @test */
    public function it_handles_whitespace()
    {
        $this->assertEquals('SP', BrazilianStates::toAbbreviation('  São Paulo  '));
        $this->assertEquals('RJ', BrazilianStates::toAbbreviation(' Rio de Janeiro '));
    }

    /** @test */
    public function it_converts_all_brazilian_states()
    {
        $testCases = [
            'Acre' => 'AC',
            'Alagoas' => 'AL',
            'Amapá' => 'AP',
            'Amazonas' => 'AM',
            'Bahia' => 'BA',
            'Ceará' => 'CE',
            'Distrito Federal' => 'DF',
            'Espírito Santo' => 'ES',
            'Goiás' => 'GO',
            'Maranhão' => 'MA',
            'Mato Grosso' => 'MT',
            'Mato Grosso do Sul' => 'MS',
            'Minas Gerais' => 'MG',
            'Pará' => 'PA',
            'Paraíba' => 'PB',
            'Paraná' => 'PR',
            'Pernambuco' => 'PE',
            'Piauí' => 'PI',
            'Rio de Janeiro' => 'RJ',
            'Rio Grande do Norte' => 'RN',
            'Rio Grande do Sul' => 'RS',
            'Rondônia' => 'RO',
            'Roraima' => 'RR',
            'Santa Catarina' => 'SC',
            'São Paulo' => 'SP',
            'Sergipe' => 'SE',
            'Tocantins' => 'TO',
        ];

        foreach ($testCases as $fullName => $abbreviation) {
            $this->assertEquals(
                $abbreviation,
                BrazilianStates::toAbbreviation($fullName),
                "Failed to convert '{$fullName}' to '{$abbreviation}'"
            );
        }
    }
}
