<?php

declare(strict_types = 1);

namespace Onyx\Providers;

use Pimple\ServiceProviderInterface;
use Puzzle\Configuration;
use Pimple\Container;
use Silex\Provider\TwigServiceProvider;
use Onyx\ViewManager;
use Onyx\Plugins\ViewExtensionInContainer;

class Twig implements ServiceProviderInterface, ViewManager
{
    private
        $extensions,
        $paths;

    public function register(Container $container): void
    {
        $this->extensions = [];
        $this->paths = [];

        $this->validatePuzzleConfiguration($container);
        $this->initializeTwigProvider($container);
    }

    public function addPath($paths, bool $prioritary = true): void
    {
        if(! is_array($paths))
        {
            $paths = array($paths);
        }

        $arrayAddFunction = 'array_push';
        if($prioritary === true)
        {
            $arrayAddFunction = 'array_unshift';
            $paths = array_reverse($paths);
        }

        foreach($paths as $path)
        {
            $arrayAddFunction($this->paths, $path);
        }
    }

    public function addExtension($extension): void
    {
        $this->extensions[] = $extension;
    }

    public function getExtensions(): iterable
    {
        return $this->extensions;
    }

    public function retrieveExtensionInstance($extension, Container $container)
    {
        if($extension instanceof \Twig_Extension)
        {
            return $extension;
        }

        if($extension instanceof ViewExtensionInContainer)
        {
            $key = $extension->key();
            if($container->offsetExists($key))
            {
                return $container[$key];
            }
        }

        return null;
    }

    private function initializeTwigProvider(Container $container): void
    {
        $container->register(new TwigServiceProvider());

        $cacheDirectory = $container['configuration']->read('twig/cache/directory', false);
        if($cacheDirectory !== false)
        {
            $cacheDirectory = $container['var.path'] . $cacheDirectory;
        }
        $container['twig.cache.path'] = $cacheDirectory;

        $container['twig.options'] = array(
            'cache' => $container['twig.cache.path'],
            'auto_reload' => $container['configuration']->read('twig/developmentMode', false),
        );

        $container['view.manager'] = function ($c) {
            return $this;
        };

        $container['twig.path'] = function($c) {
            return $this->paths;
        };

    }

    private function validatePuzzleConfiguration(Container $container): void
    {
        if(! isset($container['configuration']) || ! $container['configuration'] instanceof Configuration)
        {
            throw new \LogicException(__CLASS__ . ' requires an instance of Puzzle\Configuration (as key "configuration").');
        }
    }
}
