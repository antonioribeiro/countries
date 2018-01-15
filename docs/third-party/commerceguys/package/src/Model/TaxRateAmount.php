<?php

namespace CommerceGuys\Tax\Model;

/**
 * Default tax rate amount implementation.
 *
 * Can be mapped and used by Doctrine.
 */
class TaxRateAmount implements TaxRateAmountEntityInterface
{
    /**
     * The tax rate.
     *
     * @var TaxRateEntityInterface
     */
    protected $rate;

    /**
     * The tax rate amount id.
     *
     * @var string
     */
    protected $id;

    /**
     * The tax rate amount.
     *
     * @var float
     */
    protected $amount;

    /**
     * The tax rate amount start date.
     *
     * @var \DateTime
     */
    protected $startDate;

    /**
     * The tax rate amount end date.
     *
     * @var \DateTime
     */
    protected $endDate;

    /**
     * {@inheritdoc}
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * {@inheritdoc}
     */
    public function setRate(TaxRateEntityInterface $rate = null)
    {
        $this->rate = $rate;

        return $this;
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * {@inheritdoc}
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * {@inheritdoc}
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * {@inheritdoc}
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }
}
