<?php

namespace CommerceGuys\Tax;

interface TaxableInterface
{
    /**
     * Returns whether the taxable item is physical (i.e. shippable).
     *
     * Used by tax resolvers to distinguish physical items from digital products
     * and services, which are often taxed differently (in the EU, for example).
     *
     * @return bool True if the taxable item is physical, false otherwise.
     */
    public function isPhysical();
}
