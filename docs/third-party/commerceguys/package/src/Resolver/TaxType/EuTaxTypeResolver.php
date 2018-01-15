<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Model\TaxTypeInterface;
use CommerceGuys\Tax\Repository\TaxTypeRepositoryInterface;
use CommerceGuys\Tax\Resolver\Context;

/**
 * Resolver for EU VAT.
 */
class EuTaxTypeResolver implements TaxTypeResolverInterface
{
    use StoreRegistrationCheckerTrait;

    /**
     * The tax type repository.
     *
     * @param TaxTypeRepositoryInterface
     */
    protected $taxTypeRepository;

    /**
     * Creates a EuTaxTypeResolver instance.
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
        $customerAddress = $context->getCustomerAddress();
        $customerCountry = $customerAddress->getCountryCode();
        $customerTaxTypes = $this->filterByAddress($taxTypes, $customerAddress);
        if (empty($customerTaxTypes)) {
            // The customer is not in the EU.
            return [];
        }
        $storeAddress = $context->getStoreAddress();
        $storeCountry = $storeAddress->getCountryCode();
        $storeTaxTypes = $this->filterByAddress($taxTypes, $storeAddress);
        $storeRegistrationTaxTypes = $this->filterByStoreRegistration($taxTypes, $context);
        if (empty($storeTaxTypes) && empty($storeRegistrationTaxTypes)) {
            // The store is not in the EU nor registered to collect EU VAT.
            return [];
        }

        $customerTaxNumber = $context->getCustomerTaxNumber();
        // Since january 1st 2015 all digital services sold to EU customers
        // must apply the destination tax type(s). For example, an ebook sold
        // to Germany needs to have German VAT applied.
        $isDigital = $context->getDate()->format('Y') >= '2015' && !$taxable->isPhysical();

        $resolvedTaxTypes = [];
        if (empty($storeTaxTypes) && !empty($storeRegistrationTaxTypes)) {
            // The store is not in the EU but is registered to collect VAT.
            // This VAT is only charged on B2C digital services.
            $resolvedTaxTypes = self::NO_APPLICABLE_TAX_TYPE;
            if ($isDigital && !$customerTaxNumber) {
                $resolvedTaxTypes = $customerTaxTypes;
            }
        } elseif ($customerTaxNumber && $customerCountry != $storeCountry) {
            // Intra-community supply (B2B).
            $icTaxType = $this->taxTypeRepository->get('eu_ic_vat');
            $resolvedTaxTypes = [$icTaxType];
        } elseif ($isDigital) {
            $resolvedTaxTypes = $customerTaxTypes;
        } else {
            // Physical products use the origin tax types, unless the store is
            // registered to pay taxes in the destination zone. This is required
            // when the total yearly transactions breach the defined threshold.
            // See http://www.vatlive.com/eu-vat-rules/vat-registration-threshold/
            $resolvedTaxTypes = $storeTaxTypes;
            $customerTaxType = reset($customerTaxTypes);
            if ($this->checkStoreRegistration($customerTaxType->getZone(), $context)) {
                $resolvedTaxTypes = $customerTaxTypes;
            }
        }

        return $resolvedTaxTypes;
    }

    /**
     * Filters out tax types not matching the provided address.
     *
     * @param TaxTypeInterface[] $taxTypes The tax types to filter.
     * @param AddressInterface   $address  The address to filter by.
     *
     * @return TaxTypeInterface[] An array of tax types whose zones match the
     *                            provided address.
     */
    protected function filterByAddress(array $taxTypes, AddressInterface $address)
    {
        $taxTypes = array_filter($taxTypes, function ($taxType) use ($address) {
            $zone = $taxType->getZone();

            return $zone->match($address);
        });

        return $taxTypes;
    }

    /**
     * Returns the EU tax types.
     *
     * @return TaxTypeInterface[] An array of EU tax types.
     */
    protected function getTaxTypes()
    {
        $taxTypes = $this->taxTypeRepository->getAll();
        $taxTypes = array_filter($taxTypes, function ($taxType) {
            // "eu_ic_vat" is not resolved via its zone, so it isn't needed.
            return $taxType->getId() != 'eu_ic_vat' && $taxType->getTag() == 'EU';
        });

        return $taxTypes;
    }
}
