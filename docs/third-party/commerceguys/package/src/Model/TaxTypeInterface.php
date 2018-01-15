<?php

namespace CommerceGuys\Tax\Model;

use CommerceGuys\Zone\Model\ZoneInterface;

interface TaxTypeInterface
{
    // Rounding modes.
    const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    /**
     * Gets the tax type id.
     *
     * @return string
     */
    public function getId();

    /**
     * Gets the tax type name.
     *
     * For example, "German VAT".
     *
     * @return string The tax type name.
     */
    public function getName();

    /**
     * Gets the tax type generic label.
     *
     * Used to identify the applied tax in cart and order summaries.
     * Represented by one of the GenericLabel values, it is mapped to a
     * translated string by the implementing application.
     *
     * @return string The tax type generic label.
     */
    public function getGenericLabel();

    /**
     * Gets whether the tax type is compound.
     *
     * Compound tax is calculated on top of a primary tax.
     * For example, Canada's Provincial Sales Tax (PST) is compound, calculated
     * on a price that already includes the Goods and Services Tax (GST).
     *
     * @return bool True if the tax type is compound, false otherwise.
     */
    public function isCompound();

    /**
     * Gets whether the tax type is display inclusive.
     *
     * E.g. US sales tax is not display inclusive, a $5 price is shown as $5
     * even if a $1 tax has been calculated. In France, a 5€ price is shown as
     * 6€ if a 1€ tax was calculated, because French VAT is display inclusive.
     *
     * @return bool True if the tax type is display inclusive, false otherwise.
     */
    public function isDisplayInclusive();

    /**
     * Gets the tax type rounding mode.
     *
     * @return int The tax type rounding mode, a ROUND_ constant.
     */
    public function getRoundingMode();

    /**
     * Gets the tax type zone.
     *
     * @return ZoneInterface The tax type zone.
     */
    public function getZone();

    /**
     * Gets the tax type tag.
     *
     * Used by the resolvers to analyze only the tax types relevant to them.
     * For example, the EuTaxTypeResolver would analyze only the tax types
     * with the "EU" tag.
     *
     * @return string The tax type tag.
     */
    public function getTag();

    /**
     * Gets the tax rates.
     *
     * @return TaxRateInterface[] The tax rates.
     */
    public function getRates();

    /**
     * Checks whether the tax type has tax rates.
     *
     * @return bool True if the tax type has tax rates, false otherwise.
     */
    public function hasRates();
}
