<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\General;

class Taxes extends Base
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
        $this->general->progress('Updating taxes...');

        $this->general->eraseDataDir($dataDir = '/taxes/default');

        $taxes = $this->general->loadJsonFiles($this->general->dataDir('third-party/commerceguys/taxes/types'));

        $taxes = $taxes->mapWithKeys(function ($vat, $key) {
            $parts = countriesCollect(explode('_', $key));
            $cca2 = $parts->first();
            $type = $parts->last();
            $modifier = $parts->count() > 2 ? $parts[1] : '';

            $country = $this->updater->getCountries()->where('cca2', strtoupper($cca2))->first();

            $vat['vat_id'] = $key;

            $vat['cca2'] = $country->cca2;

            $vat['cca3'] = $country->cca3;

            $vat['tax_type'] = $type;

            $vat['tax_modifier'] = $modifier;

            $vat = $this->updater->addDataSource($vat, 'commerceguys');

            $vat = $this->updater->addRecordType($vat, 'tax');

            $vat = [
                $type.(empty($modifier) ? '' : '_').$modifier => $vat,
            ];

            return [$country->cca3 => $vat];
        });

        $this->general->message('Processing taxes...');

        $normalizerClosure = function ($item) {
            return $item;
        };

        $getCodeClosure = function ($item) {
            return $item['tax_type'];
        };

        $generateTaxData = function ($tax) {
            return $this->normalizeTax($tax);
        };

        $taxes = $this->updater->generateJsonFiles($taxes, $dataDir, $normalizerClosure, $getCodeClosure, $generateTaxData, null);

        $this->general->progress('Generated '.count($taxes).' taxes.');
    }

    public function normalizeTax($tax)
    {
        return $tax;
    }
}
