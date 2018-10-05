<?php

namespace PragmaRX\Countries\Package\Support;

class Base
{
    public function defineConstants()
    {
        if (! \defined('__COUNTRIES_DIR__')) {
            \define(
                '__COUNTRIES_DIR__',
                realpath(
                    __DIR__.$this->helper->toDir('/../../../')
                )
            );
        }
    }
}
