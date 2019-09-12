<?php

namespace PragmaRX\Countries\Update;

class States
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
     * @var Rinvex
     */
    private $rinvex;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Rinvex $rinvex
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Rinvex $rinvex, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;

        $this->rinvex = $rinvex;
    }

    /**
     * Update states.
     */
    public function update()
    {
        $this->helper->progress('--- States');

        $this->helper->eraseDataDir($dataDir = '/states/default');

        $result = $this->helper->loadShapeFile('third-party/natural_earth/ne_10m_admin_1_states_provinces');

        $this->helper->message('Processing states...');

        $normalizerClosure = function ($item) {
            $item = $this->updater->addDataSource($item, 'natural');

            $item = $this->updater->addRecordType($item, 'state');

            return $this->updater->normalizeStateOrCityData($item);
        };

        $getCodeClosure = function ($item) {
            return $this->makeStatePostalCode($item);
        };

        $counter = 0;

        $mergerClosure = function ($states) use (&$counter) {
            if ($counter++ % 100 === 0) {
                $this->helper->message("Processed: $counter");
            }

            return $this->rinvex->mergeCountryStatesWithRinvex($states);
        };

        [, $states] = $this->updater->generateJsonFiles($result, $dataDir, $normalizerClosure, $getCodeClosure, $mergerClosure);

        $this->helper->progress('Generated '.count($states).' states.');
    }

    /**
     * Get the state postal code.
     *
     * @param $item
     * @return mixed
     */
    public function makeStatePostalCode($item)
    {
        $item = coollect($item);

        if ($item->iso_3166_2 !== '') {
            $code = explode('-', $item->iso_3166_2);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        if (! empty(trim($item->postal))) {
            $item->postal;
        }

        if ($item->code_hasc !== '') {
            $code = explode('.', $item->code_hasc);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        return $this->helper->caseForKey($item->iso_3166_2);
    }
}
