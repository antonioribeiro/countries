<?php

namespace PragmaRX\Countries\Support;

class ExportAdminStates
{
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

    public function import()
    {
        $file = $this->readSourceFile();

        $result = [];

        $counter = -1;

        foreach ($file as $line) {
            if (! trim($line)) {
                continue;
            }

            list($field, $value) = explode(':', $line);

            $field = str_replace(' ', '_', trim($field));
            $value = trim($value);

            if ($field == 'adm1_code') {
                $counter++;
                $result[$counter] = [];
            }

            $result[$counter][$field] = $value;
        }

        collect($result)->groupBy('gu_a3')->each(function($item, $key) {
            file_put_contents($this->makeStateFileName($key), json_encode($item));
        });
    }

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
     * @return array
     */
    protected function readSourceFile()
    {
        $file = file($this->getSourceFileName(), FILE_IGNORE_NEW_LINES);

        return $file;
    }
}
