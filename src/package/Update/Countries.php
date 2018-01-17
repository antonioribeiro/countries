<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\General;
use PragmaRX\Countries\Package\Facade as CountriesService;

class Countries extends Base
{
    /**
     * @var General
     */
    protected $general;

    /**
     * @var Updater
     */
    protected $updater;

    /**
     * @var Mledoze
     */
    protected $mledoze;

    /**
     * @var Natural
     */
    protected $natural;

    /**
     * @var Rinvex
     */
    private $rinvex;

    /**
     * Rinvex constructor.
     *
     * @param General $general
     * @param Natural $natural
     * @param Mledoze $mledoze
     * @param Rinvex $rinvex
     * @param Updater $updater
     */
    public function __construct(General $general, Natural $natural, Mledoze $mledoze, Rinvex $rinvex, Updater $updater)
    {
        $this->general = $general;

        $this->updater = $updater;

        $this->mledoze = $mledoze;

        $this->natural = $natural;

        $this->rinvex = $rinvex;
    }

    /**
     * Update countries.
     */
    public function update()
    {
        $this->general->progress('Updating countries...');

        $dataDir = '/countries/default/';

        $this->updater->setCountries(cache()->remember('updateCountries->buildCountriesCoollection', 160, function () use ($dataDir) {
            $this->general->eraseDataDir($dataDir);

            return $this->buildCountriesCoollection($dataDir);
        }));

        $this->general->putFile(
            $this->general->makeJsonFileName('_all_countries', $dataDir),
            $this->updater->getCountries()->toJson(JSON_PRETTY_PRINT)
        );

        $this->general->progress('Generated '.count($this->updater->getCountries()).' countries.');

        $this->updater->setCountries(CountriesService::all());
    }

    /**
     * Build countries collection.
     *
     * @param $dataDir
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function buildCountriesCoollection($dataDir)
    {
        $this->general->message('Processing countries...');

        $mledoze = $this->mledoze->loadMledozeCountries();

        $countries = countriesCollect($this->general->loadShapeFile('third-party/natural_earth/ne_10m_admin_0_countries'))->map(function ($country) {
            return $this->natural->fixNaturalOddCountries($country);
        })->mapWithKeys(function ($natural) use ($mledoze, $dataDir) {
            list($mledoze, $countryCode) = $this->mledoze->findMledozeCountry($mledoze, $natural);

            $natural = countriesCollect($natural)->mapWithKeys(function ($country, $key) {
                return [strtolower($key) => $country];
            });

            if (is_null($countryCode)) {
                $result = $this->mledoze->fillMledozeFields($natural);

                $countryCode = $natural['adm0_a3'];
            } else {
                $result = $this->mledoze->mergeWithMledoze($mledoze, $natural);
            }

            $result = $this->rinvex->mergeWithRinvex($result,
                $this->rinvex->findRinvexCountry($result),
                $this->rinvex->findRinvexTranslations($result)
            );

            $result = $this->clearCountryCurrencies($result);

            $result = $this->updater->addDataSource($result, 'natural');

            $result = $this->updater->addRecordType($result, 'country');

            $result = $result->sortByKeysRecursive();

            $this->general->putFile(
                $this->general->makeJsonFileName(strtolower($countryCode), $dataDir),
                $result->toJson(JSON_PRETTY_PRINT)
            );

            return [$countryCode => $result];
        });

        return $mledoze->overwrite($countries);
    }

    public function clearCountryCurrencies($country)
    {
        if (isset($country['currency']) && ! is_null($country['currency'])) {
            $country['currencies'] = array_keys($country['currency']);

            unset($country['currency']);
        } else {
            $country['currencies'] = [];
        }

        return $country;
    }
}
