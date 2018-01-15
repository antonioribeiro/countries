<?php

namespace CommerceGuys\Tax\Model;

interface TaxRateAmountEntityInterface extends TaxRateAmountInterface
{
    /**
     * Sets the tax rate.
     *
     * @param TaxRateEntityInterface|null $rate The tax rate.
     *
     * @return self
     */
    public function setRate(TaxRateEntityInterface $rate = null);

    /**
     * Sets the tax rate amount id.
     *
     * @param string $id The tax rate amount id.
     *
     * @return self
     */
    public function setId($id);

    /**
     * Sets the decimal tax rate amount.
     *
     * @param float $amount The tax rate amount expressed as a decimal.
     *
     * @return self
     */
    public function setAmount($amount);

    /**
     * Sets the tax rate amount start date.
     *
     * @param \DateTime $startDate The tax rate amount start date.
     *
     * @return self
     */
    public function setStartDate(\DateTime $startDate);

    /**
     * Sets the tax rate amount end date.
     *
     * @param \DateTime $endDate The tax rate amount end date.
     *
     * @return self
     */
    public function setEndDate(\DateTime $endDate);
}
