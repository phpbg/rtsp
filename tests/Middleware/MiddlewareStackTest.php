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
use PhpBg\Rtsp\Middleware\MiddlewareStack;
use PhpBg\Rtsp\Tests\ConnectionStub;
use PhpBg\Rtsp\Tests\Mock\CallNextMiddleware;
use PhpBg\Rtsp\Tests\Mock\EmptyResponseMiddleware;
use PhpBg\Rtsp\Tests\Mock\ParametrizedResponseMiddleware;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

class MiddlewareStackTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testEmptyStack()
    {
        new MiddlewareStack([]);
    }

    public function testInvokeSingle()
    {
        $middleware1 = new ParametrizedResponseMiddleware(1);

        $stack = new MiddlewareStack([$middleware1]);

        $response = $stack(new Request(), new ConnectionStub());
        $this->assertSame(1, $response->statusCode);
    }

    public function testInvokeMany()
    {
        $middleware1 = new CallNextMiddleware();
        $middleware2 = new CallNextMiddleware();
        $middleware3 = $this->getMockBuilder(ParametrizedResponseMiddleware::class)->setMethods(['__invoke'])->getMock();
        $middleware3->expects($this->once())->method('__invoke')->willReturn(new Response());

        $stack = new MiddlewareStack([$middleware1, $middleware2, $middleware3]);

        $stack(new Request(), new ConnectionStub());

        $this->assertSame(1, $middleware1->calls);
        $this->assertSame(1, $middleware2->calls);
    }
}