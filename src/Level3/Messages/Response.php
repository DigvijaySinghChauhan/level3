<?php

namespace Level3\Messages;

use Level3\Resource\Resource;
use Level3\Resource\Formatter;
use Level3\Exceptions\HTTPException;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Teapot\StatusCode;
use Exception;
use DateTime;
use DateInterval;

class Response extends SymfonyResponse
{
    protected $resource;
    protected $formatter;

    public static function createFromResource(Request $request, Resource $resource)
    {
        $response = new static();
        $response->setStatusCode(StatusCode::OK);
        $response->setResource($resource);
        $response->setFormatter(new Formatter\HAL\JsonFormatter());

        if ($cache = $resource->getCache()) {
            $date = new DateTime();
            $date->add(new DateInterval(sprintf('PT%dS', $cache)));

            $response->setExpires($date);
            $response->setTTL($cache);
        }

        if ($id = $resource->getId()) {
            $response->setEtag($id);
        }

        if ($date = $resource->getLastUpdate()) {
            $response->setLastModified($date);
        }

        return $response;
    }

    public static function createFromException(Request $request, Exception $exception)
    {
        $code = StatusCode::INTERNAL_SERVER_ERROR;
        if ($exception instanceof HTTPException) {
            $code = $exception->getCode();
        }

        $exceptionClass = explode('\\', get_class($exception));
        $resource = new Resource();
        $resource->setData([
            'type' => end($exceptionClass),
            'message' => $exception->getMessage()
        ]);

        $response = static::createFromResource($request, $resource);
        $response->setStatusCode($code);

        return $response;
    }

    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
        $this->setContentType($formatter->getContentType());
    }

    public function getFormatter()
    {
        return $this->formatter;
    }

    public function addHeader($header, $value)
    {
        $this->headers->set($header, $value, false);
    }

    public function setHeader($header, $value)
    {
        $this->headers->set($header, $value, true);
    }

    public function getHeaders($header)
    {
        return $this->headers->get($header, null, false);
    }

    public function getHeader($header)
    {
        return $this->headers->get($header);
    }

    public function getContent()
    {
        if (!$this->formatter instanceof Formatter || !$this->resource) {
            return '';
        }

        return $this->formatter->toResponse($this->resource);
    }

    public function sendContent()
    {
        echo $this->getContent();

        return $this;
    }

    public function setContentType($contentType)
    {
        $this->setHeader('Content-Type', $contentType);
    }
}
