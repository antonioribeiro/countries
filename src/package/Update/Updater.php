<?php

namespace PragmaRX\Countries\Package\Update;

use Closure;
use Illuminate\Console\Command;
use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Helper;

/**
 * @codeCoverageIgnore
 */
class Updater extends Base
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    protected $command;

    /**
     * @param \PragmaRX\Coollection\Package\Coollection $countries
     */
    protected $_countries;

    /**
     * @param \PragmaRX\Countries\Package\Update\Config $config
     */
    protected $config;

    /**
     * @param \PragmaRX\Countries\Package\Update\Helper $helper
     */
    protected $helper;

    /**
     * @param \PragmaRX\Countries\Package\Update\Rinvex $rinvex
     */
    protected $rinvex;

    /**
     * @param \PragmaRX\Countries\Package\Update\Natural $natural
     */
    protected $natural;

    /**
     * @param \PragmaRX\Countries\Package\Update\Mledoze $mledoze
     */
    protected $mledoze;

    /**
     * @param \PragmaRX\Countries\Package\Update\Countries $countries
     */
    protected $countries;

    /**
     * @param \PragmaRX\Countries\Package\Update\Cities $cities
     */
    protected $cities;

    /**
     * @param \PragmaRX\Countries\Package\Update\Currencies $currencies
     */
    protected $currencies;

    /**
     * @param \PragmaRX\Countries\Package\Update\States $states
     */
    protected $states;

    /**
     * @param \PragmaRX\Countries\Package\Update\Taxes $taxes
     */
    protected $taxes;

    /**
     * @param \PragmaRX\Countries\Package\Update\Timezones $timezones
     */
    protected $timezones;

    /**
     * Updater constructor.
     */
    public function __construct()
    {
        $this->config = new Config();

        $this->helper = new Helper($this->config, $this);

        $this->natural = new Natural($this->helper, $this);

        $this->rinvex = new Rinvex($this->helper, $this->natural, $this);

        $this->states = new States($this->helper, $this->rinvex, $this);

        $this->natural->setStates($this->states);

        $this->mledoze = new Mledoze($this->helper, $this->natural, $this);

        $this->countries = new Countries($this->helper, $this->natural, $this->mledoze, $this->rinvex, $this);

        $this->cities = new Cities($this->helper, $this);

        $this->currencies = new Currencies($this->helper, $this);

        $this->taxes = new Taxes($this->helper, $this);

        $this->timezones = new Timezones($this->helper, $this);
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function getCountries()
    {
        return $this->_countries;
    }

    /**
     * @param mixed $countries
     */
    public function setCountries($countries)
    {
        $this->_countries = $countries;
    }

    /**
     * Update all data.
     *
     * @param $command
     */
    public function update($command)
    {
        $this->command = $command;

        $this->helper->downloadFiles();

        $this->countries->update();

        $this->currencies->update();

        $this->states->update();

        $this->cities->update();

        $this->taxes->update();

        $this->timezones->update();

        $this->helper->deleteTemporaryFiles();
    }

    /**
     * Add data sources to collection.
     *
     * @param \PragmaRX\Coollection\Package\Coollection $record
     * @param string $source
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function addDataSource($record, $source)
    {
        if (arrayable($record)) {
            $record = $record->toArray();
        }

        if (! isset($record[$field = 'data_sources'])) {
            $record['data_sources'] = [];
        }

        $record['data_sources'][] = $source;

        return countriesCollect($record);
    }

    /**
     * @param $result
     * @param $type
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function addRecordType($result, $type)
    {
        $result['record_type'] = $type;

        return $result;
    }

    /**
     * @param \PragmaRX\Coollection\Package\Coollection $mledoze
     * @param \PragmaRX\Coollection\Package\Coollection $natural
     * @return array
     */
    public function findCountryByAnyField($mledoze, $natural)
    {
        $fields = [
            ['cca3', 'iso_a3'],
            ['cca2', 'iso_a2'],
            ['cca2', 'wb_a2'],
            ['cca3', 'wb_a3'],
            ['name.common', 'admin'],
            ['name.common', 'name'],
            ['name.common', 'name_long'],
            ['name.common', 'formal_en'],
            ['name.official', 'admin'],
            ['name.official', 'formal_en'],
            ['name.official', 'name'],
            ['name.official', 'name_long'],
        ];

        return $this->findByFields($mledoze, $natural, $fields, 'cca3');
    }

    /**
     * @param \PragmaRX\Coollection\Package\Coollection $on
     * @param \PragmaRX\Coollection\Package\Coollection $by
     * @param $fields
     * @param $codeField
     * @return array
     */
    public function findByFields($on, $by, $fields, $codeField)
    {
        foreach ($fields as $field) {
            if (isset($by[$field[1]]) && ! is_null($found = $on->where($field[0], $by[$field[1]])->first())) {
                return [countriesCollect($found), $found->{$codeField}];
            }
        }

        return [countriesCollect(), null];
    }

    /**
     * Generate all json files.
     *
     * @param $dir
     * @param Closure|null $makeGroupKeyClosure
     * @param \PragmaRX\Coollection\Package\Coollection $records
     * @param string|null $groupKey
     */
    public function generateAllJsonFiles($dir, $makeGroupKeyClosure, $records, $groupKey)
    {
        if (! empty($groupKey)) {
            $records = $records->groupBy($groupKey);
        }

        $records->each(function (Coollection $record, $key) use ($dir, $makeGroupKeyClosure) {
            $this->helper->mkdir(dirname($file = $this->helper->makeJsonFileName($key, $dir)));

            $record = $record->mapWithKeys(function ($record, $key) use ($makeGroupKeyClosure) {
                $key = is_null($makeGroupKeyClosure)
                    ? $key
                    : $makeGroupKeyClosure($record, $key);

                $record = countriesCollect($record)->sortBy(function ($value, $key) {
                    return $key;
                });

                return empty($key)
                    ? $record
                    : [$key => $record];
            })->sortByKeysRecursive();

            file_put_contents($file, $this->helper->jsonEncode($record));
        });
    }

    /**
     * Generate json files from array.
     *
     * @param $data
     * @param $dir
     * @param Closure $normalizerClosure
     * @param Closure|null $makeGroupKeyClosure
     * @param Closure $mergeData
     * @param string $groupKey
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function generateJsonFiles($data, $dir, $normalizerClosure, $makeGroupKeyClosure, $mergeData, $groupKey = 'cca3')
    {
        $this->helper->message('Normalizing data...');

        $data = $this->normalizeData($data, $dir, $normalizerClosure);

        $this->helper->message('Merging data...');

        $data = $mergeData($data);

        $this->helper->message('Generating files...');

        $this->generateAllJsonFiles($dir, $makeGroupKeyClosure, $data, $groupKey);

        return $data;
    }

    /**
     * @param $result
     * @param $dir
     * @param $normalizerClosure
     * @return array
     */
    public function normalizeData($result, $dir, $normalizerClosure)
    {
        return cache()->remember(
            'normalizeData'.$dir, 160,
            function () use ($dir, $result, $normalizerClosure) {
                return countriesCollect($result)->map(function ($item, $key) use ($normalizerClosure) {
                    return $normalizerClosure(countriesCollect($item)->mapWithKeys(function ($value, $key) {
                        return [strtolower($key) => $value];
                    }), $key);
                });
            }
        );
    }

    /**
     * Normalize data.
     *
     * @param $item
     * @return mixed
     */
    public function normalizeStateOrCityData($item)
    {
        $fields = [
            ['cca2', 'iso_a2'],
            ['name.common', 'admin'],
            ['name.official', 'admin'],
            ['adm0_a3', 'adm0_a3'],
        ];

        list(, $countryCode) = $this->findByFields($this->_countries, $item, $fields, 'cca3');

        if (is_null($countryCode)) {
            $countryCode = $this->helper->caseForKey($item['name']);
        }

        $item['iso_a3'] = ! isset($item['iso_a3'])
            ? $countryCode
            : $item['iso_a3'];

        $item['cca3'] = $item['iso_a3'];

        $item['cca2'] = $item['iso_a2'];

        return $item;
    }

    /**
     * Command setter.
     *
     * @param \Illuminate\Console\Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }
}
