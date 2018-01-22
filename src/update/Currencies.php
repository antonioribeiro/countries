<?php

namespace PragmaRX\Countries\Update;

use Exception;
use PragmaRX\Countries\Package\Support\Base;

class Currencies extends Base
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Updater
     */
    protected $updater;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;
    }

    /**
     * Update all currencies.
     *
     * @throws Exception
     */
    public function update()
    {
        $this->helper->progress('--- Currencies');

        $this->helper->eraseDataDir($dataDir = '/currencies/default');

        $currencies = $this->helper->loadJsonFiles($directory = $this->helper->dataDir('third-party/world-currencies/package/src'));

        if ($currencies->isEmpty()) {
            throw new Exception("No currencies found in {$directory}");
        }

        $currencies = $currencies->mapWithKeys(function ($currency) {
            return $currency;
        });

        $this->helper->message('Processing currencies...');

        $normalizerClosure = function ($item) {
            $item = $this->updater->addDataSource($item, 'world-currencies');

            $item = $this->updater->addRecordType($item, 'currency');

            return [$item];
        };

        $getCodeClosure = function () {
        };

        $generateTaxData = function ($tax) {
            return $tax;
        };

        $currencies = $this->updater->generateJsonFiles($currencies, $dataDir, $normalizerClosure, $getCodeClosure, $generateTaxData, null);

        $this->helper->progress('Generated '.count($currencies).' currencies.');
    }
}
