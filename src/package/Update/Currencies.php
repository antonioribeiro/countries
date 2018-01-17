<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\Helper;

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
     *
     */
    public function update()
    {
        $this->helper->progress('Updating currencies...');

        $this->helper->eraseDataDir($dataDir = '/currencies/default');

        $currencies = $this->helper->loadJsonFiles($this->helper->dataDir('third-party/world-currencies/package/src'));

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
