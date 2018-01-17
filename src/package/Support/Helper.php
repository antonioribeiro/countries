<?php

namespace PragmaRX\Countries\Package\Support;

use Exception;
use ReflectionClass;
use ShapeFile\ShapeFile;
use Illuminate\Support\Facades\File;
use PragmaRX\Countries\Package\Service;
use PragmaRX\Countries\Package\Update\Config;
use PragmaRX\Countries\Package\Update\Updater;

class Helper
{
    /**
     * @var Helper
     */
    protected $config;
    /**
     * @var Updater
     */
    private $updater;

    /**
     * Rinvex constructor.
     *
     * @param Config $config
     * @param Updater $updater
     */
    public function __construct(Config $config, Updater $updater)
    {
        $this->config = $config;

        $this->updater = $updater;
    }

    /**
     * @param $dir
     * @param $files
     */
    protected function deleteAllFiles($dir, $files): void
    {
        foreach ($files as $file) {
            (is_dir("$dir/$file"))
                ? $this->delTree("$dir/$file")
                : unlink("$dir/$file");
        }
    }

    /**
     * Make a directory.
     *
     * @param $dir
     */
    public function mkDir($dir)
    {
        if (file_exists($dir)) {
            return;
        }

        mkdir($dir, 0755, true);
    }

    /**
     * Download one or more files.
     *
     * @param $url
     * @param $directory
     */
    public function download($url, $directory)
    {
        countriesCollect((array) $url)->each(function ($url) use ($directory) {
            $filename = basename($url);

            $destination = $this->toDir("{$directory}/{$filename}");

            $this->message("Downloading to {$destination}");

            $this->mkDir($directory);

            $this->downloadFile($url, $destination);
        });
    }

    /**
     * @param $class
     * @return string
     */
    public function getClassDir($class)
    {
        $reflector = new ReflectionClass($class);

        return dirname($reflector->getFileName());
    }

    /**
     * @param $url
     * @param $destination
     */
    public function downloadFile($url, $destination)
    {
        if (file_exists($destination)) {
            return;
        }

        try {
            $this->downloadFopen($url, $destination);
        } catch (\Exception $exception) {
            $this->downloadCurl($url, $destination);
        }

        chmod($destination, 0644);
    }

    /**
     * @param $url
     * @param $destination
     */
    public function downloadFopen($url, $destination)
    {
        $fr = fopen($url, 'r');

        $fw = fopen($destination, 'w');

        while (! feof($fr)) {
            fwrite($fw, fread($fr, 4096));
            flush();
        }

        fclose($fr);

        fclose($fw);
    }

