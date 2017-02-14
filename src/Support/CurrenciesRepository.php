<?php

namespace PragmaRX\Countries\Support;

use Commercie\Currency\ResourceRepository;

class CurrenciesRepository extends ResourceRepository
{
    protected function createCurrencyFromJson($json)
    {
        return json_decode($json, true);
    }
}
