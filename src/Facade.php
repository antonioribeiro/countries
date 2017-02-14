<?php

namespace PragmaRX\Countries;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @see \Collective\Html\FormBuilder
 */
class Facade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pragmarx.countries';
    }
}
