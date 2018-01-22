<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Countries\Package\Support\Base;

class Taxes extends Base
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

    public function update()
    {
        $this->helper->progress('--- Taxes');

        $this->helper->eraseDataDir($dataDir = '/taxes/default');

        $taxes = $this->helper->loadJsonFiles($this->helper->dataDir('third-party/commerceguys/taxes/types'));

        $taxes = $taxes->mapWithKeys(function ($vat, $key) {
            $parts = coollect(explode('_', $key));
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

        $this->helper->message('Processing taxes...');

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

        $this->helper->progress('Generated '.count($taxes).' taxes.');
    }

    public function normalizeTax($tax)
    {
        return $tax;
    }
}
