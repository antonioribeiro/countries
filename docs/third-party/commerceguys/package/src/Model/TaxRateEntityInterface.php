<?php

namespace CommerceGuys\Tax\Model;

use Doctrine\Common\Collections\Collection;

interface TaxRateEntityInterface extends TaxRateInterface
{
    /**
     * Sets the tax type.
     *
     * @param TaxTypeEntityInterface|null $type The tax type.
     *
     * @return self
     */
    public function setType(TaxTypeEntityInterface $type = null);

    /**
     * Sets the tax rate id.
     *
     * @param string $id The tax rate id.
     *
     * @return self
     */
    public function setId($id);

    /**
     * Sets the tax rate name.
     *
     * @param string $name The tax rate name.
     *
     * @return self
     */
    public function setName($name);

    /**
     * Sets whether the tax rate is the default for its tax type.
     *
     * @param bool $default Whether the tax rate is the default.
     *
     * @return self
     */
    public function setDefault($default);

    /**
     * Sets the tax rate amounts.
     *
     * @param TaxRateAmountEntityInterface[] $amounts The tax rate amounts.
     *
     * @return self
     */
    public function setAmounts(Collection $amounts);

    /**
     * Adds a tax rate amount.
     *
     * @param TaxRateAmountEntityInterface $amount The tax rate amount.
     *
     * @return self
     */
    public function addAmount(TaxRateAmountEntityInterface $amount);

    /**
     * Removes a tax rate amount.
     *
     * @param TaxRateAmountEntityInterface $amount The tax rate amount.
     *
     * @return self
     */
    public function removeAmount(TaxRateAmountEntityInterface $amount);

    /**
     * Checks whether the tax rate has a tax rate amount.
     *
     * @param TaxRateAmountEntityInterface $amount The tax rate amount.
     *
     * @return bool True if the tax rate amount was found, false otherwise.
     */
    public function hasAmount(TaxRateAmountEntityInterface $amount);
}