    /**
     * @param $url
     * @param $destination
     */
    public function downloadCurl($url, $destination)
    {
        $nextStep = 8192;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $total, $downloaded) use (&$nextStep) {
            if ($downloaded > $nextStep) {
                echo '.';
                $nextStep += 8192;
            }
        });
        curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GuzzleHttp/6.2.1 curl/7.54.0 PHP/7.2.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        file_put_contents($destination, curl_exec($ch));
        curl_close($ch);

        echo "\n";
    }

    /**
     * @param $file
     * @param $subPath
     * @param $path
     * @param $exclude
     */
    protected function renameMasterToPackage($file, $subPath, $path, $exclude): void
    {
        if (ends_with($file, 'master.zip')) {
            $dir = countriesCollect(scandir($path))->filter(function ($file) use ($exclude) {
                return $file !== '.' && $file !== '..' && $file !== $exclude;
            })->first()
            ;

            rename("$path/$dir", $subPath);
        }
    }

    /**
     * @param $file
     * @param $subPath
     */
    public function unzipFile($file, $subPath)
    {
        $path = dirname($file);

        if (! ends_with($file, '.zip') || file_exists($subPath = "$path/$subPath")) {
            return;
        }

        chdir($path);

        exec("unzip -o $file");

        $this->renameMasterToPackage($file, $subPath, $path, basename($file));
    }

    /**
     * Delete a directory and all its files.
     *
     * @param $dir
     * @return bool
     */
    public function delTree($dir)
    {
        if (! file_exists($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        $this->deleteAllFiles($dir, $files);

        return rmdir($dir);
    }

    /**
     * Load a shapeFile.
     *
     * @param $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function shapeFile($dir)
    {
        $shapeRecords = new ShapeFile($dir);

        $result = [];

        foreach ($shapeRecords as $record) {
            if ($record['dbf']['_deleted']) {
                continue;
            }

            $data = $record['dbf'];

            unset($data['_deleted']);

            $result[] = $data;
        }

        unset($shapeRecords);

        return countriesCollect($result)->mapWithKeys(function ($fields, $key1) {
            return [
                strtolower($key1) => countriesCollect($fields)->mapWithKeys(function ($value, $key2) {
                    return [strtolower($key2) => $value];
                }),
            ];
        });
    }

    /**
     * Recursively change all array keys case.
     *
     * @param array|\PragmaRX\Coollection\Package\Coollection $array
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function arrayKeysSnakeRecursive($array)
    {
        $result = [];

        $array = arrayable($array) ? $array->toArray() : $array;

        array_walk($array, function ($value, $key) use (&$result) {
            $result[snake_case($key)] = arrayable($value) || is_array($value)
                ? $this->arrayKeysSnakeRecursive($value)
                : $value;
        });

        return countriesCollect($result);
    }

    /**
     * Load CSV file.
     *
     * @param $csv
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function csvDecode($csv)
    {
        return countriesCollect(array_map('str_getcsv', $csv));
    }

    /**
     * Fix a bad UTF8 string.
     *
     * @param $string
     * @return string
     */
    public function fixUtf8($string)
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);
    }

    /**
     * Unzip a file.
     *
     * @param $file
     * @param $path
     */
    public function unzip($file, $path)
    {
        if (ends_with($file, '.zip')) {
            $this->message("Unzipping to {$file}");

            $this->unzipFile($file, $path);
        }
    }

    /**
     * Return string to be used in keys.
     *
     * @param $admin
     * @return string
     */
    public function caseForKey($admin)
    {
        return snake_case(strtolower(str_replace('-', '_', $admin)));
    }

    /**
     * Download files.
     */
    public function downloadDataFiles()
    {
        $this->config->get('downloadable')->each(function ($urls, $path) {
            if (! file_exists($destination = $this->dataDir("third-party/$path"))) {
                countriesCollect($urls)->each(function ($url) use ($path, $destination) {
                    $this->download($url, $destination);

                    $file = basename($url);

                    $this->unzip("$destination/$file", 'package');
                });
            }
        });
    }

    /**
     * Download, move and delete data files.
     */
    public function downloadFiles()
    {
        $this->downloadDataFiles();

        $this->moveDataFiles();
    }

    /**
     * Erase all files from states data dir.
     *
     * @param string $dir
     */
    public function eraseDataDir($dir)
    {
        $this->delTree($this->dataDir($dir));
    }

    /**
     * Load the shape file (DBF) to array.
     *
     * @param string $file
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function loadShapeFile($file)
    {
        $this->progress('Loading shape file...');

        if (file_exists($sha = $this->dataDir('tmp/'.sha1($file = $this->dataDir($file))))) {
            return $this->loadJson($sha);
        }

        $shapeFile = $this->shapeFile($file);

        $this->mkDir(dirname($sha));

        file_put_contents($sha, $shapeFile->toJson());

        return $shapeFile;
    }

    /**
     * Load json files from dir.
     *
     * @param $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function loadJsonFiles($dir)
    {
        return countriesCollect(glob("$dir/*.json*"))->mapWithKeys(function ($file) {
            $key = str_replace('.json', '', str_replace('.json5', '', basename($file)));

            return [$key => $this->loadJson($file)];
        });
    }

    /**
     * Move a data file or many using wildcards.
     *
     * @param $from
     * @param $to
     */
    public function moveDataFile($from, $to)
    {
        if (str_contains($from, '*.')) {
            $this->moveFilesWildcard($from, $to);

            return;
        }

        if (file_exists($from = $this->dataDir($from))) {
            $this->mkDir(dirname($to = $this->dataDir($to)));

            rename($from, $to);
        }
    }

    /**
     * Move data files to the proper location.
     */
    public function moveDataFiles()
    {
        $this->config->get('moveable')->each(function ($to, $from) {
            $this->moveDataFile($from, $to);
        });
    }

    /**
     * Delete uneeded data files.
     */
    public function deleteTemporaryFiles()
    {
        $this->config->get('deletable')->each(function ($directory) {
            if (file_exists($directory = $this->dataDir($directory))) {
                File::deleteDirectory($directory);
            }
        });
    }

    /**
     * Move files using wildcard filter.
     *
     * @param $from
     * @param $to
     */
    public function moveFilesWildcard($from, $to)
    {
        countriesCollect(glob($this->dataDir($from)))->each(function ($from) use ($to) {
            $this->mkDir($dir = $this->dataDir($to));

            rename($from, $dir.'/'.basename($from));
        });
    }

    /**
     * Show the progress.
     *
     * @param string $string
     */
    public function progress($string = '')
    {
        if (is_null($command = $this->updater->getCommand())) {
            dump($string);

            return;
        }

        $command->line($string);
    }

    /**
     * Display a message in console.
     *
     * @param $message
     * @param string $type
     */
    public function message($message, $type = 'line')
    {
        if (! is_null($command = $this->updater->getCommand())) {
            $command->{$type}($message);
        }
    }

    /**
     * Get temp directory.
     *
     * @param string $path
     * @return string
     */
    public function tmpDir($path)
    {
        return __COUNTRIES_DIR__.$this->toDir("/tmp/{$path}");
    }

    /**
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     * @throws \Exception
     */
    public function loadCsv($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadCsv Error: File name not set');
        }

        if (! file_exists($file)) {
            $file = $this->dataDir("/$dir/".strtolower($file).'.csv');
        }

        return countriesCollect($this->csvDecode(file($file)));
    }

    /**
     * Make state json filename.
     *
     * @param $key
     * @param string $dir
     * @return string
     */
    public function makeJsonFileName($key, $dir = '')
    {
        if (! ends_with($dir, (DIRECTORY_SEPARATOR))) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        return $this->dataDir($this->toDir($dir).strtolower($key).'.json');
    }

    /**
     * Put contents into a file.
     *
     * @param $file
     * @param $contents
     */
    public function putFile($file, $contents)
    {
        $this->mkdir(dirname($file));

        file_put_contents($file, $contents);
    }

    /**
     * Encode and pretty print json.
     *
     * @param array|\PragmaRX\Coollection\Package\Coollection $data
     * @return string
     */
    public function jsonEncode($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Get package home dir.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return $this->getClassDir(Service::class);
    }

    /**
     * Get data directory.
     *
     * @param $path
     * @return string
     */
    public function dataDir($path = '')
    {
        $path = (empty($path) || starts_with($path, DIRECTORY_SEPARATOR)) ? $path : "/{$path}";

        return __COUNTRIES_DIR__.$this->toDir("/src/data$path");
    }

    /**
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     * @throws Exception
     */
    public function loadJson($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadJson Error: File name not set');
        }

        if (! file_exists($file) && ! file_exists($file = $this->dataDir("/$dir/".strtolower($file).'.json'))) {
            return countriesCollect();
        }

        $decoded = json5_decode($this->loadFile($file), true);

        if (is_null($decoded)) {
            throw new Exception("Error decoding json file: $file");
        }

        return countriesCollect($decoded);
    }

    /**
     * Load a file from disk.
     *
     * @param $file
     * @return null|string
     */
    public function loadFile($file)
    {
        if (file_exists($file)) {
            return $this->sanitizeFile(file_get_contents($file));
        }
    }

    /**
     * @param $contents
     * @return string
     */
    public function sanitizeFile($contents)
    {
        return str_replace('\n', '', $contents);
    }

    /**
     * Check if array is multidimensional.
     *
     * @param $string
     * @return string
     */
    public function toDir($string)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $string);
    }
}
