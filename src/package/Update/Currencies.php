<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\General;

class Currencies extends Base
{
    /**
     * @var General
     */
    protected $general;

    /**
     * @var Updater
     */
    protected $updater;

    /**
     * Rinvex constructor.
     *
     * @param General $general
     * @param Updater $updater
     */
    public function __construct(General $general, Updater $updater)
    {
        $this->general = $general;

        $this->updater = $updater;
    }

    /**
     *
     */
    public function update()
    {
        $this->general->progress('Updating currencies...');

        $this->general->eraseDataDir($dataDir = '/currencies/default');

        $currencies = $this->general->loadJsonFiles($this->general->dataDir('third-party/world-currencies/package/src'));

        $currencies = $currencies->mapWithKeys(function ($currency) {
            return $currency;
        });

        $this->general->message('Processing currencies...');

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

        $this->general->progress('Generated '.count($currencies).' currencies.');
    }
}
