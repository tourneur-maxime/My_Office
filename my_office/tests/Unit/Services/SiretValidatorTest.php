<?php

namespace Tests\Unit\Services;

use App\Services\SiretValidator;
use Tests\TestCase;

class SiretValidatorTest extends TestCase
{
    /**
     * Test that a valid 14-digit SIRET passes validation
     *
     * @test
     */
    public function it_validates_correct_14_digit_siret_with_valid_luhn()
    {
        // SIRET: 73282932000074 (valid format and Luhn checksum)
        $result = SiretValidator::validate('73282932000074');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /**
     * Test that SIRET with less than 14 digits fails validation
     *
     * @test
     */
    public function it_fails_validation_for_siret_with_less_than_14_digits()
    {
        $result = SiretValidator::validate('1234567890');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le numéro SIRET doit contenir 14 chiffres', $result['error']);
    }

    /**
     * Test that SIRET with more than 14 digits fails validation
     *
     * @test
     */
    public function it_fails_validation_for_siret_with_more_than_14_digits()
    {
        $result = SiretValidator::validate('123456789012345');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le numéro SIRET doit contenir 14 chiffres', $result['error']);
    }

    /**
     * Test that SIRET with non-numeric characters fails validation
     *
     * @test
     */
    public function it_fails_validation_for_siret_with_non_numeric_characters()
    {
        $result = SiretValidator::validate('1234567890ABCD');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le numéro SIRET doit contenir 14 chiffres', $result['error']);
    }

    /**
     * Test that SIRET with special characters fails validation
     *
     * @test
     */
    public function it_fails_validation_for_siret_with_special_characters()
    {
        $result = SiretValidator::validate('12345678-90123');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le numéro SIRET doit contenir 14 chiffres', $result['error']);
    }

    /**
     * Test that SIRET with spaces fails validation
     *
     * @test
     */
    public function it_fails_validation_for_siret_with_spaces()
    {
        $result = SiretValidator::validate('123 456 789 012');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le numéro SIRET doit contenir 14 chiffres', $result['error']);
    }

    /**
     * Test that empty string is allowed (optional field)
     *
     * @test
     */
    public function it_allows_empty_string()
    {
        $result = SiretValidator::validate('');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /**
     * Test that null value is allowed (optional field)
     *
     * @test
     */
    public function it_allows_null_value()
    {
        $result = SiretValidator::validate(null);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /**
     * Test that SIRET with valid format but invalid Luhn checksum fails
     *
     * @test
     */
    public function it_fails_validation_for_invalid_luhn_checksum()
    {
        // 14 digits but invalid Luhn checksum
        $result = SiretValidator::validate('12345678901234');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Le numéro SIRET n'est pas valide", $result['error']);
    }

    /**
     * Test Luhn algorithm with another valid SIRET
     *
     * @test
     */
    public function it_validates_multiple_valid_siret_numbers()
    {
        // Test multiple valid SIRETs
        $validSirets = [
            '73282932000074',
            '44306184100047',
        ];

        foreach ($validSirets as $siret) {
            $result = SiretValidator::validate($siret);
            $this->assertTrue($result['valid'], "SIRET {$siret} should be valid");
            $this->assertNull($result['error']);
        }
    }
}
