<?php

namespace PragmaRX\Countries\Package\Support;

use ShapeFile\ShapeFile;

class UpdateData extends Base
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    protected $command;

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

    /**
     * @param $result
     * @return mixed
     */
    private function generateStatesJsonFiles($result)
    {
        $count = countriesCollect($result)->map(function ($item) {
            return $this->normalize($item);
        })->groupBy('grouping')->each(function ($item, $key) {
            file_put_contents($this->makeStateFileName($key), json_encode($item));
        })->count()
        ;

        return $count;
    }

    /**
     * Load the shape file (DBF) to array.
     *
     * @return array
     */
    private function loadShapeFile()
    {
        $this->progress('Loading shape file...');

        $shapeRecords = new ShapeFile($this->dataDir('natural_earth/ne_10m_admin_1_states_provinces'));

        $result = [];

        foreach ($shapeRecords as $record) {
            if ($record['dbf']['_deleted']) {
                continue;
            }

            $result[] = $record['dbf'];
        }

        unset($shapeRecords);

        deltree($this->dataDir('natural_earth'));

        return $result;
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
     * Rename wrong states json files.
     */
    private function renameWrongStatesJsonFiles()
    {
        rename($this->dataDir('/states/fxx.json'), $this->dataDir('/states/fra.json'));
    }

    /**
     * Import data.
     */
    public function updateAdminStates()
    {
        $result = $this->loadShapeFile();

        $this->progress('Updating json files...');

        $count = $this->generateStatesJsonFiles($result);

        $this->renameWrongStatesJsonFiles();

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
     * Update all data.
     *
     * @param $command
     */
    public function update($command)
    {
        $this->command = $command;

        $this->downloadFiles();

        $this->updateAdminStates();
    }
}
