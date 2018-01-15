<?php

namespace CommerceGuys\Tax\Model;

use Doctrine\Common\Collections\Collection;
use CommerceGuys\Zone\Model\ZoneEntityInterface;

interface TaxTypeEntityInterface extends TaxTypeInterface
{
    /**
     * Sets the tax type id.
     *
     * @param string $id The tax type id.
     *
     * @return self
     */
    public function setId($id);

    /**
     * Sets the tax type name.
     *
     * @param string $name The tax type name.
     *
     * @return self
     */
    public function setName($name);

    /**
     * Sets the tax type generic label.
     *
     * @param string $genericLabel The tax type generic label.
     *
     * @return self
     */
    public function setGenericLabel($genericLabel);

    /**
     * Sets whether the tax type is compound.
     *
     * @param bool $compound Whether the tax type is compound.
     *
     * @return self
     */
    public function setCompound($compound);

    /**
     * Sets whether the tax type is display inclusive.
     *
     * @param bool $displayInclusive Whether the tax type is display inclusive.
     *
     * @return self
     */
    public function setDisplayInclusive($displayInclusive);

    /**
     * Sets the tax type rounding mode.
     *
     * @param int $roundingMode The tax type rounding mode, a ROUND_ constant.
     */
    public function setRoundingMode($roundingMode);

    /**
     * Sets the tax type zone.
     *
     * @param ZoneEntityInterface $zone The tax type zone.
     *
     * @return self
     */
    public function setZone(ZoneEntityInterface $zone);

    /**
     * Sets the tax type tag.
     *
     * @param string $tag The tax type tag.
     *
     * @return self
     */
    public function setTag($tag);

    /**
     * Sets the tax rates.
     *
     * @param TaxRateEntityInterface[] $rates The tax rates.
     *
     * @return self
     */
    public function setRates(Collection $rates);

    /**
     * Adds a tax rate.
     *
     * @param TaxRateEntityInterface $rate The tax rate.
     *
     * @return self
     */
    public function addRate(TaxRateEntityInterface $rate);

    /**
     * Removes a tax rate.
     *
     * @param TaxRateEntityInterface $rate The tax rate.
     *
     * @return self
     */
    public function removeRate(TaxRateEntityInterface $rate);

    /**
     * Checks whether the tax type has a tax rate.
     *
     * @param TaxRateEntityInterface $rate The tax rate.
     *
     * @return bool True if the tax rate was found, false otherwise.
     */
    public function hasRate(TaxRateEntityInterface $rate);
}
