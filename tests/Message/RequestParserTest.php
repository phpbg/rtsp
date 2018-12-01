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

namespace PhpBg\Rtsp\Tests\Message;

use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Message\RequestParser;
use PHPUnit\Framework\TestCase;

class RequestParserTest extends TestCase
{
    public function testFeedSingleMessageOneShot()
    {
        $optionMessage = $this->getOptionMessage();
        $rp = new RequestParser();
        $request = null;
        $rp->on('request', function ($newRequest) use (&$request) {
            $request = $newRequest;
        });
        $rp->feed($optionMessage);

        $this->assertRequestMatchOption($request);
    }

    public function testFeedSingleMessageProgressive()
    {
        $optionMessage = $this->getOptionMessage();
        $rp = new RequestParser();
        $request = null;
        $rp->on('request', function ($newRequest) use (&$request) {
            $request = $newRequest;
        });

        $optionMessageArray = str_split($optionMessage);
        foreach ($optionMessageArray as $char) {
            $rp->feed($char);
        }

        $this->assertRequestMatchOption($request);
    }

    public function testFeedTwoMessagesOneShot()
    {
        $rp = new RequestParser();
        $requests = [];
        $rp->on('request', function ($request) use (&$requests) {
            $requests[] = $request;
        });
        $optionMessage = $this->getOptionMessage();
        $describeMessage = $this->getDescribeMessage();
        $rp->feed($optionMessage . $describeMessage);

        $this->assertCount(2, $requests);
        $this->assertRequestMatchOption($requests[0]);
        $this->assertRequestMatchDescribe($requests[1]);

    }

    public function testFeedTwoMessagesProgressive()
    {
        $rp = new RequestParser();
        $request = null;
        $rp->on('request', function ($newRequest) use (&$request) {
            $request = $newRequest;
        });
        $optionMessage = $this->getOptionMessage();
        $optionMessageArray = str_split($optionMessage);
        foreach ($optionMessageArray as $char) {
            $rp->feed($char);
        }
        $this->assertRequestMatchOption($request);

        $describeMessage = $this->getDescribeMessage();
        $describeMessageArray = str_split($describeMessage);
        foreach ($describeMessageArray as $char) {
            $rp->feed($char);
        }
        $rp->feed($describeMessage);
        $this->assertRequestMatchDescribe($request);

    }

    public function testNoMemoryLeak()
    {
        $optionMessage = $this->getOptionMessage();
        $rp = new RequestParser();

        for ($i = 0; $i < 100000; $i++) {
            if ($i === 100) {
                //Warm a bit then take initial memory usage
                $initialMemoryUsage = memory_get_usage(false);
            }
            $rp->feed($optionMessage);
        }
        $finalMemoryUsage = memory_get_usage(false);
        $this->assertTrue($finalMemoryUsage <= 1.001 * $initialMemoryUsage, "Memory grown from $initialMemoryUsage to $finalMemoryUsage");
    }

    private function assertRequestMatchOption($request)
    {
        $this->assertNotNull($request);
        $this->assertTrue($request instanceof Request);
        $this->assertEquals('OPTIONS rtsp://192.168.1.22:5540/257 RTSP/1.0', $request->getFirstLine());
        $this->assertEquals('OPTIONS', $request->method);
        $this->assertEquals('rtsp://192.168.1.22:5540/257', $request->uri);
        $this->assertSame(null, $request->body);
        $this->assertTrue($request->hasHeader('cseq'));
        $this->assertEquals(1, $request->getHeader('cseq'));
        $this->assertTrue($request->hasHeader('user-agent'));
        $this->assertEquals('mpv 0.14.0', $request->getHeader('user-agent'));
        $this->assertTrue($request->hasHeader('user-AGENT'));
        $this->assertEquals('mpv 0.14.0', $request->getHeader('user-AGENT'));
        $this->assertFalse($request->hasHeader('foo'));
        $this->assertSame(null, $request->getHeader('foo'));
    }

    private function assertRequestMatchDescribe($request)
    {
        $this->assertNotNull($request);
        $this->assertTrue($request instanceof Request);
        $this->assertEquals('DESCRIBE rtsp://192.168.1.22:5540/257 RTSP/1.0', $request->getFirstLine());
        $this->assertEquals('DESCRIBE', $request->method);
        $this->assertEquals('rtsp://192.168.1.22:5540/257', $request->uri);
        $this->assertSame(null, $request->body);
        $this->assertTrue($request->hasHeader('cseq'));
        $this->assertEquals(2, $request->getHeader('cseq'));
        $this->assertTrue($request->hasHeader('user-agent'));
        $this->assertEquals('mpv 0.14.0', $request->getHeader('user-agent'));
        $this->assertTrue($request->hasHeader('user-AGENT'));
        $this->assertEquals('mpv 0.14.0', $request->getHeader('user-AGENT'));
        $this->assertTrue($request->hasHeader('accept'));
        $this->assertEquals('application/sdp', $request->getHeader('accept'));
        $this->assertFalse($request->hasHeader('foo'));
        $this->assertSame(null, $request->getHeader('foo'));
    }

    private function getOptionMessage()
    {
        return "OPTIONS rtsp://192.168.1.22:5540/257 RTSP/1.0\r\ncseq: 1\r\nuser-agent: mpv 0.14.0\r\n\r\n";
    }

    private function getDescribeMessage()
    {
        return "DESCRIBE rtsp://192.168.1.22:5540/257 RTSP/1.0\r\naccept: application/sdp\r\ncseq: 2\r\nuser-agent: mpv 0.14.0\r\n\r\n";
    }
}