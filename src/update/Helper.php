<?php

namespace PragmaRX\Countries\Update;

use Exception;
use IlluminateAgnostic\Str\Support\Str;
use PragmaRX\Countries\Package\Services\Command;
use PragmaRX\Countries\Package\Services\Helper as ServiceHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ShapeFile\ShapeFile;

class Helper
{
    /**
     * @var Helper
     */
    protected $config;

    /**
     * @var ServiceHelper
     */
    protected $serviceHelper;

    /**
     * @var Command
     */
    protected $command;

    /**
     * Rinvex constructor.
     *
     * @param object $config
     */
    public function __construct($config)
    {
        $this->config = $config;

        $this->serviceHelper = new ServiceHelper($config);

        $this->command = new Command();
    }

    /**
     * Forward calls to the service helper.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->serviceHelper, $name], $arguments);
    }

    /**
     * Abort with message.
     *
     * @param $message
     * @throws Exception
     */
    protected function abort($message)
    {
        echo "\n$message\n\nAborted.\n";

        exit;
    }

    /**
     * Add suffix to string.
     *
     * @param $suffix
     * @param $string
     * @return mixed
     */
    protected function addSuffix($suffix, $string)
    {
        if (substr($string, -strlen($suffix)) !== (string) $suffix) {
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * @param $dir
     * @param $files
     */
    protected function deleteAllFiles($dir, $files)
    {
        foreach ($files as $file) {
            (is_dir("$dir/$file"))
                ? $this->delTree("$dir/$file")
                : unlink("$dir/$file");
        }
    }

    /**
     * Delete a whole directory.
     *
     * @param $dir
     */
    protected function deleteDirectory($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');

            $todo($fileInfo->getRealPath());
        }

        rmdir($dir);
    }

    protected function fopenOrFail($url, $string)
    {
        if (($handle = @fopen($url, $string)) === false) {
            $this->exception("Could not open file $url");
        }

        return $handle;
    }

    /**
     * Raise exception.
     *
     * @param $message
     * @throws Exception
     */
    public function exception($message)
    {
        throw new Exception($message);
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
        coollect((array) $url)->each(function ($url) use ($directory) {
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
            try {
                $this->downloadCurl($url, $destination);
            } catch (\Exception $exception) {
                $this->abort("Could not download {$url} to {$destination}");
            }
        }

        chmod($destination, 0644);
    }

    /**
     * @param $url
     * @param $destination
     */
    public function downloadFopen($url, $destination)
    {
        $fr = $this->fopenOrFail($url, 'r');

        $fw = $this->fopenOrFail($destination, 'w');

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
    protected function renameMasterToPackage($file, $subPath, $path, $exclude)
    {
        if (Str::endsWith($file, 'master.zip')) {
            $dir = coollect(scandir($path))->filter(function ($file) use ($exclude) {
                return $file !== '.' && $file !== '..' && $file !== $exclude;
            })->first();

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

        if (! Str::endsWith($file, '.zip') || file_exists($subPath = "$path/$subPath")) {
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

        return coollect($result)->mapWithKeys(function ($fields, $key1) {
            return [
                strtolower($key1) => coollect($fields)->mapWithKeys(function ($value, $key2) {
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
            $result[Str::snake($key)] = arrayable($value) || is_array($value)
                ? $this->arrayKeysSnakeRecursive($value)
                : $value;
        });

        return coollect($result);
    }

    /**
     * Load CSV file.
     *
     * @param $csv
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function csvDecode($csv)
    {
        return coollect(array_map('str_getcsv', $csv));
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
        if (Str::endsWith($file, '.zip')) {
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
        return Str::snake(strtolower(str_replace('-', '_', $admin)));
    }

    /**
     * Download files.
     */
    public function downloadDataFiles()
    {
        $this->config->get('downloadable')->each(function ($urls, $path) {
            if (! file_exists($destination = $this->dataDir("third-party/$path"))) {
                coollect($urls)->each(function ($url) use ($destination) {
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
        $this->progress('--- Download files');

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
            $this->progress('Loaded.');

            return $this->loadJson($sha);
        }

        $this->progress($file);

        $shapeFile = $this->shapeFile($file);

        $this->mkDir(dirname($sha));

        file_put_contents($sha, $shapeFile->toJson());

        $this->progress('Loaded.');

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
        return coollect(glob("$dir/*.json*"))->mapWithKeys(function ($file) {
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
        if (Str::contains($from, '*.')) {
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
     * Show the progress.
     *
     * @param string $string
     */
    public function progress($string = '')
    {
        if (is_null($this->command)) {
            dump($string);

            return;
        }

        $this->command->line($string);
    }

    /**
     * Display a message in console.
     *
     * @param $message
     * @param string $type
     */
    public function message($message, $type = 'line')
    {
        if (! is_null($this->command)) {
            $this->command->{$type}($message);
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
            $this->abort('loadCsv Error: File name not set');
        }

        if (! file_exists($file)) {
            $file = $this->dataDir($this->addSuffix('.csv', "/$dir/".strtolower($file)));
        }

        return coollect($this->csvDecode(file($file)));
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
        if (! Str::endsWith($dir, (DIRECTORY_SEPARATOR))) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        return $this->dataDir($this->addSuffix('.json', $this->toDir($dir).strtolower($key)));
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
        $path = (empty($path) || Str::startsWith($path, DIRECTORY_SEPARATOR)) ? $path : "/{$path}";

        return __COUNTRIES_DIR__.$this->toDir("/src/data$path");
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

    /**
     * Delete uneeded data files.
     */
    public function deleteTemporaryFiles()
    {
        $this->progress('--- Delete temporary files');

        $this->config->get('deletable')->each(function ($directory) {
            if (file_exists($directory = $this->dataDir($directory))) {
                $this->deleteDirectory($directory);
            }
        });
    }
}
