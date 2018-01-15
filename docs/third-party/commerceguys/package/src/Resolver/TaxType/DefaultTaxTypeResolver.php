<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Repository\TaxTypeRepositoryInterface;
use CommerceGuys\Tax\Resolver\Context;

/**
 * Default resolver.
 *
 * Meant to run last in the process.
 * A tax type applies if both the store and the customer are in the same zone
 * (e.g. the customer is in Serbia and the store is in Serbia) OR the store is
 * registered to collect taxes in the customer's zone (e.g. the store is in
 * Serbia and the customer is in Montenegro, but the store is registered to
 * collect Montenegrin VAT).
 */
class DefaultTaxTypeResolver implements TaxTypeResolverInterface
{
    use StoreRegistrationCheckerTrait;

    /**
     * The tax type repository.
     *
     * @param TaxTypeRepositoryInterface
     */
    protected $taxTypeRepository;

    /**
     * Creates a DefaultTaxTypeResolver instance.
     *
     * @param TaxTypeRepositoryInterface $taxTypeRepository The tax type repository.
     */
    public function __construct(TaxTypeRepositoryInterface $taxTypeRepository)
    {
        $this->taxTypeRepository = $taxTypeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(TaxableInterface $taxable, Context $context)
    {
        $taxTypes = $this->getTaxTypes();
        $results = [];
        foreach ($taxTypes as $taxType) {
            $zone = $taxType->getZone();
            $customerZoneMatch = $zone->match($context->getCustomerAddress());
            $storeZoneMatch = $zone->match($context->getStoreAddress());
            if ($customerZoneMatch && $storeZoneMatch) {
                // The customer and store belong to the same zone.
                $results[] = $taxType;
            } elseif ($customerZoneMatch && $this->checkStoreRegistration($zone, $context)) {
                // The customer belongs to the zone, and the store is
                // registered to collect taxes there.
                $results[] = $taxType;
            }
        }

        return $results;
    }

    /**
     * Returns the non-tagged tax types.
     *
     * It is assumed that the tagged tax types have already been evaluated by
     * other resolvers.
     *
     * @return TaxTypeInterface[] An array of non-tagged tax types.
     */
    protected function getTaxTypes()
    {
        $taxTypes = $this->taxTypeRepository->getAll();
        $taxTypes = array_filter($taxTypes, function ($taxType) {
            $tag = $taxType->getTag();

            return empty($tag);
        });

        return $taxTypes;
    }
}
