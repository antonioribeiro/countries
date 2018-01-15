<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Repository\TaxTypeRepositoryInterface;
use CommerceGuys\Tax\Resolver\Context;

/**
 * Resolver for Canada's tax types (HST, PST, GST).
 */
class CanadaTaxTypeResolver implements TaxTypeResolverInterface
{
    /**
     * The tax type repository.
     *
     * @param TaxTypeRepositoryInterface
     */
    protected $taxTypeRepository;

    /**
     * Creates a CanadaTaxTypeResolver instance.
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
        $customerAddress = $context->getCustomerAddress();
        $storeAddress = $context->getStoreAddress();
        if ($customerAddress->getCountryCode() != 'CA' || $storeAddress->getCountryCode() != 'CA') {
            // The customer or the store is not in Canada.
            return [];
        }

        // Canadian tax types are matched by the customer address.
        // If the customer is from Ontario, the tax types are for Ontario.
        $taxTypes = $this->getTaxTypes();
        $results = [];
        foreach ($taxTypes as $taxType) {
            $zone = $taxType->getZone();
            if ($zone->match($customerAddress)) {
                $results[] = $taxType;
            }
        }

        return $results;
    }

    /**
     * Returns the Canadian tax types.
     *
     * @return TaxTypeInterface[] An array of Canadian tax types.
     */
    protected function getTaxTypes()
    {
        $taxTypes = $this->taxTypeRepository->getAll();
        $taxTypes = array_filter($taxTypes, function ($taxType) {
            return $taxType->getTag() == 'CA';
        });

        return $taxTypes;
    }
}
