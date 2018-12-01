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

namespace PhpBg\Rtsp\Message;

use PhpBg\Rtsp\Message\Enum\RtspVersion;

class MessageFactory
{

    /**
     * Create a response
     * @param int $statusCode
     * @param array $headers
     * @param null $body
     * @param string $reasonPhrase
     * @param string $rtspVersion
     * @return Response
     */
    public static function response(int $statusCode = 200, array $headers = [], $body = null, string $reasonPhrase = 'ok', string $rtspVersion = RtspVersion::RTSP10): Response
    {
        $response = new Response();
        $response->statusCode = $statusCode;
        $response->headers = $headers;
        $response->body = $body;
        $response->reasonPhrase = $reasonPhrase;
        $response->rtspVersion = $rtspVersion;
        return $response;
    }
}