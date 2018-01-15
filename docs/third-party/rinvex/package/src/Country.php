<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Country Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Country Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

namespace Rinvex\Country;

use Exception;

class Country
{
    /**
     * The attributes array.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create a new Country instance.
     *
     * @param array $attributes
     *
     * @throws \Exception
     */
    public function __construct($attributes)
    {
        // Set the attributes
        $this->setAttributes($attributes);

        // Check required mandatory attributes
        if (empty($this->getName()) || empty($this->getOfficialName())
            || empty($this->getNativeName()) || empty($this->getNativeOfficialName())
            || empty($this->getIsoAlpha2()) || empty($this->getIsoAlpha3())) {
            throw new Exception('Missing mandatory country attributes!');
        }
    }

    /**
     * Set the attributes.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get the attributes.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set single attribute.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an item from attributes array using "dot" notation.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $array = $this->attributes;

        if (is_null($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Get the common name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->get('name.common') ?: $this->get('name');
    }

    /**
     * Get the official name.
     *
     * @return string|null
     */
    public function getOfficialName()
    {
        return $this->get('name.official') ?: $this->get('official_name');
    }

    /**
     * Get the given native name or fallback to first native name.
     *
     * @param string|null $language
     *
     * @return string|null
     */
    public function getNativeName($language = null)
    {
        $language = $language ? mb_strtolower($language) : null;

        return $this->get("name.native.{$language}.common")
            ?: (current($this->get('name.native', []))['common'] ?: $this->get('native_name'));
    }

    /**
     * Get the given native official name or fallback to first native official name.
     *
     * @param string|null $language
     *
     * @return string|null
     */
    public function getNativeOfficialName($language = null)
    {
        $language = $language ? mb_strtolower($language) : null;

        return $this->get("name.native.{$language}.official")
            ?: (current($this->get('name.native', []))['official'] ?: $this->get('native_official_name'));
    }

    /**
     * Get the native names.
     *
     * @return array|null
     */
    public function getNativeNames()
    {
        return $this->get('name.native');
    }

    /**
     * Get the demonym.
     *
     * @return string|null
     */
    public function getDemonym()
    {
        return $this->get('demonym');
    }

    /**
     * Get the capital.
     *
     * @return string|null
     */
    public function getCapital()
    {
        return $this->get('capital');
    }

    /**
     * Get the ISO 3166-1 alpha2.
     *
     * @return string|null
     */
    public function getIsoAlpha2()
    {
        return $this->get('iso_3166_1_alpha2');
    }

    /**
     * Get the ISO 3166-1 alpha3.
     *
     * @return string|null
     */
    public function getIsoAlpha3()
    {
        return $this->get('iso_3166_1_alpha3');
    }

    /**
     * Get the ISO 3166-1 numeric.
     *
     * @return string|null
     */
    public function getIsoNumeric()
    {
        return $this->get('iso_3166_1_numeric');
    }

    /**
     * Get the given currency or fallback to first currency.
     *
     * @param string|null $currency
     *
     * @return string|null
     */
    public function getCurrency($currency = null)
    {
        $currency = $currency ? mb_strtoupper($currency) : null;

        return $this->get("currency.{$currency}") ?: (current($this->get('currency', [])) ?: null);
    }

    /**
     * Get the currencies.
     *
     * @return array|null
     */
    public function getCurrencies()
    {
        return $this->get('currency');
    }

    /**
     * Get the TLD.
     *
     * @return string|null
     */
    public function getTld()
    {
        return current($this->get('tld', [])) ?: null;
    }

    /**
     * Get the TLDs.
     *
     * @return array|null
     */
    public function getTlds()
    {
        return $this->get('tld');
    }

    /**
     * Get the alternative spellings.
     *
     * @return array|null
     */
    public function getAltSpellings()
    {
        return $this->get('alt_spellings');
    }

    /**
     * Get the given language or fallback to first language.
     *
     * @param string|null $language
     *
     * @return string|null
     */
    public function getLanguage($language = null)
    {
        $language = $language ? mb_strtoupper($language) : null;

        return $this->get("languages.{$language}") ?: (current($this->get('languages', [])) ?: null);
    }

    /**
     * Get the languages.
     *
     * @return array|null
     */
    public function getLanguages()
    {
        return $this->get('languages');
    }

    /**
     * Get the translations.
     *
     * @return array
     */
    public function getTranslations()
    {
        // Get english name
        $name = [
            'eng' => [
                'common' => $this->getName(),
                'official' => $this->getOfficialName(),
            ],
        ];

        // Get native names
        $natives = $this->getNativeNames() ?: [];

        // Get other translations
        $file = __DIR__.'/../resources/translations/'.mb_strtolower($this->getIsoAlpha2()).'.json';
        $translations = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

        // Merge all names together
        $result = array_merge($translations, $natives, $name);

        // Sort alphabetically
        ksort($result);

        return $result;
    }

