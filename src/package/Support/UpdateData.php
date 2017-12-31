<?php

namespace PragmaRX\Countries\Package\Support;

use Closure;

class UpdateData extends Base
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    protected $command;

    /**
     * Build countries collection.
     *
     * @param $dataDir
     * @return static
     */
    private function buildCountriesCollection($dataDir)
    {
        $shapefile = $this->loadShapeFile('natural_earth/ne_10m_admin_0_countries');

        $mledoze = countriesCollect(json_decode($this->loadJson('countries', 'mledoze'), true))->mapWithKeys(function ($country) {
            return [$country['cca3'] => $country];
        });

        $countries = countriesCollect($shapefile)->mapWithKeys(function ($natural, $key) use ($mledoze, $dataDir) {
            $result = $this->mergeCountries(
                countriesCollect($natural)->mapWithKeys(function ($value, $key) {
                    return [strtolower($key) => $value];
                }),
                $mledoze->get($countryCode = $natural['adm0_a3'])
            );

            $this->putFile(
                $this->makeJsonFileName(strtolower($countryCode), $dataDir),
                $result->toJson(JSON_PRETTY_PRINT)
            );

            return [$countryCode => $result];
        });

        return $countries;
    }

    /**
     * Download files.
     */
    private function downloadFiles()
    {
        countriesCollect(config('countries.data.downloadable'))->each(function ($urls, $path) {
            countriesCollect($urls)->each(function ($url) use ($path) {
                $this->download($url, $this->dataDir($path));
            });
        });
    }

    /**
     * Erase all files from states data dir.
     * @param string $dir
     */
    private function eraseDataDir($dir)
    {
        deltree($this->dataDir($dir));
    }

    /**
     * Generate json files from array.
     * s.
     * @param $result
     * @param $dir
     * @param Closure $makeGroupKeyClosure
     * @return mixed
     */
    private function generateJsonFiles($result, $dir, $makeGroupKeyClosure)
    {
        $count2 = 0;

        $count1 = countriesCollect($result)->map(function ($item) {
            return $this->normalize(countriesCollect($item)->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            }));
        })->groupBy('grouping')->each(function ($item, $key) use ($dir, $makeGroupKeyClosure, &$count2) {
            $this->mkdir(dirname($file = $this->makeJsonFileName($key, $dir)));

            $item = $item->mapWithKeys(function ($item) use ($makeGroupKeyClosure) {
                return [$makeGroupKeyClosure($item) => countriesCollect($item)->sortBy(function ($value, $key) {
                    return $key;
                })];
            })->sortBy(function ($value, $key) {
                return $key;
            });

            $count2 += $item->count();

            file_put_contents($file, $this->jsonEncode($item));
        })->count();

        return [$count1, $count2];
    }

    /**
     * Get the state postal code.
     *
     * @param $item
     * @return mixed
     */
    protected function makeStatePostalCode($item)
    {
        return trim($item->postal) === ''
            ? "{$item->grouping}.{$item->adm0_sr}"
            : $item->postal;
    }

    /**
     * Load the shape file (DBF) to array.
     *
     * @param string $dir
     * @return array
     */
    private function loadShapeFile($dir)
    {
        $this->progress('Loading shape file...');

        return load_shapefile($this->dataDir($dir));
    }

    /**
     * Normalize data.
     *
     * @param $item
     * @return mixed
     */
    private function normalize($item)
    {
        $item['grouping'] = $item['adm0_a3'];

        return $item;
    }

    /**
     * Show the progress.
     *
     * @param string $string
     */
    private function progress($string = '')
    {
        if (is_null($this->command)) {
            dump($string);

            return;
        }

        $this->command->line($string);
    }

    /**
     * Update all data.
     *
     * @param $command
     */
    public function update($command)
    {
        $this->command = $command;

        $this->downloadFiles();

        $this->updateStates();

        $this->updateCities();

        $this->updateCountries();

        deltree($this->dataDir('natural_earth'));

        deltree($this->dataDir('mledoze'));
    }

    /**
     * Update cities.
     */
    public function updateCities()
    {
        $this->progress('Updating cities...');

        $this->eraseDataDir($dataDir = '/cities/default/');

        $result = $this->loadShapeFile('natural_earth/ne_10m_populated_places');

        list($countries, $cities) = $this->generateJsonFiles($result, $dataDir, function ($item) {
            return snake_case(strtolower($item['nameascii']));
        });

        $this->progress("Generated {$cities} cities for {$countries} countries.");
    }

    /**
     * Update countries.
     */
    public function updateCountries()
    {
        $this->progress('Updating countries...');

        $this->eraseDataDir($dataDir = '/countries/default/');

        $countries = $this->buildCountriesCollection($dataDir);

        $this->putFile(
            $this->makeJsonFileName('_all_countries', $dataDir),
            $countries->toJson(JSON_PRETTY_PRINT)
        );

        $this->progress('Generated '.$countries->count().' countries.');
    }

    /**
     * Merge the two countries sources.
     *
     * @param \PragmaRX\Countries\Package\Support\Collection $country1
     * @param \PragmaRX\Countries\Package\Support\Collection $country2
     * @return mixed
     */
    public function mergeCountries($country1, $country2)
    {
        if (is_null($country2)) {
            return $country1;
        }

        foreach ($country1 as $key => $value) {
            $got = $country2->get($key);

            if (is_null($got)) {
                $country2[$key] = $value;

                continue;
            }

            if ($got !== $value) {
                $country2[$key.'_nev'] = $value; // Natural Earth Vector
            }
        }

        return $country2->sortBy(function ($value, $key) {
            return $key;
        });
    }

    /**
     * Update states.
     */
    public function updateStates()
    {
        $this->progress('Updating states...');

        $this->eraseDataDir($dataDir = '/states/default');

        $result = $this->loadShapeFile('natural_earth/ne_10m_admin_1_states_provinces');

        list($countries, $states) = $this->generateJsonFiles($result, $dataDir, function ($item) {
            return $this->makeStatePostalCode($item);
        });

        $this->progress("Generated {$states} states for {$countries} countries.");
    }
}
