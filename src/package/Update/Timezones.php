<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\General;

class Timezones extends Base
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
     * Rinvex constructor.
     *
     * @param General $general
     * @param Updater $updater
     */
    public function __construct(General $general, Updater $updater)
    {
        $this->general = $general;

        $this->updater = $updater;
    }


    public function update()
    {
        $this->general->eraseDataDir($dataDir = '/timezones');

        $this->general->progress('Loading countries...');

        $countries = cache()->remember(
            'updateTimezone.countries', 160,
            function () {
                return $this->general->loadCsv($this->general->dataDir('third-party/timezonedb/country.csv'));
            }
        );

        $this->general->progress('Loading zones...');

        $zones = cache()->remember(
            'updateTimezone.zones', 160,
            function () {
                return $this->general->loadCsv($this->general->dataDir('third-party/timezonedb/zone.csv'))->mapWithKeys(function ($value) {
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

        $this->general->progress('Loading timezones...');

        $timezones = cache()->remember(
            'updateTimezone.timezones', 160,
            function () {
                return $this->general->loadCsv($this->general->dataDir('third-party/timezonedb/timezone.csv'))->map(function ($timezone) {
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

        $this->general->progress('Generating abbreviations...');

        $abbreviations = cache()->remember(
            'updateTimezone.abbreviations', 160,
            function () use ($timezones) {
                return $timezones->groupBy('zone_id')->map(function (Coollection $timezones) {
                    return $timezones->map(function ($timezone) {
                        return $timezone['abbreviation'];
                    })->unique()->sort()->values();
                });
            }
        );

        $this->general->progress('Updating countries timezones...');

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

            list($country) = $this->updater->findByFields($this->updater->getCountries(), $item, $fields, 'cca2');

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

                $zone = $this->updater->addDataSource($zone, 'timezonedb');

                $zone = $this->updater->addRecordType($zone, 'timezone');

                return [$this->zoneNameSnake($zone['zone_name']) => $zone];
            });

            return $country;
        });

        $this->general->message('Generating timezone files...');

        $getCountryCodeClosure = function () {
        };

        $normalizeCountryClosure = function ($country) {
            return [$country['timezones']];
        };

        $dummyClosure = function ($country) {
            return $country;
        };

        $this->updater->generateJsonFiles($countries, "$dataDir/countries/default", $normalizeCountryClosure, $getCountryCodeClosure, $dummyClosure, '');

        $this->updater->generateJsonFiles($timezones, "$dataDir/timezones/default", $dummyClosure, null, $dummyClosure, 'zone_id');

        $this->general->progress('Generated timezones for '.count($countries).' countries.');

        $this->general->progress('Generated '.count($timezones).' timezones.');
    }

    /**
     * Get the zone name from a timezone.
     *
     * @param $country
     * @return string
     */
    public function getZoneName($country): string
    {
        return isset($country['timezones']['zone_name'])
            ? $country['timezones']['zone_name']
            : 'unknown';
    }

    /**
     * @param $name
     * @return string
     */
    public function zoneNameSnake($name)
    {
        return snake_case(str_replace(['\\', '/', '__'], ['_', ''], $name));
    }
}