    /**
     * Get the translation.
     *
     * @param string|null $language
     *
     * @return array
     */
    public function getTranslation($language = null)
    {
        return isset($this->getTranslations()[$language])
            ? $this->getTranslations()[$language] : current($this->getTranslations());
    }

    /**
     * Get the geodata.
     *
     * @return array|null
     */
    public function getGeodata()
    {
        return $this->get('geo');
    }

    /**
     * Get the continent.
     *
     * @return string|null
     */
    public function getContinent()
    {
        return current($this->get('geo.continent', [])) ?: null;
    }

    /**
     * Determine whether the country uses postal code.
     *
     * @return bool|null
     */
    public function usesPostalCode()
    {
        return $this->get('geo.postal_code');
    }

    /**
     * Get the latitude.
     *
     * @return string|null
     */
    public function getLatitude()
    {
        return $this->get('geo.latitude');
    }

    /**
     * Get the longitude.
     *
     * @return string|null
     */
    public function getLongitude()
    {
        return $this->get('geo.longitude');
    }

    /**
     * Get the described latitude.
     *
     * @return string|null
     */
    public function getLatitudeDesc()
    {
        return $this->get('geo.latitude_desc');
    }

    /**
     * Get the described longitude.
     *
     * @return string|null
     */
    public function getLongitudeDesc()
    {
        return $this->get('geo.longitude_desc');
    }

    /**
     * Get the maximum latitude.
     *
     * @return string|null
     */
    public function getMaxLatitude()
    {
        return $this->get('geo.max_latitude');
    }

    /**
     * Get the maximum longitude.
     *
     * @return string|null
     */
    public function getMaxLongitude()
    {
        return $this->get('geo.max_longitude');
    }

    /**
     * Get the minimum latitude.
     *
     * @return string|null
     */
    public function getMinLatitude()
    {
        return $this->get('geo.min_latitude');
    }

    /**
     * Get the minimum longitude.
     *
     * @return string|null
     */
    public function getMinLongitude()
    {
        return $this->get('geo.min_longitude');
    }

    /**
     * Get the area.
     *
     * @return int|null
     */
    public function getArea()
    {
        return $this->get('geo.area');
    }

    /**
     * Get the region.
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->get('geo.region');
    }

    /**
     * Get the subregion.
     *
     * @return string|null
     */
    public function getSubregion()
    {
        return $this->get('geo.subregion');
    }

    /**
     * Get the world region.
     *
     * @return string|null
     */
    public function getWorldRegion()
    {
        return $this->get('geo.world_region');
    }

    /**
     * Get the region code.
     *
     * @return string|null
     */
    public function getRegionCode()
    {
        return $this->get('geo.region_code');
    }

    /**
     * Get the subregion code.
     *
     * @return string|null
     */
    public function getSubregionCode()
    {
        return $this->get('geo.subregion_code');
    }

    /**
     * Check the landlock status.
     *
     * @return bool|null
     */
    public function isLandlocked()
    {
        return $this->get('geo.landlocked');
    }

    /**
     * Get the borders.
     *
     * @return array|null
     */
    public function getBorders()
    {
        return $this->get('geo.borders');
    }

    /**
     * Determine whether the country is independent.
     *
     * @return string|null
     */
    public function isIndependent()
    {
        return $this->get('geo.independent');
    }

    /**
     * Get the given calling code or fallback to first calling code.
     *
     * @return string|null
     */
    public function getCallingCode()
    {
        return current($this->get('dialling.calling_code', [])) ?: (current($this->get('calling_code', [])) ?: null);
    }

    /**
     * Get the calling codes.
     *
     * @return array|null
     */
    public function getCallingCodes()
    {
        return $this->get('dialling.calling_code');
    }

    /**
     * Get the national prefix.
     *
     * @return string|null
     */
    public function getNationalPrefix()
    {
        return $this->get('dialling.national_prefix');
    }

    /**
     * Get the national number length.
     *
     * @return int|null
     */
    public function getNationalNumberLength()
    {
        return current($this->get('dialling.national_number_lengths', [])) ?: null;
    }

    /**
     * Get the national number lengths.
     *
     * @return array|null
     */
    public function getNationalNumberLengths()
    {
        return $this->get('dialling.national_number_lengths');
    }

    /**
     * Get the national destination code length.
     *
     * @return int|null
     */
    public function getNationalDestinationCodeLength()
    {
        return current($this->get('dialling.national_destination_code_lengths', [])) ?: null;
    }

