<?php

namespace PragmaRX\Countries\Update;

use Closure;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Contracts\Config;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;
use PragmaRX\Countries\Package\Services\Command;
use PragmaRX\Countries\Package\Services\Config as ConfigService;
use PragmaRX\Countries\Package\Support\Base;

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
     * @param Config $config
     */
    protected $config;

    /**
     * @param \PragmaRX\Countries\Update\Helper $helper
     */
    protected $helper;

    /**
     * @param \PragmaRX\Countries\Update\Rinvex $rinvex
     */
    protected $rinvex;

    /**
     * @param \PragmaRX\Countries\Update\Natural $natural
     */
    protected $natural;

    /**
     * @param \PragmaRX\Countries\Update\Mledoze $mledoze
     */
    protected $mledoze;

    /**
     * @param \PragmaRX\Countries\Update\Countries $countries
     */
    protected $countries;

    /**
     * @param \PragmaRX\Countries\Update\Cities $cities
     */
    protected $cities;

    /**
     * @param \PragmaRX\Countries\Update\Currencies $currencies
     */
    protected $currencies;

    /**
     * @param \PragmaRX\Countries\Update\States $states
     */
    protected $states;

    /**
     * @param \PragmaRX\Countries\Update\Taxes $taxes
     */
    protected $taxes;

    /**
     * @param \PragmaRX\Countries\Update\Timezones $timezones
     */
    protected $timezones;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Nationality
     */
    private $nationality;

    /**
     * Updater constructor.
     * @param object $config
     * @param Helper $helper
     */
    public function __construct($config, Helper $helper)
    {
        $this->config = $config;

        $this->helper = $helper;

        $this->cache = new Cache(new ConfigService());

        $this->cache->clear();

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

        $this->nationality = new Nationality($this->helper, $this);

        $this->init();
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

    protected function instantiateCommand($command)
    {
        return is_null($command)
            ? new Command()
            : $command;
    }

    private function loadCountries()
    {
        if (is_null($this->_countries)) {
            $this->_countries = $this->helper->loadJson(__DIR__.'/../data/countries/default/_all_countries.json');
        }
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
    public function update($command = null)
    {
        $this->command = $this->instantiateCommand($command);

        $this->helper->downloadFiles();

        $this->countries->update();

        $this->currencies->update();

        $this->loadCountries();

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

        return coollect($record);
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
            $found = $on->where($field[0], $by[$field[1]])->first();

            if (isset($by[$field[1]]) && ! is_null($found) && $found->count() > 0) {
                return [coollect($found), $found->{$codeField}];
            }
        }

        return [coollect(), null];
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

                $record = coollect($record)->sortBy(function ($value, $key) {
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
        $counter = 0;

        return $this->cache->remember(
            'normalizeData'.$dir,
            160,
            function () use ($result, $normalizerClosure, &$counter) {
                return coollect($result)->map(function ($item, $key) use ($normalizerClosure, &$counter) {
                    if ($counter++ % 1000 === 0) {
                        $this->helper->message("Normalized: {$counter}");
                    }

                    return $normalizerClosure(coollect($item)->mapWithKeys(function ($value, $key) {
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

        [, $countryCode] = $this->findByFields($this->_countries, $item, $fields, 'cca3');

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
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    public function init()
    {
        $this->defineConstants();
    }
}
