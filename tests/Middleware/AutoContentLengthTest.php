<?php

/**
 * MIT License
 *
 * Copyright (c) 2018 Samuel CHEMLA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpBg\Rtsp\Tests\Middleware;

use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Message\Response;
use PhpBg\Rtsp\Middleware\AutoContentLength;
use PhpBg\Rtsp\Tests\ConnectionStub;
use PhpBg\Rtsp\Tests\Mock\EmptyResponseMiddleware;
use PhpBg\Rtsp\Tests\Mock\ParametrizedResponseMiddleware;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

class AutoContentLengthTest extends TestCase
{
    public function testAddHeader()
    {
        $middleware = new AutoContentLength();

        $request = new Request();

        $body = 'foo';

        /** @var PromiseInterface $response */
        $response = $middleware($request, new ConnectionStub(), new ParametrizedResponseMiddleware(200, [], $body));

        $extractedResponse = null;
        $response->then(function (Response $response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertNotNull($extractedResponse);
        /**
         * @var Response $extractedResponse
         */
        $this->assertSame(strlen($body), $extractedResponse->getHeader('content-length'));
    }

    public function testTouchHeaderIfAlreadyPresent()
    {
        $middleware = new AutoContentLength();

        $request = new Request();

        $body = 'foo';

        /** @var PromiseInterface $response */
        $response = $middleware($request, new ConnectionStub(), new ParametrizedResponseMiddleware(200, ['content-length' => strlen($body) + 1], $body));

        $extractedResponse = null;
        $response->then(function (Response $response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertNotNull($extractedResponse);
        /**
         * @var Response $extractedResponse
         */
        $this->assertSame(strlen($body) + 1, $extractedResponse->getHeader('content-length'));
    }

    public function testDontAddHeaderIfNoBody()
    {
        $middleware = new AutoContentLength();

        $request = new Request();

        /** @var PromiseInterface $response */
        $response = $middleware($request, new ConnectionStub(), new EmptyResponseMiddleware());

        $extractedResponse = null;
        $response->then(function (Response $response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertNotNull($extractedResponse);
        /**
         * @var Response $extractedResponse
         */
        $this->assertFalse($extractedResponse->hasHeader('content-length'));
    }
}