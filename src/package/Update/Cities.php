<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\General;

class Cities extends Base
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
     * Update cities.
     */
    public function update()
    {
        $this->general->progress('Updating cities...');

        $this->general->eraseDataDir($dataDir = '/cities/default/');

        $result = $this->general->loadShapeFile('third-party/natural_earth/ne_10m_populated_places');

        $this->general->message('Processing cities...');

        $normalizerClosure = function ($item) {
            $item = $this->updater->addDataSource($item, 'natural');

            $item = $this->updater->addRecordType($item, 'city');

            return $this->updater->normalizeStateOrCityData($item);
        };

        $codeGeneratorClosure = function ($item) {
            return $this->general->caseForKey($item['nameascii']);
        };

        $mergerClosure = function ($item) {
            return $item;
        };

        list(, $cities) = $this->updater->generateJsonFiles($result, $dataDir, $normalizerClosure, $codeGeneratorClosure, $mergerClosure);

        $this->general->progress('Generated '.count($cities).' cities.');
    }
}
