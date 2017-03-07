<?php

namespace PragmaRX\Countries\Tests\PhpUnit\Service;

use Illuminate\Support\Facades\Validator;
use PragmaRX\Countries\Facade as Countries;
use PragmaRX\Countries\Tests\PhpUnit\TestCase;

class ValidationTest extends TestCase
{
    public function testCommonNameRule()
    {
        // Valid country
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make(['country' => 'Brazil'], ['country' => 'country']);
        $this->assertTrue($validator->passes());

        // Change to Invalid country
        $validator->setData(['country' => 'NotACountry']);
        $this->assertFalse($validator->passes());
    }

    public function testCCA3Rule()
    {
        // Valid country
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make(['country' => 'BRA'], ['country' => 'cca3']);
        $this->assertTrue($validator->passes());

        // Change to Invalid country
        $validator->setData(['country' => 'NotACountry']);
        $this->assertFalse($validator->passes());
    }

    public function testLanguageRule()
    {
        // Valid language
        $validator = Validator::make(['country' => 'Portuguese'], ['country' => 'language']);
        $this->assertTrue($validator->passes());

        // Change to invalid language
        $validator->setData(['country' => 'NotACountry']);
        $this->assertFalse($validator->passes());
    }

    public function testISO639_3Rule()
    {
        // Valid language
        $validator = Validator::make(['country' => 'por'], ['country' => 'language_short']);
        $this->assertTrue($validator->passes());

        // Change to invalid language
        $validator->setData(['country' => 'NotACountry']);
        $this->assertFalse($validator->passes());
    }

    public function testISO4217Rule()
    {
        // Valid currency
        $validator = Validator::make(['country' => 'EUR'], ['country' => 'currency']);
        $this->assertTrue($validator->passes());

        // Change to invalid currency
        $validator->setData(['country' => 'NotACountry']);
        $this->assertFalse($validator->passes());
    }

    public function testWhereLanguage()
    {
        $shortName = Countries::where('ISO639_3', 'por')->count();
        $this->assertGreaterThan(0, $shortName);
        $this->assertEquals($shortName, Countries::where('language', 'Portuguese')->count());
    }

    public function testWhereCurrency()
    {
        $shortName = Countries::where('ISO4217', 'EUR')->count();
        $this->assertGreaterThan(0, $shortName);
    }

    public function testMapping()
    {
        $shortName = Countries::where('lca3', 'por')->count();
        $this->assertGreaterThan(0, $shortName);
    }

    public function testMagicCall()
    {
        $this->assertEquals(
            Countries::whereNameCommon('Brazil')->count(),
            Countries::where('name.common', 'Brazil')->count()
        );

        $this->assertEquals(
            Countries::whereISO639_3('por')->count(),
            Countries::where('ISO639_3', 'por')->count()
        );

        $this->assertEquals(
            Countries::whereLca3('por')->count(),
            Countries::where('lca3', 'por')->count()
        );
    }
}
