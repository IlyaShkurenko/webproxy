<?php

namespace Proxy\FeedBox;

class FileFeedBox extends AbstractFeedBoxPartials
{
    const DIR = __DIR__ . '/../../../var/';
    const EXT = '.txt';

    protected $fileHandlers = [];
    protected $filePathCache = [];

    protected function doPush($key, $data)
    {
        $path = self::DIR . $this->prepareKey($key) . self::EXT;

        file_put_contents($path, $data);
    }

    protected function doPull($key)
    {
        $path = self::DIR . $this->prepareKey($key) . self::EXT;

        return !is_file($path) ?: file_get_contents($path);
    }

    public function supportsPartial()
    {
        return true;
    }

    public function endPartialQueue($key)
    {
        $path = $this->getFilePath($key, '.part');

        if (parent::endPartialQueue($key)) {
            // Move generated file
            $pathTmp = $path . '.tmp';

            if (!empty($this->fileHandlers[$key])) {
                fclose($this->fileHandlers[$key]);
                unset($this->fileHandlers[$key]);
            }

            if (is_file($path) and is_file($pathTmp)) {
                unlink($path);
                rename($pathTmp, $path);
            } elseif (is_file($pathTmp)) {
                rename($pathTmp, $path);
            }
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    protected function doPushPartial($key, $data, $init = false)
    {
        if (empty($this->fileHandlers[$key])) {
            $path = $path = $this->getFilePath($key, '.part') . '.tmp';
            $this->fileHandlers[$key] = fopen($path, 'w');
        }

        fwrite($this->fileHandlers[$key], is_array($data) ? (join(PHP_EOL, $data) . PHP_EOL) : ($data . PHP_EOL));
    }

    protected function doPullPartial($key)
    {
        if (empty($this->fileHandlers[$key])) {
            $path = $this->getFilePath($key, '.part');

            if (!is_file($path)) {
                return false;
            }

            $this->fileHandlers[$key] = fopen($path, 'r');
        }

        $handle = $this->fileHandlers[$key];
        $line = fgets($handle);

        // End of the file
        if (false === $line or !trim($line)) {
            fclose($handle);
            unset($this->fileHandlers[$key]);

            return false;
        }

        return $line;
    }

    protected function getFilePath($key, $suffix = '')
    {
        if (empty($this->filePathCache[$key . $suffix])) {
            $this->filePathCache[$key . $suffix] = self::DIR . $this->prepareKey($key) . $suffix . self::EXT;
        }
        return $this->filePathCache[$key . $suffix];
    }

    protected function prepareKey($key)
    {
        foreach ([
            '[^a-zA-Z0-9]+' => '_',
            '[_]{2,}'       => '_',
            '_$'            => ''
        ] as $regex => $replace)
        {
            $key = preg_replace("~$regex~", $replace, $key);
        }

        return $key;
    }
}