    /**
     * Get the national destination code lengths.
     *
     * @return array|null
     */
    public function getnationaldestinationcodelengths()
    {
        return $this->get('dialling.national_destination_code_lengths');
    }

    /**
     * Get the international prefix.
     *
     * @return string|null
     */
    public function getInternationalPrefix()
    {
        return $this->get('dialling.international_prefix');
    }

    /**
     * Get the extras.
     *
     * @return array|null
     */
    public function getExtra()
    {
        return $this->get('extra');
    }

    /**
     * Get the geonameid.
     *
     * @return int|null
     */
    public function getGeonameid()
    {
        return $this->get('extra.geonameid');
    }

    /**
     * Get the edgar code.
     *
     * @return string|null
     */
    public function getEdgar()
    {
        return $this->get('extra.edgar');
    }

    /**
     * Get the itu code.
     *
     * @return string|null
     */
    public function getItu()
    {
        return $this->get('extra.itu');
    }

    /**
     * Get the marc code.
     *
     * @return string|null
     */
    public function getMarc()
    {
        return $this->get('extra.marc');
    }

    /**
     * Get the wmo code.
     *
     * @return string|null
     */
    public function getWmo()
    {
        return $this->get('extra.wmo');
    }

    /**
     * Get the ds code.
     *
     * @return string|null
     */
    public function getDs()
    {
        return $this->get('extra.ds');
    }

    /**
     * Get the fifa code.
     *
     * @return string|null
     */
    public function getFifa()
    {
        return $this->get('extra.fifa');
    }

    /**
     * Get the fips code.
     *
     * @return string|null
     */
    public function getFips()
    {
        return $this->get('extra.fips');
    }

    /**
     * Get the gaul code.
     *
     * @return int|null
     */
    public function getGaul()
    {
        return $this->get('extra.gaul');
    }

    /**
     * Get the ioc code.
     *
     * @return string|null
     */
    public function getIoc()
    {
        return $this->get('extra.ioc');
    }

    /**
     * Get the cowc code.
     *
     * @return string|null
     */
    public function getCowc()
    {
        return $this->get('extra.cowc');
    }

    /**
     * Get the cown code.
     *
     * @return int|null
     */
    public function getCown()
    {
        return $this->get('extra.cown');
    }

    /**
     * Get the fao code.
     *
     * @return int|null
     */
    public function getFao()
    {
        return $this->get('extra.fao');
    }

    /**
     * Get the imf code.
     *
     * @return int|null
     */
    public function getImf()
    {
        return $this->get('extra.imf');
    }

    /**
     * Get the ar5 code.
     *
     * @return string|null
     */
    public function getAr5()
    {
        return $this->get('extra.ar5');
    }

    /**
     * Get the address format.
     *
     * @return string|null
     */
    public function getAddressFormat()
    {
        return $this->get('extra.address_format');
    }

    /**
     * Determine whether the country is EU member.
     *
     * @return bool|null
     */
    public function isEuMember()
    {
        return $this->get('extra.eu_member');
    }

    /**
     * Get the VAT rates.
     *
     * @return array|null
     */
    public function getVatRates()
    {
        return $this->get('extra.vat_rates');
    }

    /**
     * Get the emoji.
     *
     * @return array|null
     */
    public function getEmoji()
    {
        return $this->get('extra.emoji') ?: $this->get('emoji');
    }

    /**
     * Get the geographic data structure.
     *
     * @return string|null
     */
    public function getGeoJson()
    {
        if (! ($code = $this->getIsoAlpha2())) {
            return;
        }

        return file_exists($file = __DIR__.'/../resources/geodata/'.mb_strtolower($code).'.json') ? file_get_contents($file) : null;
    }

    /**
     * Get the flag.
     *
     * @return string|null
     */
    public function getFlag()
    {
        if (! ($code = $this->getIsoAlpha2())) {
            return;
        }

        return file_exists($file = __DIR__.'/../resources/flags/'.mb_strtolower($code).'.svg') ? file_get_contents($file) : null;
    }

    /**
     * Get the divisions.
     *
     * @return array|null
     */
    public function getDivisions()
    {
        if (! ($code = $this->getIsoAlpha2())) {
            return;
        }

        return file_exists($file = __DIR__.'/../resources/divisions/'.mb_strtolower($code).'.json') ? json_decode(file_get_contents($file), true) : null;
    }

    /**
     * Get the divisions.
     *
     * @param string $division
     *
     * @return array|null
     */
    public function getDivision($division)
    {
        return ! empty($this->getDivisions()) && isset($this->getDivisions()[$division])
            ? $this->getDivisions()[$division] : null;
    }
}
