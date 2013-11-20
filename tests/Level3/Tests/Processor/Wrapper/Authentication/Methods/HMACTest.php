<?php

namespace Level3\Tests\Processor\Wrapper\Authenticator;

use Level3\Processor\Wrapper\Authenticator\Methods\HMAC;

use Level3\Tests\TestCase;
use Mockery as m;

class HMACTest extends TestCase
{
    const VALID_TOKEN_MD5 = 'foo:f197feb7989b7e3284550e9818dfc87d';
    const VALID_TOKEN_SHA256 = 'foo:2ca3a2e99ee58316b7dc7abdb4a914f28701f81dcf94b374472df36f8765c1fa';
    const INVALID_TOKEN = 'foo:qux';
    const MALFORMED_TOKEN = 'foux';
    CONST INVALID_ALGORITHM = 'foo';

    public function testAuthenticateRequest()
    {
        $method = new HMACMock();

        $request = $this->createRequestMockSimple();
        $request->headers->shouldReceive('get')
            ->with(HMAC::AUTHORIZATION_HEADER)
            ->twice()->andReturn('Authorization: HMAC '. self::VALID_TOKEN_SHA256);

        $request->shouldReceive('getContent')
            ->withNoArgs()
            ->once()->andReturn('qux');

        $request->attributes->shouldReceive('set')
            ->once()
            ->with('_credentials', m::on(function($credentials) {
                return $credentials->isAuthenticated();
            }));

        $method->authenticateRequest($request, 'get');
    }

    public function testSetHashAlgorithm()
    {
        $method = new HMACMock();
        $method->setHashAlgorithm('md5');

        $request = $this->createRequestMockSimple();
        $request->headers->shouldReceive('get')
            ->with(HMAC::AUTHORIZATION_HEADER)
            ->twice()->andReturn('Authorization: HMAC '. self::VALID_TOKEN_MD5);

        $request->shouldReceive('getContent')
            ->withNoArgs()
            ->once()->andReturn('qux');

        $request->attributes->shouldReceive('set')
            ->once()
            ->with('_credentials', m::on(function($credentials) {
                return $credentials->isAuthenticated();
            }));

        $method->authenticateRequest($request, 'get');
    }

    /**
     * @expectedException Exception
     */
    public function testSetHashAlgorithmInvalid()
    {
        $method = new HMACMock();
        $method->setHashAlgorithm(self::INVALID_ALGORITHM);
    }

    /**
     * @expectedException Level3\Processor\Wrapper\Authenticator\Exceptions\MalformedCredentials
     */
    public function testAuthenticateRequestMalformed()
    {
        $method = new HMACMock();

        $request = $this->createRequestMockSimple();
        $request->headers->shouldReceive('get')
            ->with(HMAC::AUTHORIZATION_HEADER)
            ->twice()->andReturn('Authorization: HMAC '. self::MALFORMED_TOKEN);

        $method->authenticateRequest($request, 'get');
    }

    /**
     * @expectedException Level3\Processor\Wrapper\Authenticator\Exceptions\Unauthorized
     */
    public function testAuthenticateRequestInvalid()
    {
        $method = new HMACMock();

        $request = $this->createRequestMockSimple();
        $request->headers->shouldReceive('get')
            ->with(HMAC::AUTHORIZATION_HEADER)
            ->twice()->andReturn('Authorization: HMAC '. self::INVALID_TOKEN);

        $request->shouldReceive('getContent')
            ->withNoArgs()
            ->once()->andReturn('qux');

        $method->authenticateRequest($request, 'get');
    }
}

class HMACMock extends HMAC
{
    protected function getPrivateKey($apiKey)
    {
        if ($apiKey == 'foo') return 'bar';
    }
}
