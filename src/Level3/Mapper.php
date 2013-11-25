<?php

namespace Level3;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Mapper
{
    const SLASH_CHARACTER = '/';
    const DEFAULT_INTERFACE = 'Level3\Repository\Getter';

    protected $baseURI = '';
    protected $skipCurieSegments = 0;

    protected $interfacesWithOutParams = [
        'Level3\Repository\Poster' => 'POST',
        'Level3\Repository\Finder' => 'GET'
    ];

    protected $interfacesWithParams = [
        'Level3\Repository\Deleter' => 'DELETE',
        'Level3\Repository\Getter' => 'GET',
        'Level3\Repository\Putter' => 'PUT',
        'Level3\Repository\Patcher' => 'PATCH'
    ];

    public function setBaseURI($uri)
    {
        if ($this->doesNotEndInSlash($uri)) {
            $uri = $this->addSlashToUri($uri);
        }

        $this->baseURI = $uri;
    }

    public function setSkipCurieSegments($skip)
    {
        $this->skipCurieSegments = $skip;
    }

    private function doesNotEndInSlash($uri)
    {
        if (!strlen($uri)) {
            return false;
        }

        return $uri[strlen($uri) - 1] != self::SLASH_CHARACTER;
    }

    private function addSlashToUri($uri)
    {
        return $uri . self::SLASH_CHARACTER;
    }

    public function getBaseURI()
    {
        return $this->baseURI;
    }

    private function transformCurieURI($curieURI, ParameterBag $attributes = null)
    {
        if (!$attributes) {
            return $curieURI;
        }

        foreach ($attributes->all() as $key => $value) {
            $curieURI = str_replace(sprintf('{%s}', $key), $value, $curieURI);
        }

        return $curieURI;
    }

    public function boot(Hub $hub)
    {
        foreach ($hub->getKeys() as $resourceKey) {
            $this->mapRepositoryToRoutes($hub, $resourceKey);
        }
    }

    private function mapRepositoryToRoutes(Hub $hub, $repositoryKey)
    {
        $repository = $hub->get($repositoryKey);

        $interfaces = array_merge(
            $this->interfacesWithOutParams,
            $this->interfacesWithParams
        );

        foreach ($interfaces as $interface => $method) {
            $this->mapMethodIfNeeded($repository, $interface);
        }

        $this->mapOptionsMethod($repository);
    }

    private function mapMethodIfNeeded(Repository $repository, $interface)
    {
        if ($repository instanceof $interface) {
            $this->callToMapMethod($repository, $interface);
        }
    }

    private function mapOptionsMethod(Repository $repository)
    {
        $repositoryKey = $repository->getKey();

        $curieURIWithOutParams = $this->generateCurieURI($repositoryKey);
        $this->mapOptions($repositoryKey, $curieURIWithOutParams);

        $curieURIWithParams = $this->generateCurieURI($repositoryKey, true);
        $this->mapOptions($repositoryKey, $curieURIWithParams);
    }

    private function callToMapMethod(Repository $repository, $interface)
    {
        $namespace = explode('\\', $interface);
        $name = ucfirst(strtolower(end($namespace)));
        $method = sprintf('map%s', $name);

        $repositoryKey = $repository->getKey();
        $curieURI = $this->getCurieURI($repositoryKey, $interface);

        $this->$method($repositoryKey, $curieURI);
    }

    public function getURI(
        $repositoryKey,
        $interface = self::DEFAULT_INTERFACE,
        ParameterBag $attributes = null
    )
    {
        $curieURI = $this->getCurieURI($repositoryKey, $interface);

        return $this->transformCurieURI($curieURI, $attributes);
    }

    public function getCurieURI($repositoryKey, $interface = null)
    {
        if (!$interface) {
            $interface = self::DEFAULT_INTERFACE;
        }

        foreach ($this->interfacesWithOutParams as $interfaceName => $method) {
            if ($interface == $interfaceName) {
                return $this->generateCurieURI($repositoryKey);
            }
        }

        foreach ($this->interfacesWithParams as $interfaceName => $method) {
            if ($interface == $interfaceName) {
                return $this->generateCurieURI($repositoryKey, true);
            }
        }
    }

    protected function generateCurieURI($repositoryKey, $specific = false)
    {
        $uri = $this->baseURI;

        $names = explode(self::SLASH_CHARACTER, $repositoryKey);
        $max = count($names);
        for ($i=0; $i<$max; $i++) {
            $uri .= self::SLASH_CHARACTER . $names[$i];
            if (($specific || $max > $i+1) && $i >= $this->skipCurieSegments) {
                $uri .= self::SLASH_CHARACTER . $this->createCurieParamFromName($names[$i]);
            }
        }

        return $uri;
    }

    protected function createCurieParamFromName($name)
    {
        return sprintf('{%sId}', $name);
    }

    public function getMethods(Repository $repository)
    {
        $interfaces = array_merge(
            $this->interfacesWithOutParams,
            $this->interfacesWithParams
        );

        $methods = [];
        foreach ($interfaces as $interface => $method) {
            if ($repository instanceof $interface) {
                $methods[] = $method;
            }
        }

        $methods = array_unique($methods);
        sort($methods);

        return $methods;
    }

    abstract public function mapFinder($repositoryKey, $uri);
    abstract public function mapGetter($repositoryKey, $uri);
    abstract public function mapPoster($repositoryKey, $uri);
    abstract public function mapPutter($repositoryKey, $uri);
    abstract public function mapPatcher($repositoryKey, $uri);
    abstract public function mapDeleter($repositoryKey, $uri);
    abstract public function mapOptions($repositoryKey, $uri);
}
