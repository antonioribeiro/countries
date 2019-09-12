<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Countries\Package\Support\Base;

class Cities extends Base
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
     * Update cities.
     */
    public function update()
    {
        $this->helper->progress('--- Cities');

        $this->helper->eraseDataDir($dataDir = '/cities/default/');

        $result = $this->helper->loadShapeFile('third-party/natural_earth/ne_10m_populated_places');

        $this->helper->message('Processing cities...');

        $normalizerClosure = function ($item) {
            $item = $this->updater->addDataSource($item, 'natural');

            $item = $this->updater->addRecordType($item, 'city');

            return $this->updater->normalizeStateOrCityData($item);
        };

        $codeGeneratorClosure = function ($item) {
            return $this->helper->caseForKey($item['nameascii']);
        };

        $mergerClosure = function ($item) {
            return $item;
        };

        [, $cities] = $this->updater->generateJsonFiles($result, $dataDir, $normalizerClosure, $codeGeneratorClosure, $mergerClosure);

        $this->helper->progress('Generated '.count($cities).' cities.');
    }
}
