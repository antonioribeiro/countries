<?php

namespace CommerceGuys\Tax\Repository;

use CommerceGuys\Tax\Exception\UnknownTaxTypeException;
use CommerceGuys\Tax\Model\TaxType;
use CommerceGuys\Tax\Model\TaxRate;
use CommerceGuys\Tax\Model\TaxRateAmount;
use CommerceGuys\Zone\Repository\ZoneRepository;
use CommerceGuys\Zone\Repository\ZoneRepositoryInterface;

/**
 * Manages tax types based on JSON definitions.
 */
class TaxTypeRepository implements TaxTypeRepositoryInterface
{
    /**
     * The path where the tax type and zone definitions are stored.
     *
     * @var string
     */
    protected $definitionPath;

    /**
     * The zone repository.
     *
     * @var ZoneRepositoryInterface
     */
    protected $zoneRepository;

    /**
     * Tax type index.
     *
     * @var array
     */
    protected $taxTypeIndex = [];

    /**
     * Tax types.
     *
     * @var array
     */
    protected $taxTypes = [];

    /**
     * Creates a TaxRepository instance.
     *
     * @param string $definitionPath The path to the tax type and zone
     *                               definitions. Defaults to 'resources/'.
     */
    public function __construct($definitionPath = null, ZoneRepositoryInterface $zoneRepository = null)
    {
        $definitionPath = $definitionPath ?: __DIR__ . '/../../resources/';
        $this->definitionPath = $definitionPath . 'tax_type/';
        $this->zoneRepository = $zoneRepository ?: new ZoneRepository($definitionPath . 'zone/');
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!isset($this->taxTypes[$id])) {
            $definition = $this->loadDefinition($id);
            $this->taxTypes[$id] = $this->createTaxTypeFromDefinition($definition);
        }

        return $this->taxTypes[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        // Build the list of all available tax types.
        if (empty($this->taxTypeIndex)) {
            if ($handle = opendir($this->definitionPath)) {
                while (false !== ($entry = readdir($handle))) {
                    if (substr($entry, 0, 1) != '.') {
                        $id = strtok($entry, '.');
                        $this->taxTypeIndex[] = $id;
                    }
                }
                closedir($handle);
            }
        }

        // Load each tax type.
        $taxTypes = [];
        foreach ($this->taxTypeIndex as $id) {
            $taxTypes[$id] = $this->get($id);
        }

        return $taxTypes;
    }

    /**
     * Loads the tax type definition for the provided id.
     *
     * @param string $id The zone id.
     *
     * @return array The zone definition.
     */
    protected function loadDefinition($id)
    {
        $filename = $this->definitionPath . $id . '.json';
        $definition = @file_get_contents($filename);
        if (empty($definition)) {
            throw new UnknownTaxTypeException($id);
        }
        $definition = json_decode($definition, true);
        $definition['id'] = $id;

        return $definition;
    }

    /**
     * Creates a tax type object from the provided definition.
     *
     * @param array $definition The tax type definition.
     *
     * @return TaxType
     */
    protected function createTaxTypeFromDefinition(array $definition)
    {
        // Load the referenced zone.
        $definition['zone'] = $this->zoneRepository->get($definition['zone']);
        // Provide defaults.
        if (!isset($definition['compound'])) {
            $definition['compound'] = false;
        }
        if (!isset($definition['display_inclusive'])) {
            $definition['display_inclusive'] = false;
        }
        if (!isset($definition['rounding_mode'])) {
            $definition['rounding_mode'] = PHP_ROUND_HALF_UP;
        }

        $type = new TaxType();
        // Bind the closure to the TaxType object, giving it access to
        // its protected properties. Faster than both setters and reflection.
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
            $this->name = $definition['name'];
            $this->compound = $definition['compound'];
            $this->displayInclusive = $definition['display_inclusive'];
            $this->roundingMode = $definition['rounding_mode'];
            $this->zone = $definition['zone'];
            if (isset($definition['generic_label'])) {
                $this->genericLabel = $definition['generic_label'];
            }
            if (isset($definition['tag'])) {
                $this->tag = $definition['tag'];
            }
        }, $type, '\CommerceGuys\Tax\Model\TaxType');
        $setValues($definition);

        foreach ($definition['rates'] as $rateDefinition) {
            $rate = $this->createTaxRateFromDefinition($rateDefinition);
            $type->addRate($rate);
        }

        return $type;
    }

    /**
     * Creates a tax rate object from the provided definition.
     *
     * @param array $definition The tax rate definition.
     *
     * @return TaxRate
     */
    protected function createTaxRateFromDefinition(array $definition)
    {
        $rate = new TaxRate();
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
            $this->name = $definition['name'];
            if (isset($definition['default'])) {
                $this->default = $definition['default'];
            }
        }, $rate, '\CommerceGuys\Tax\Model\TaxRate');
        $setValues($definition);

        foreach ($definition['amounts'] as $amountDefinition) {
            $amount = $this->createTaxRateAmountFromDefinition($amountDefinition);
            $rate->addAmount($amount);
        }

        return $rate;
    }

    /**
     * Creates a tax rate amount object from the provided definition.
     *
     * @param array $definition The tax rate amount definition.
     *
     * @return TaxRateAmount
     */
    protected function createTaxRateAmountFromDefinition(array $definition)
    {
        $amount = new TaxRateAmount();
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
            $this->amount = $definition['amount'];
            if (isset($definition['start_date'])) {
                $this->startDate = new \DateTime($definition['start_date']);
            }
            if (isset($definition['end_date'])) {
                $this->endDate = new \DateTime($definition['end_date']);
            }
        }, $amount, '\CommerceGuys\Tax\Model\TaxRateAmount');
        $setValues($definition);

        return $amount;
    }
}
