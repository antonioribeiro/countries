<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Addressing\Address;
use CommerceGuys\Tax\Model\TaxTypeInterface;
use CommerceGuys\Tax\Resolver\Context;
use CommerceGuys\Zone\Model\ZoneInterface;

trait StoreRegistrationCheckerTrait
{
    /**
     * The empty addresses constructed for checking store registrations.
     *
     * @var Address[]
     */
    protected $emptyAddresses = [];

    /**
     * Checks whether the store is registered to collect taxes in the given zone.
     *
     * @param ZoneInterface $zone    The zone.
     * @param Context       $context The context containing store information.
     *
     * @return bool True if the store is registered to collect taxes in the
     *              given zone, false otherwise.
     */
    protected function checkStoreRegistration(ZoneInterface $zone, Context $context)
    {
        $storeRegistrations = $context->getStoreRegistrations();
        foreach ($storeRegistrations as $country) {
            if (!isset($this->emptyAddresses[$country])) {
                $this->emptyAddresses[$country] = new Address($country);
            }

            if ($zone->match($this->emptyAddresses[$country])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filters out tax types not matching the store registration.
     *
     * @param TaxTypeInterface[] $taxTypes The tax types to filter.
     * @param Context            $context  The context containing store information.
     *
     * @return TaxTypeInterface[] An array of additional tax types the store is
     *                            registered to collect.
     */
    protected function filterByStoreRegistration(array $taxTypes, Context $context)
    {
        $taxTypes = array_filter($taxTypes, function ($taxType) use ($context) {
            $zone = $taxType->getZone();

            return $this->checkStoreRegistration($zone, $context);
        });

        return $taxTypes;
    }
}
