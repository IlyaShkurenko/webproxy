<?php

namespace Application;

use Silex\Application;

class TwigExtension extends \Twig_Extension
{

    /**
     * @var Application
     */
    private $app;

    function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getFunctions()
    {
        $assetHandler = function ($file, $prefix = 'assets') {
            $root = __DIR__ . "/../../web/$prefix/";

            $path = $root . ltrim($file, '/');

            // File not found, just return it as it passed
            if (!is_file($path)) {
                return $file;
            }

            $date = filemtime($path);

            // Unknown error, don't handle it
            if (!$date) {
                return $file;
            }

            return "$prefix/$file" . '?v=' . md5($date);
        };

        return [
            new \Twig_SimpleFunction('asset', $assetHandler),
            new \Twig_SimpleFunction('asset_relative', function ($file) use ($assetHandler) {
                return $assetHandler($file, isset($this->app['config.view.asset_relative_path']) ?
                    "assets/{$this->app['config.view.asset_relative_path']}" :
                    'assets'
                );
            }),
        ];
    }
}
