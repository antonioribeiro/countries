<?php

namespace PragmaRX\Countries\Support;

use Commercie\Currency\ResourceRepository;

class CurrenciesRepository extends ResourceRepository
{
    /**
     * Create a currency from a json.
     *
     * @param string $json
     * @return mixed
     */
    protected function createCurrencyFromJson($json)
    {
        return json_decode($json, true);
    }

    /**
     * Get all currencies.
     *
     * @return mixed
     */
    public function all()
    {
        return $this->listCurrencies();
    }
}
