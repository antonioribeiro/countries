<?php

namespace PragmaRX\Countries\Package\Support;

use ShapeFile\ShapeFile;

class UpdateData
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    private $command;

    private function downloadFile($url, $path)
    {
        $path = $this->dataDir($path);

        $destination = _dir("{$path}/".basename($url));

        $this->makeDir($path);

        $this->progress("Downloading {$url} to {$destination}...");

        file_put_contents(
            $destination,
            file_get_contents($url),
            0644
        );
    }

    private function downloadFiles()
    {
        collect(config('countries.data.downloadable'))->each(function ($urls, $path) {
            collect((array) $urls)->each(function ($url) use ($path) {
                $this->downloadFile($url, $path);
            });
        });
    }

    /**
     * @param $line
     * @return array
     */
    protected function extractFieldValue($line): array
    {
        list($field, $value) = explode(':', $line);

        $field = str_replace(' ', '_', trim($field));
        $value = trim($value);

        return [$field, $value];
    }

    /**
     * Generate update data.
     *
     * @param $file
     * @return mixed
     */
    protected function generateUpdateData($file)
    {
        $this->progress('Generating updetable data...');

        $result = [];

        $counter = -1;

        foreach (array_filter($file) as $line) {
            list($field, $value) = $this->extractFieldValue($line);

            if ($field == 'adm1_code') {
                $counter++;
            }

            $result[$counter][$field] = $value;
        }

        return $result;
    }

    /**
     * Get data directory.
     *
     * @param $path
     * @return string
     */
    protected function dataDir($path = '')
    {
        $path = empty($path) ? '' : "/{$path}";

        return __COUNTRIES_DIR__._dir("/src/data$path");
    }

    private function loadShapeFile()
    {
        $this->progress('Loading shape file...');

        $shapeRecords = new ShapeFile($this->dataDir('natural_earth_data/ne_10m_admin_1_states_provinces'));

        $result = [];

        foreach ($shapeRecords as $record) {
            if ($record['dbf']['_deleted']) {
                continue;
            }

            $result[] = $record['dbf'];
        }

        return $result;
    }

    /**
     * @param $path
     */
    private function makeDir($path)
    {
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Normalize data.
     *
     * @param $item
     * @return mixed
     */
    private function normalize($item)
    {
        if ($item['hasc_maybe'] == 'BR.RO|BRA-RND') {
            $item['postal'] = 'RO';
        }

        $item['grouping'] = $item['gu_a3'] ?: $item['adm0_a3'];

        return $item;
    }

    /**
     * Get data source filename.
     *
     * @return string
     */
    protected function getSourceFileName()
    {
        return $this->dataDir()._dir('/ne_10m_admin_1_states_provinces.txt');
    }

    private function progress($string = '')
    {
        if (is_null($this->command)) {
            dump($string);

            return;
        }

        $this->command->line($string);
    }

    /**
     * Import data.
     */
    public function updateAdminStates()
    {
        $result = $this->loadShapeFile();

        $this->progress('Updating json files...');

        $count = collect($result)->map(function ($item) {
            return $this->normalize($item);
        })->groupBy('grouping')->each(function ($item, $key) {
            file_put_contents($this->makeStateFileName($key), json_encode($item));
        })->count();

        $this->progress("Generated {$count} .json files.");
    }

    /**
     * Make state json filename.
     *
     * @param $key
     * @return string
     */
    protected function makeStateFileName($key)
    {
        return $this->dataDir('/states/'.strtolower($key).'.json');
    }

    /**
     * Read source file.
     *
     * @return array
     */
    protected function readSourceFile()
    {
        $this->progress('Reading source file: '.$file = $this->getSourceFileName());

        $file = file($file, FILE_IGNORE_NEW_LINES);

        return $file;
    }

    /**
     * Update timezones to json.
     */
    public function updateTimezones()
    {
        $timezones = require $this->dataDir('timezones.php');

        file_put_contents($this->dataDir().'timezones.json', json_encode($timezones));
    }

    public function update($command)
    {
        $this->command = $command;

        $this->downloadFiles();

        $this->updateAdminStates();
    }
}
