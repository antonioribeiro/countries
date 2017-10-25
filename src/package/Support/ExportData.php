<?php

namespace PragmaRX\Countries\Package\Support;

class ExportData
{
    /**
     * @param \Illuminate\Console\Command $line
     */
    private $command;

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
     * Generate export data.
     *
     * @param $file
     * @return mixed
     */
    protected function generateExportData($file)
    {
        $this->command->line('Generating exportable data...');

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
     * @return string
     */
    protected function getDataDirectory(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
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
        return __DIR__.
                DIRECTORY_SEPARATOR.
                '..'.
                DIRECTORY_SEPARATOR.
                'data'.
                DIRECTORY_SEPARATOR.
                'ne_10m_admin_1_states_provinces.txt';
    }

    /**
     * Import data.
     */
    public function exportAdminStates()
    {
        $result = $this->generateExportData($this->readSourceFile());

        $this->command->line('Exporting json files...');

        collect($result)->map(function ($item) {
            return $this->normalize($item);
        })->groupBy('grouping')->each(function ($item, $key) {
            file_put_contents(dd($this->makeStateFileName($key)), json_encode($item));
        });
    }

    /**
     * Make state json filename.
     *
     * @param $key
     * @return string
     */
    protected function makeStateFileName($key)
    {
        return __DIR__.
            DIRECTORY_SEPARATOR.
            '..'.
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            'states'.
            DIRECTORY_SEPARATOR.
            strtolower($key).
            '.json';
    }

    /**
     * Read source file.
     *
     * @return array
     */
    protected function readSourceFile()
    {
        $this->command->line('Reading source file: '.$file = $this->getSourceFileName());

        $file = file($file, FILE_IGNORE_NEW_LINES);

        return $file;
    }

    /**
     * Export timezones to json.
     */
    public function exportTimezones()
    {
        $timezones = require $this->getDataDirectory().'timezones.php';

        file_put_contents($this->getDataDirectory().'timezones.json', json_encode($timezones));
    }

    public function update($command)
    {
        $this->command = $command;

        $this->exportAdminStates();
    }
}
