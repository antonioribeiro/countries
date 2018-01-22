<?php

namespace PragmaRX\Countries\Package\Contracts;

interface Config
{
    /**
     * Get a configu key.
     *
     * @param $key
     * @return mixed
     */
    public function get($key);
}
