<?php

function getPackageHomeDir($class)
{
    $reflector  = new ReflectionClass($class);

    $dir = dirname($reflector->getFileName());

    while (! file_exists($dir.DIRECTORY_SEPARATOR.'composer.json')) {
        $dir .= DIRECTORY_SEPARATOR.'..';
    }

    return $dir;
}
