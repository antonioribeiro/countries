<?php

namespace PragmaRX\Countries\Package\Support;

use File;
use Cache;
use Closure;
use Exception;
use ShapeFile\ShapeFile;
use Illuminate\Console\Command;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Facade as CountriesService;

/**
 * @codeCoverageIgnore
 */
class UpdateData extends Base
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    protected $command;

    protected $countries;

    protected $data = [
        'downloadable' => [
            'mledoze' => 'https://github.com/mledoze/countries/archive/master.zip',

            'rinvex' => 'https://github.com/rinvex/country/archive/master.zip',

            'natural_earth' => [
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.shx',
            ],

            'commerceguys' => 'https://github.com/commerceguys/tax/archive/master.zip',

            'timezonedb' => 'http://timezonedb.com/files/timezonedb.csv.zip',

            'world-currencies' => 'https://github.com/antonioribeiro/world-currencies/archive/master.zip',
        ],

        'moveable' => [
            'third-party/mledoze/package/data' => 'third-party/mledoze/data',
            'third-party/mledoze/package/dist' => 'third-party/mledoze/dist',
            'third-party/rinvex/package/resources' => 'third-party/rinvex/data',
            'third-party/mledoze/package/data/*.svg' => 'flags',
            'third-party/mledoze/package/data/*.geo.json' => 'geo',
            'third-party/mledoze/package/data/*.topo.json' => 'topo',
            'third-party/commerceguys/package/resources/tax_type' => 'third-party/commerceguys/taxes/types',
            'third-party/commerceguys/package/resources/zone' => 'third-party/commerceguys/taxes/zones',
        ],

        'deletable' => [
            'third-party',
            'tmp',
        ],
    ];

    /**
     * Update all data.
     *
     * @param $command
     */
    public function update($command)
    {
        $this->command = $command;

        $this->downloadFiles();

        $this->updateCountries();

        $this->updateCurrencies();

        $this->updateStates();

        $this->updateCities();

        $this->updateTaxes();

        $this->updateTimezone();

        $this->deleteTemporaryFiles();
    }

    protected function fixNaturalOddCountries($country)
    {
        if ($country['iso_a2'] === '-99') {
            $country['iso_a2'] = $country['wb_a2'];

            $country['iso_a3'] = $country['wb_a3'];
        }

        return $country;
    }

    /**
     * @return Coollection
     */
    protected function loadMledozeCountries()
    {
        $mledoze = countriesCollect($this->loadJson('countries', 'third-party/mledoze/dist'))->mapWithKeys(function (
            $country
        ) {
            $country = $this->addDataSource($country, 'mledoze');

            $country = $this->addRecordType($country, 'country');

            return [$country['cca3'] => $country];
        });

        return $mledoze;
    }

    /**
     * Add data sources to collection.
     *
     * @param $record
     * @param string $source
     * @return Coollection
     */
    protected function addDataSource($record, $source)
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

    private function addRecordType($result, $type)
    {
        $result['record_type'] = $type;

        return $result;
    }

    /**
     * Build countries collection.
     *
     * @param $dataDir
     * @return static
     */
    protected function buildCountriesCollection($dataDir)
    {
        $this->message('Processing countries...');

        $mledoze = $this->loadMledozeCountries();

        $countries = countriesCollect($this->loadShapeFile('third-party/natural_earth/ne_10m_admin_0_countries'))->map(function ($country, $key) {
            return $this->fixNaturalOddCountries($country);
        })->mapWithKeys(function ($natural, $key) use ($mledoze, $dataDir) {
            list($mledoze, $countryCode) = $this->findMledozeCountry($mledoze, $natural);

            $natural = countriesCollect($natural)->mapWithKeys(function ($country, $key) {
                return [strtolower($key) => $country];
            });

            if (is_null($countryCode)) {
                $result = $this->fillMledozeFields($natural);

                $countryCode = $natural['adm0_a3'];
            } else {
                $result = $this->mergeWithMledoze($mledoze, $natural);
            }

            $result = $this->mergeWithRinvex($result,
                $this->findRinvexCountry($result),
                $this->findRinvexTranslations($result)
            );

            $result = $this->clearCountryCurrencies($result);

            $result = $this->addDataSource($result, 'natural');

            $result = $this->addRecordType($result, 'country');

            $result = $result->sortByKeysRecursive();

            $this->putFile(
                $this->makeJsonFileName(strtolower($countryCode), $dataDir),
                $result->toJson(JSON_PRETTY_PRINT)
            );

            return [$countryCode => $result];
        });

        return $mledoze->overwrite($countries);
    }

    /**
     * Return string to be used in keys.
     *
     * @param $admin
     * @return string
     */
    protected function caseForKey($admin)
    {
        return snake_case(strtolower(str_replace('-', '_', $admin)));
    }

    protected function clearCountryCurrencies($country)
    {
        if (isset($country['currency']) && ! is_null($country['currency'])) {
            $country['currencies'] = array_keys($country['currency']);

            unset($country['currency']);
        } else {
            $country['currencies'] = [];
        }

        return $country;
    }

    /**
     * Download files.
     */
    protected function downloadDataFiles()
    {
        countriesCollect($this->data['downloadable'])->each(function ($urls, $path) {
            if (! file_exists($destination = $this->dataDir("third-party/$path"))) {
                countriesCollect($urls)->each(function ($url) use ($path, $destination) {
                    $this->download($url, $destination);

                    $file = basename($url);

                    $this->unzip("$destination/$file", 'package');
                });
            }
        });
    }

    /**
     * Download, move and delete data files.
     */
    protected function downloadFiles()
    {
        $this->downloadDataFiles();

        $this->moveDataFiles();
    }

    /**
     * Erase all files from states data dir.
     *
     * @param string $dir
     */
    protected function eraseDataDir($dir)
    {
        $this->delTree($this->dataDir($dir));
    }

    /**
     * Fill mledoze fields with natural earth vector data.
     *
     * @param $fields
     * @return mixed
     */
    protected function fillMledozeFields($fields)
    {
        $fields['name_nev'] = $fields['name'];

        $fields['name'] = [
            'common' => $fields['name'],
            'official' => $fields['formal_en'],
        ];

        $fields['cca2'] = $fields['iso_a2'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];
        $fields['ccn3'] = $fields['iso_n3'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];
        $fields['cca3'] = $fields['iso_a3'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];

        $fields['region'] = $fields['region_un'];

        $fields['borders'] = [];

        $fields['curencies'] = [];

        $fields['notes'] = ['Incomplete record due to missing mledoze country.'];

        return $fields;
    }

    /**
     * Fill natural earth vector fields with mledoze data.
     *
     * @param $fields
     * @return mixed
     */
    protected function fillNaturalFields($fields)
    {
        return $fields;
    }

    /**
     * Fill array with Rinvex usable data.
     *
     * @param $natural
     * @return mixed
     */
    protected function fillRinvexFields($natural)
    {
        $mergeable = [
            'calling_code' => 'dialling',
            'borders'      => 'geo',
            'area'         => 'geo',
            'continent'    => 'geo',
            'landlocked'   => 'geo',
            'region'       => 'geo',
            'region_un'    => 'geo',
            'region_wb'    => 'geo',
            'subregion'    => 'geo',
            'latlng'       => 'geo',
        ];

        countriesCollect($mergeable)->each(function ($to, $key) use (&$natural) {
            if (isset($natural[$key])) {
                $natural->overwrite([$to => [$key => $natural[$key]]]);

                unset($natural[$key]);
            }
        });

        return $natural;
    }

    /**
     * @param Collection $mledoze
     * @param Collection $natural
     * @return array
     */
    protected function findCountryByAnyField($mledoze, $natural)
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
     * @param $on
     * @param $by
     * @param $fields
     * @param $codeField
     * @return array
     */
    protected function findByFields($on, $by, $fields, $codeField)
    {
        foreach ($fields as $field) {
            if (isset($by[$field[1]]) && ! is_null($found = $on->where($field[0], $by[$field[1]])->first())) {
                return [countriesCollect($found), $found->{$codeField}];
            }
        }

        return [countriesCollect(), null];
    }

    /**
     * Find a mledoze country from natural earth vector data.
     *
     * @param Collection $mledoze
     * @param Collection $natural
     * @return array
     */
    protected function findMledozeCountry($mledoze, $natural)
    {
        list($country, $countryCode) = $this->findCountryByAnyField($mledoze, $natural);

        if (! $country->isEmpty()) {
            return [countriesCollect($this->arrayKeysSnakeRecursive($country)), $countryCode];
        }

        return [countriesCollect(), $countryCode];
    }

    /**
     * @param $result
     * @return null|string
     */
    protected function findRinvex($result, $type)
    {
        return $this->loadJson(strtolower($result['cca2']), "third-party/rinvex/data/$type");
    }

    /**
     * Find the Rinvex country.
     *
     * @param $item
     * @return null|string
     */
    protected function findRinvexCountry($item)
    {
        return $this->findRinvex($item, 'data');
    }

    /**
     * Find the Rinvex state.
     *
     * @param $item
     * @return null|string
     */
    protected function findRinvexStates($item)
    {
        return $this->findRinvex($item, 'divisions');
    }

    /**
     * Find the Rinvex state.
     *
     * @param $country
     * @return null|string
     */
    protected function findRinvexState($country, $needle)
    {
        $states = $this->findRinvex($country, 'divisions')->map(function ($state, $postal) {
            $state['postal'] = $postal;

            $state['name'] = $this->fixUtf8($state['name']);

            return $state;
        });

        if ($states->isEmpty()) {
            return $states;
        }

        $state = $states->filter(function ($rinvexState) use ($needle) {
            return $rinvexState->postal == $needle->postal ||
                $rinvexState->name == $needle['name'] ||
                utf8_encode($rinvexState->name) == $needle['name'] ||
                $rinvexState->alt_names->contains($needle['name']) ||
                $rinvexState->alt_names->contains(function ($name) use ($needle) {
                    return $needle->alt_names->contains($name);
                });
        })->first();

        if (is_null($state)) {
            return countriesCollect();
        }

        return countriesCollect($state);
    }

    /**
     * Find the Rinvex translation.
     *
     * @param $result
     * @return null|string
     */
    protected function findRinvexTranslations($result)
    {
        return $this->loadJson(strtolower($result['cca2']), 'third-party/rinvex/data/translations');
    }

    /**
     * Generate all json files.
     *
     * @param $dir
     * @param $makeGroupKeyClosure
     * @param Coollection $records
     * @param string|null $groupKey
     */
    protected function generateAllJsonFiles($dir, $makeGroupKeyClosure, $records, $groupKey)
    {
        if (! empty($groupKey)) {
            $records = $records->groupBy($groupKey);
        }

        $records->each(function ($record, $key) use ($dir, $makeGroupKeyClosure) {
            $this->mkdir(dirname($file = $this->makeJsonFileName($key, $dir)));

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

            file_put_contents($file, $this->jsonEncode($record));
        });
    }

    /**
     * Generate json files from array.
     *
     * @param $data
     * @param $dir
     * @param Closure $normalizerClosure
     * @param Closure $makeGroupKeyClosure
     * @param Closure $mergeData
     * @param string $groupKey
     * @return Coollection
     */
    protected function generateJsonFiles($data, $dir, $normalizerClosure, $makeGroupKeyClosure, $mergeData, $groupKey = 'cca3')
    {
        $this->message('Normalizing data...');

        $data = $this->normalizeData($data, $dir, $normalizerClosure);

        $this->message('Merging data...');

        $data = $mergeData($data);

        $this->message('Generating files...');

        $this->generateAllJsonFiles($dir, $makeGroupKeyClosure, $data, $groupKey);

        return $data;
    }

    /**
     * Get the state postal code.
     *
     * @param $item
     * @return mixed
     */
    protected function makeStatePostalCode($item)
    {
        $item = countriesCollect($item);

        if ($item->iso_3166_2 !== '') {
            $code = explode('-', $item->iso_3166_2);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        if (! empty(trim($item->postal))) {
            $item->postal;
        }

        if ($item->code_hasc !== '') {
            $code = explode('.', $item->code_hasc);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        return $this->caseForKey($item->iso_3166_2);
    }

    /**
     * Load the shape file (DBF) to array.
     *
     * @param string $file
     * @return array
     */
    protected function loadShapeFile($file)
    {
        $this->progress('Loading shape file...');

        if (file_exists($sha = $this->dataDir('tmp/'.sha1($file = $this->dataDir($file))))) {
            return $this->loadJson($sha);
        }

        $shapefile = $this->shapeFile($file);

        $this->mkDir(dirname($sha));

        file_put_contents($sha, $shapefile->toJson());

        return $shapefile;
    }

    /**
     * Merge country data with Rinvex data.
     *
     * @param $natural
     * @param $rinvex
     * @param $translation
     * @param string $suffix
     * @return mixed|static
     */
    protected function mergeWithRinvex($natural, $rinvex, $translation, $suffix = '_rinvex')
    {
        $defaultToRinvex = countriesCollect([
            'currency',
            'languages',
            'dialling',
        ]);

        $merge = countriesCollect([
            'geo',
            'translations',
        ]);

        $natural = $this->fillRinvexFields($natural);

        if ($rinvex->isEmpty()) {
            return $natural;
        }

        $rinvex['translations'] = $translation;
        $rinvex['flag'] = ['emoji' => $rinvex['extra']['emoji']];

        $result = [];

        foreach ($rinvex->keys()->merge($natural->keys()) as $key) {
            $naturalValue = arrayable($var = $natural->get($key)) ? $var->sortByKeysRecursive()->toArray() : $var;

            $rinvexValue = arrayable($var = $rinvex->get($key)) ? $var->sortByKeysRecursive()->toArray() : $var;

            if (is_null($naturalValue) || is_null($rinvexValue)) {
                $result[$key] = $rinvexValue ?: $naturalValue;

                continue;
            }

            if ($rinvexValue !== $naturalValue && $merge->contains($key)) {
                $result[$key] = countriesCollect($naturalValue)->overwrite($rinvexValue);

                continue;
            }

            if ($rinvexValue !== $naturalValue && ! $defaultToRinvex->contains($key)) {
                $result[$key.$suffix] = $rinvexValue; // Natural Earth Vector
            }

            $result[$key] = $defaultToRinvex->contains($key)
                ? $rinvexValue
                : $naturalValue; // Natural Earth Vector
        }

        return countriesCollect($result)->sortBy(function ($value, $key) {
            return $key;
        });
    }

    /**
     * Move a data file or many using wildcards.
     *
     * @param $from
     * @param $to
     */
    protected function moveDataFile($from, $to)
    {
        if (str_contains($from, '*.')) {
            $this->moveFilesWildcard($from, $to);

            return;
        }

        if (file_exists($from = $this->dataDir($from))) {
            $this->mkDir(dirname($to = $this->dataDir($to)));

            rename($from, $to);
        }
    }

    /**
     * Move data files to the proper location.
     */
    protected function moveDataFiles()
    {
        countriesCollect($this->data['moveable'])->each(function ($to, $from) {
            $this->moveDataFile($from, $to);
        });
    }

    /**
     * Delete uneeded data files.
     */
    protected function deleteTemporaryFiles()
    {
        countriesCollect($this->data['deletable'])->each(function ($directory) {
            if (file_exists($directory = $this->dataDir($directory))) {
                File::deleteDirectory($directory);
            }
        });
    }

    /**
     * Move files using wildcard filter.
     *
     * @param $from
     * @param $to
     */
    protected function moveFilesWildcard($from, $to)
    {
        countriesCollect(glob($this->dataDir($from)))->each(function ($from) use ($to) {
            $this->mkDir($to = $this->dataDir($to));

            rename($from, $to.'/'.basename($from));
        });
    }

    private function naturalToStateArray($state)
    {
        $state = [
            'name' => $state['name'],

            'alt_names' => explode('|', $state['name_alt']),

            'cca2' => $state['cca2'],

            'cca3' => $state['cca3'],

            'code_hasc' => $state['code_hasc'],

            'extra' => countriesCollect($state)->sortByKey()->except([
                'name', 'name_alt', 'latitude', 'longitude', 'cca2', 'cca3',
                'iso_a2', 'iso_a3', 'type', 'type_en', 'postal',
                'iso_3166_2', 'code_hasc',
            ]),

            'geo' => [
                'latitude' => $state['latitude'],
                'longitude' => $state['longitude'],
            ],

            'iso_a2' => $state['iso_a2'],

            'iso_a3' => $state['iso_a3'],

            'iso_3166_2' => $state['iso_3166_2'],

            'postal' => $this->makeStatePostalCode($state),

            'type' => $state['type'],

            'type_en' => $state['type_en'],
        ];

        return $state;
    }

    /**
     * @param $result
     * @param $dir
     * @param $normalizerClosure
     * @return array
     */
    protected function normalizeData($result, $dir, $normalizerClosure)
    {
        return Cache::remember(
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
    protected function normalizeStateOrCityData($item)
    {
        $fields = [
            ['cca2', 'iso_a2'],
            ['name.common', 'admin'],
            ['name.official', 'admin'],
            ['adm0_a3', 'adm0_a3'],
        ];

        list($country, $countryCode) = $this->findByFields($this->countries, $item, $fields, 'cca3');

        if (is_null($countryCode)) {
            $countryCode = $this->caseForKey($item['name']);
        }

        $item['iso_a3'] = ! isset($item['iso_a3'])
            ? $countryCode
            : $item['iso_a3'];

        $item['cca3'] = $item['iso_a3'];

        $item['cca2'] = $item['iso_a2'];

        return $item;
    }

    protected function normalizeTax($tax)
    {
        return $tax;
    }

    /**
     * Show the progress.
     *
     * @param string $string
     */
    protected function progress($string = '')
    {
        if (is_null($this->command)) {
            dump($string);

            return;
        }

        $this->command->line($string);
    }

    /**
     * Merge state data with rinvex divisions data.
     *
     * @param $states
     * @return Coollection
     */
    private function mergeCountryStatesWithRinvex($states)
    {
        return countriesCollect($states)->map(function ($state, $key) {
            return $this->mergeStateWithRinvex($state);
        });
    }

    /**
     * @param $state
     * @return Coollection
     * @throws Exception
     */
    protected function mergeStateWithRinvex($state)
    {
        $country = $this->countries->where('cca3', $iso_a3 = $state['iso_a3'])->first();

        if (is_null($country)) {
            dump($state);

            throw new Exception('Country not found for state');
        }

        $state = countriesCollect($this->naturalToStateArray($state));

        $rinvex = $this->findRinvexState($country, $state);

        if ($rinvex->isEmpty()) {
            return $state;
        }

        $rinvex = $this->rinvexToStateArray($rinvex, $state['cca3'], $state->postal, $country);

        return $state->overwrite($rinvex);
    }

    private function rinvexToStateArray($rinvex, $cca3, $postal, $country)
    {
        $mergeable = [
            'cca2' => $country['cca2'],

            'cca3' => $cca3,

            'iso_a2' => $country['iso_a2'],

            'iso_a3' => $country['iso_a3'],

            'iso_3166_2' => "{$country['cca2']}-$postal",

            'postal' => $postal,
        ];

        return $rinvex->overwrite($mergeable);
    }

    /**
     * Unzip a file.
     *
     * @param $file
     * @param $path
     */
    protected function unzip($file, $path)
    {
        if (ends_with($file, '.zip')) {
            $this->message("Unzipping to {$file}");

            $this->unzipFile($file, $path);
        }
    }

    /**
     * Update cities.
     */
    protected function updateCities()
    {
        $this->progress('Updating cities...');

        $this->eraseDataDir($dataDir = '/cities/default/');

        $result = $this->loadShapeFile('third-party/natural_earth/ne_10m_populated_places');

        $this->message('Processing cities...');

        $normalizerClosure = function ($item) {
            $item = $this->addDataSource($item, 'natural');

            $item = $this->addRecordType($item, 'city');

            return $this->normalizeStateOrCityData($item);
        };

        $codeGeneratorClosure = function ($item) {
            return $this->caseForKey($item['nameascii']);
        };

        $mergerClosure = function ($item) {
            return $item;
        };

        list($countries, $cities) = $this->generateJsonFiles($result, $dataDir, $normalizerClosure, $codeGeneratorClosure, $mergerClosure);

        $this->progress('Generated '.count($cities).' cities.');
    }

    /**
     * Update countries.
     */
    protected function updateCountries()
    {
        $this->progress('Updating countries...');

        $dataDir = '/countries/default/';

        $this->countries = Cache::remember('updateCountries->buildCountriesCollection', 160, function () use ($dataDir) {
            $this->eraseDataDir($dataDir);

            return $this->buildCountriesCollection($dataDir);
        });

        $this->putFile(
            $this->makeJsonFileName('_all_countries', $dataDir),
            $this->countries->toJson(JSON_PRETTY_PRINT)
        );

        $this->progress('Generated '.count($this->countries).' countries.');

        $this->countries = CountriesService::all();
    }

    /**
     * Merge the two countries sources.
     *
     * @param \PragmaRX\Countries\Package\Support\Collection $mledoze
     * @param \PragmaRX\Countries\Package\Support\Collection $natural
     * @param string $suffix
     * @return mixed
     */
    protected function mergeWithMledoze($mledoze, $natural, $suffix = '_nev')
    {
        if ($mledoze->isEmpty() || $natural->isEmpty()) {
            return $mledoze->isEmpty()
                ? $this->fillMledozeFields($natural)
                : $this->fillNaturalFields($mledoze);
        }

        $result = [];

        foreach ($mledoze->keys()->merge($natural->keys()) as $key) {
            $naturalValue = $natural->get($key);
            $mledozeValue = $mledoze->get($key);

            if (is_null($naturalValue) || is_null($mledozeValue)) {
                $result[$key] = $mledozeValue ?: $naturalValue;

                continue;
            }

            if ($key == 'data_sources') {
                $result[$key] = $mledozeValue->merge($naturalValue);

                continue;
            }

            if ($mledozeValue !== $naturalValue) {
                $result[$key.$suffix] = $naturalValue; // Natural Earth Vector
            }

            $result[$key] = $mledozeValue; // Natural Earth Vector
        }

        return countriesCollect($result)->sortBy(function ($value, $key) {
            return $key;
        });
    }

    protected function updateCurrencies()
    {
        $this->progress('Updating currencies...');

        $this->eraseDataDir($dataDir = '/currencies/default');

        $currencies = $this->loadJsonFiles($this->dataDir('third-party/world-currencies/package/src'));

        $currencies = $currencies->mapWithKeys(function ($currency, $key) {
            return $currency;
        });

        $this->message('Processing currencies...');

        $normalizerClosure = function ($item) {
            $item = $this->addDataSource($item, 'world-currencies');

            $item = $this->addRecordType($item, 'currency');

            return [$item];
        };

        $getCodeClosure = function ($item) {
        };

        $generateTaxData = function ($tax) {
            return $tax;
        };

        $currencies = $this->generateJsonFiles($currencies, $dataDir, $normalizerClosure, $getCodeClosure, $generateTaxData, null);

        $this->progress('Generated '.count($currencies).' currencies.');
    }

    /**
     * Update states.
     */
    protected function updateStates()
    {
        $this->progress('Updating states...');

        $this->eraseDataDir($dataDir = '/states/default');

        $result = $this->loadShapeFile('third-party/natural_earth/ne_10m_admin_1_states_provinces');

        $this->message('Processing states...');

        $normalizerClosure = function ($item) {
            $item = $this->addDataSource($item, 'natural');

            $item = $this->addRecordType($item, 'state');

            return $this->normalizeStateOrCityData($item);
        };

        $getCodeClosure = function ($item) {
            return $this->makeStatePostalCode($item);
        };

        $mergerClosure = function ($states) {
            return $this->mergeCountryStatesWithRinvex($states);
        };

        list($countries, $states) = $this->generateJsonFiles($result, $dataDir, $normalizerClosure, $getCodeClosure, $mergerClosure);

        $this->progress('Generated '.count($states).' states.');
    }

    protected function updateTaxes()
    {
        $this->progress('Updating taxes...');

        $this->eraseDataDir($dataDir = '/taxes/default');

        $taxes = $this->loadJsonFiles($this->dataDir('third-party/commerceguys/taxes/types'));

        $taxes = $taxes->mapWithKeys(function ($vat, $key) {
            $parts = countriesCollect(explode('_', $key));
            $cca2 = $parts->first();
            $type = $parts->last();
            $modifier = $parts->count() > 2 ? $parts[1] : '';

            $country = $this->countries->where('cca2', strtoupper($cca2))->first();

            $vat['vat_id'] = $key;

            $vat['cca2'] = $country->cca2;

            $vat['cca3'] = $country->cca3;

            $vat['tax_type'] = $type;

            $vat['tax_modifier'] = $modifier;

            $vat = $this->addDataSource($vat, 'commerceguys');

            $vat = $this->addRecordType($vat, 'tax');

            $vat = [
                $type.(empty($modifier) ? '' : '_').$modifier => $vat,
            ];

            return [$country->cca3 => $vat];
        });

        $this->message('Processing taxes...');

        $normalizerClosure = function ($item, $key) {
            return $item;
        };

        $getCodeClosure = function ($item) {
            return $item['tax_type'];
        };

        $generateTaxData = function ($tax) {
            return $this->normalizeTax($tax);
        };

        $taxes = $this->generateJsonFiles($taxes, $dataDir, $normalizerClosure, $getCodeClosure, $generateTaxData, null);

        $this->progress('Generated '.count($taxes).' taxes.');
    }

    protected function updateTimezone()
    {
        $this->eraseDataDir($dataDir = '/timezones');

        $this->progress('Loading countries...');

        $countries = Cache::remember(
            'updateTimezone.countries', 160,
            function () {
                return $this->loadCsv($this->dataDir('third-party/timezonedb/country.csv'));
            }
        );

        $this->progress('Loading zones...');

        $zones = Cache::remember(
            'updateTimezone.zones', 160,
            function () {
                return $this->loadCsv($this->dataDir('third-party/timezonedb/zone.csv'))->mapWithKeys(function ($value) {
                    return [
                        $value[0] => [
                            'zone_id'      => $value[0],
                            'country_code' => $value[1],
                            'zone_name'    => $value[2],
                        ],
                    ];
                });
            }
        );

        $this->progress('Loading timezones...');

        $timezones = Cache::remember(
            'updateTimezone.timezones', 160,
            function () {
                return $this->loadCsv($this->dataDir('third-party/timezonedb/timezone.csv'))->map(function ($timezone) {
                    return [
                        'zone_id' => $timezone[0],
                        'abbreviation' => $timezone[1],
                        'time_start' => $timezone[2],
                        'gmt_offset' => $timezone[3],
                        'dst' => $timezone[4],
                    ];
                });
            }
        );

        $this->progress('Generating abbreviations...');

        $abbreviations = Cache::remember(
            'updateTimezone.abbreviations', 160,
            function () use ($timezones) {
                return $timezones->groupBy('zone_id')->map(function ($timezones) {
                    return $timezones->map(function ($timezone) {
                        return $timezone['abbreviation'];
                    })->unique()->sort()->values();
                });
            }
        );

        $this->progress('Updating countries timezones...');

        $countries = $countries->mapWithKeys(function ($item) {
            return [$item[0] => [
                'cca2' => $item[0],
                'name' => $item[1],
            ]];
        })
        ->mapWithKeys(function ($item, $cca2) {
            $fields = [
                ['cca2', 'cca2'],
                ['name.common', 'name'],
                ['name.official', 'name'],
            ];

            list($country, $countryCode) = $this->findByFields($this->countries, $item, $fields, 'cca2');

            if ($country->isEmpty()) {
                return [$cca2 => $item];
            }

            return [
                $country->cca3 => [
                    'cca2' => $country->cca2,
                    'cca3' => $country->cca3,
                    'name' => $item['name'],
                ],
            ];
        })->map(function ($country) use ($zones, $abbreviations) {
            $country['timezones'] = $zones->where('country_code', $country['cca2'])->mapWithKeys(function ($zone) use ($abbreviations, $country) {
                $zone['abbreviations'] = $abbreviations[$zone['zone_id']];

                $zone['cca3'] = isset($country['cca3']) ? $country['cca3'] : null;

                $zone['cca2'] = isset($country['cca2']) ? $country['cca2'] : null;

                $zone = $this->addDataSource($zone, 'timezonedb');

                $zone = $this->addRecordType($zone, 'timezone');

                return [$this->zoneNameSnake($zone['zone_name']) => $zone];
            });

            return $country;
        });

        $this->message('Generating timezone files...');

        $getCountryCodeClosure = function ($timezone) {
        };

        $normalizeCountryClosure = function ($country) {
            return [$country['timezones']];
        };

        $dummyClosure = function ($country) {
            return $country;
        };

        $this->generateJsonFiles($countries, "$dataDir/countries/default", $normalizeCountryClosure, $getCountryCodeClosure, $dummyClosure, '');

        $this->generateJsonFiles($timezones, "$dataDir/timezones/default", $dummyClosure, null, $dummyClosure, 'zone_id');

        $this->progress('Generated timezones for '.count($countries).' countries.');

        $this->progress('Generated '.count($timezones).' timezones.');
    }

    /**
     * Get the zone name from a timezone.
     *
     * @param $country
     * @return string
     */
    protected function getZoneName($country): string
    {
        return isset($country['timezones']['zone_name'])
            ? $country['timezones']['zone_name']
            : 'unknown';
    }

    protected function zoneNameSnake($name)
    {
        return snake_case(str_replace(['\\', '/', '__'], ['_', ''], $name));
    }

    /**
     * Display a message in console.
     *
     * @param $message
     * @param string $type
     */
    protected function message($message, $type = 'line')
    {
        if (! is_null($this->command)) {
            $this->command->{$type}($message);
        }
    }

    /**
     * Get temp directory.
     *
     * @param string $path
     * @return string
     */
    protected function tmpDir($path)
    {
        return __COUNTRIES_DIR__._dir("/tmp/{$path}");
    }

    /**
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return null|string
     * @throws Exception
     */
    public function loadCsv($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadCsv Error: File name not set');
        }

        if (! file_exists($file)) {
            $file = $this->dataDir("/$dir/".strtolower($file).'.csv');
        }

        return countriesCollect($this->csvDecode(
            file($file),
            true
        ));
    }

    /**
     * Make state json filename.
     *
     * @param $key
     * @return string
     */
    protected function makeJsonFileName($key, $dir = '')
    {
        if (! ends_with($dir, (DIRECTORY_SEPARATOR))) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        return $this->dataDir(_dir($dir).strtolower($key).'.json');
    }

    /**
     * Put contents into a file.
     *
     * @param $file
     * @param $contents
     */
    public function putFile($file, $contents)
    {
        $this->mkdir(dirname($file));

        file_put_contents($file, $contents);
    }

    /**
     * Encode and pretty print json.
     *
     * @param array $data
     * @return string
     */
    public function jsonEncode($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Get package home dir.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return $this->getClassDir(Service::class);
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

    /**
     * Make a directory.
     *
     * @param $dir
     */
    protected function mkDir($dir)
    {
        if (file_exists($dir)) {
            return;
        }

        mkdir($dir, 0755, true);
    }

    /**
     * Download one or more files.
     *
     * @param $url
     * @param $directory
     */
    protected function download($url, $directory)
    {
        countriesCollect((array) $url)->each(function ($url) use ($directory) {
            $filename = basename($url);

            $destination = _dir("{$directory}/{$filename}");

            $this->message("Downloading to {$destination}");

            $this->mkDir($directory);

            $this->download_file($url, $destination);
        });
    }

    public function getClassDir($class)
    {
        $reflector = new ReflectionClass($class);

        return dirname($reflector->getFileName());
    }

    public function download_file($url, $destination)
    {
        if (file_exists($destination)) {
            return;
        }

        try {
            $this->download_fopen($url, $destination);
        } catch (\Exception $exception) {
            $this->download_curl($url, $destination);
        }

        chmod($destination, 0644);
    }

    public function download_fopen($url, $destination)
    {
        $fr = fopen($url, 'r');

        $fw = fopen($destination, 'w');

        while (! feof($fr)) {
            fwrite($fw, fread($fr, 4096));
            flush();
        }

        fclose($fr);

        fclose($fw);
    }

    public function download_curl($url, $destination)
    {
        $nextStep = 8192;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $total, $downloaded) use (&$nextStep) {
            if ($downloaded > $nextStep) {
                echo '.';
                $nextStep += 8192;
            }
        });
        curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GuzzleHttp/6.2.1 curl/7.54.0 PHP/7.2.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        file_put_contents($destination, curl_exec($ch));
        curl_close($ch);

        echo "\n";
    }

    public function unzipFile($file, $subPath)
    {
        $path = dirname($file);

        $exclude = basename($file);

        if (! ends_with($file, '.zip') || file_exists($subPath = "$path/$subPath")) {
            return;
        }

        chdir($path);

        exec("unzip -o $file");

        if (ends_with($file, 'master.zip')) {
            $dir = countriesCollect(scandir($path))->filter(function ($file) use ($exclude) {
                return $file !== '.' && $file !== '..' && $file !== $exclude;
            })->first();

            rename("$path/$dir", $subPath);
        }
    }

    /**
     * Delete a directory and all its files.
     *
     * @param $dir
     * @return bool
     */
    public function delTree($dir)
    {
        if (! file_exists($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    /**
     * Load a shapefile.
     *
     * @param $dir
     * @return \PragmaRX\Countries\Package\Support\Collection
     */
    public function shapeFile($dir)
    {
        $shapeRecords = new ShapeFile($dir);

        $result = [];

        foreach ($shapeRecords as $record) {
            if ($record['dbf']['_deleted']) {
                continue;
            }

            $data = $record['dbf'];

            unset($data['_deleted']);

            $result[] = $data;
        }

        unset($shapeRecords);

        return countriesCollect($result)->mapWithKeys(function ($fields, $key1) {
            return [
                strtolower($key1) => countriesCollect($fields)->mapWithKeys(function ($value, $key2) {
                    return [strtolower($key2) => $value];
                }),
            ];
        });
    }

    /**
     * Recursively change all array keys case.
     *
     * @param $array
     * @return Collection
     */
    public function arrayKeysSnakeRecursive($array)
    {
        $result = [];

        $array = arrayable($array) ? $array->toArray() : $array;

        array_walk($array, function ($value, $key) use (&$result) {
            $result[snake_case($key)] = arrayable($value) || is_array($value)
                ? $this->arrayKeysSnakeRecursive($value)
                : $value;
        });

        return countriesCollect($result);
    }

    /**
     * Load CSV file.
     *
     * @param $csv
     * @return Coollection
     */
    public function csvDecode($csv)
    {
        return countriesCollect(array_map('str_getcsv', $csv));
    }

    /**
     * Fix a bad UTF8 string.
     *
     * @param $string
     * @return string
     */
    public function fixUtf8($string)
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);
    }
}
