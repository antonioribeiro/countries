<?php

namespace CommerceGuys\Tax\Model;

use CommerceGuys\Tax\Enum\GenericLabel;
use CommerceGuys\Zone\Model\ZoneEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Default tax type implementation.
 *
 * Can be mapped and used by Doctrine.
 */
class TaxType implements TaxTypeEntityInterface
{
    /**
     * The tax type id.
     *
     * @var string
     */
    protected $id;

    /**
     * The tax type name.
     *
     * @var string
     */
    protected $name;

    /**
     * The tax type generic label.
     *
     * @var string
     */
    protected $genericLabel;

    /**
     * Whether the tax type is compound.
     *
     * @var bool
     */
    protected $compound = false;

    /**
     * Whether the tax type is display inclusive.
     *
     * @var bool
     */
    protected $displayInclusive = false;

    /**
     * The tax type rounding mode.
     *
     * @var int
     */
    protected $roundingMode;

    /**
     * The tax type zone.
     *
     * @var ZoneEntityInterface
     */
    protected $zone;

    /**
     * The tax type tag.
     *
     * @var string
     */
    protected $tag;

    /**
     * The tax rates.
     *
     * @var TaxRateEntityInterface[]
     */
    protected $rates;

    /**
     * Creates a TaxType instance.
     */
    public function __construct()
    {
        $this->genericLabel = GenericLabel::getDefault();
        $this->rates = new ArrayCollection();
    }

    /**
     * Returns the string representation of the tax type.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenericLabel()
    {
        return $this->genericLabel;
    }

    /**
     * {@inheritdoc}
     */
    public function setGenericLabel($genericLabel)
    {
        GenericLabel::assertExists($genericLabel);
        $this->genericLabel = $genericLabel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompound()
    {
        return !empty($this->compound);
    }

    /**
     * {@inheritdoc}
     */
    public function setCompound($compound)
    {
        $this->compound = $compound;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayInclusive()
    {
        return !empty($this->displayInclusive);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayInclusive($displayInclusive)
    {
        $this->displayInclusive = $displayInclusive;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoundingMode()
    {
        return $this->roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function setRoundingMode($roundingMode)
    {
        $this->roundingMode = $roundingMode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * {@inheritdoc}
     */
    public function setZone(ZoneEntityInterface $zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * {@inheritdoc}
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRates()
    {
        return $this->rates;
    }

    /**
     * {@inheritdoc}
     */
    public function setRates(Collection $rates)
    {
        $this->rates = $rates;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRates()
    {
        return !$this->rates->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function addRate(TaxRateEntityInterface $rate)
    {
        if (!$this->hasRate($rate)) {
            $rate->setType($this);
            $this->rates->add($rate);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRate(TaxRateEntityInterface $rate)
    {
        if ($this->hasRate($rate)) {
            $this->rates->removeElement($rate);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRate(TaxRateEntityInterface $rate)
    {
        return $this->rates->contains($rate);
    }
}
