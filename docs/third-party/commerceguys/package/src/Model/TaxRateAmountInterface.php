<?php

namespace CommerceGuys\Tax\Model;

interface TaxRateAmountInterface
{
    /**
     * Gets the tax rate.
     *
     * @return TaxRateInterface The tax rate.
     */
    public function getRate();

    /**
     * Gets the tax rate amount id.
     *
     * @return string The tax rate amount id.
     */
    public function getId();

    /**
     * Gets the decimal tax rate amount.
     *
     * For example, 0.2 for a 20% tax rate.
     *
     * @return float The tax rate amount expressed as a decimal.
     */
    public function getAmount();

    /**
     * Gets the tax rate amount start date.
     *
     * @return \DateTime|null The tax rate amount start date, if known.
     */
    public function getStartDate();

    /**
     * Gets the tax rate amount end date.
     *
     * @return \DateTime|null The tax rate amount end date, if known.
     */
    public function getEndDate();
}
