<?php

namespace CommerceGuys\Tax\Model;

interface TaxRateInterface
{
    /**
     * Gets the tax type.
     *
     * @return TaxTypeInterface The tax type.
     */
    public function getType();

    /**
     * Gets the tax rate id.
     *
     * @return string The tax rate id.
     */
    public function getId();

    /**
     * Gets the tax rate name.
     *
     * Used to identify the tax rate on administration pages.
     * For example, "Standard".
     *
     * @return string The tax rate name.
     */
    public function getName();

    /**
     * Gets whether the tax rate is the default for its tax type.
     *
     * When resolving the tax rate for a specific tax type, the default tax
     * rate is returned if no other resolver provides a more applicable one.
     *
     * @return bool True if the tax rate is the default, false otherwise.
     */
    public function isDefault();

    /**
     * Gets the tax rate amounts.
     *
     * @return TaxRateAmountInterface[] The tax rate amounts.
     */
    public function getAmounts();

    /**
     * Gets the tax rate amount valid for the provided date.
     *
     * @param \DateTime $date The date.
     *
     * @return TaxRateAmountInterface|null The tax rate amount, if matched.
     */
    public function getAmount(\DateTime $date);

    /**
     * Checks whether the tax rate has tax rate amounts.
     *
     * @return bool True if the tax rate has tax rate amounts, false otherwise.
     */
    public function hasAmounts();
}
