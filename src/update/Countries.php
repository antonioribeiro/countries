<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Countries\Package\Countries as CountriesService;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;
use PragmaRX\Countries\Package\Services\Config;
use PragmaRX\Countries\Package\Support\Base;

class Countries extends Base
{
    /**
     * @var Helper
     */
    protected $helper;

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
     * @var Cache
     */
    private $cache;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Natural $natural
     * @param Mledoze $mledoze
     * @param Rinvex $rinvex
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Natural $natural, Mledoze $mledoze, Rinvex $rinvex, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;

        $this->mledoze = $mledoze;

        $this->natural = $natural;

        $this->rinvex = $rinvex;

        $this->cache = new Cache(new Config());
    }

    /**
     * Update countries.
     */
    public function update()
    {
        $this->helper->progress('--- Countries');

        $dataDir = '/countries/default/';

        $this->updater->setCountries($this->cache->remember('updateCountries->buildCountriesCoollection', 160, function () use ($dataDir) {
            $this->helper->eraseDataDir($dataDir);

            return $this->buildCountriesCoollection($dataDir);
        }));

        $this->helper->putFile(
            $this->helper->makeJsonFileName('_all_countries', $dataDir),
            $this->updater->getCountries()->toJson(JSON_PRETTY_PRINT)
        );

        $this->helper->progress('Generated '.count($this->updater->getCountries()).' countries.');

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
        $this->helper->message('Processing countries...');

        $mledoze = $this->mledoze->loadMledozeCountries();

        $shapeFile = $this->helper->loadShapeFile('third-party/natural_earth/ne_10m_admin_0_countries');

        $this->helper->message('Generating countries...');

        $countries = coollect($shapeFile)->map(function ($country) {
            return $this->natural->fixNaturalOddCountries($country);
        })->mapWithKeys(function ($natural) use ($mledoze, $dataDir) {
            [$mledoze, $countryCode] = $this->mledoze->findMledozeCountry($mledoze, $natural);

            $natural = coollect($natural)->mapWithKeys(function ($country, $key) {
                return [strtolower($key) => $country];
            });

            if (is_null($countryCode)) {
                $result = $this->mledoze->fillMledozeFields($natural);

                $countryCode = $natural['adm0_a3'];
            } else {
                $result = $this->mledoze->mergeWithMledoze($mledoze, $natural);
            }

            $result = $this->rinvex->mergeWithRinvex(
                $result,
                $this->rinvex->findRinvexCountry($result),
                $this->rinvex->findRinvexTranslations($result)
            );

            $result = $this->clearCountryCurrencies($result);

            $result = $this->updater->addDataSource($result, 'natural');

            $result = $this->updater->addRecordType($result, 'country');

            $result = $result->sortByKeysRecursive();

            $this->helper->putFile(
                $this->helper->makeJsonFileName(strtolower($countryCode), $dataDir),
                $result->toJson(JSON_PRETTY_PRINT)
            );

            $this->helper->message($result['name']['common']);

            return [$countryCode => $result];
        });

        return $mledoze->overwrite($countries);
    }

    public function clearCountryCurrencies($country)
    {
        if (isset($country['currency']) && ! is_null($country['currency'])) {
            $country['currencies'] = $country['currency']->keys();

            unset($country['currency']);
        } else {
            $country['currencies'] = [];
        }

        return $country;
    }
}
