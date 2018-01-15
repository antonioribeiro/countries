<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Country Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Country Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

use Rinvex\Country\CountryLoader;

if (! function_exists('country')) {
    /**
     * Get the country by it's ISO 3166-1 alpha-2.
     *
     * @param string $code
     * @param bool   $hydrate
     *
     * @return \Rinvex\Country\Country|array
     */
    function country($code, $hydrate = true)
    {
        return CountryLoader::country($code, $hydrate);
    }
}

if (! function_exists('countries')) {
    /**
     * Get all countries short-listed.
     *
     * @param bool $longlist
     * @param bool $hydrate
     *
     * @return array
     */
    function countries($longlist = false, $hydrate = false)
    {
        return CountryLoader::countries($longlist, $hydrate);
    }
}
