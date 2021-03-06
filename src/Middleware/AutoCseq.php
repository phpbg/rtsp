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

namespace PhpBg\Rtsp\Middleware;

use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Message\Response;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

/**
 * Middleware that automatically add cseq header to responses as required by https://www.ietf.org/rfc/rfc2326.txt
 */
class AutoCseq
{

    /**
     * This middleware just looks like a standard middleware: invokable, receive request and return response
     *
     * @param Request $request
     * @param ConnectionInterface $connection
     * @param callable $next
     * @return Response|PromiseInterface
     */
    public function __invoke(Request $request, ConnectionInterface $connection, callable $next)
    {
        $response = $next($request, $connection);
        if (!($response instanceof PromiseInterface)) {
            $response = new FulfilledPromise($response);
        }
        return $response->then(function (Response $resolvedResponse) use ($request) {
            if (!$resolvedResponse->hasHeader('cseq') && $request->hasHeader('cseq')) {
                $resolvedResponse->setHeader('cseq', $request->getHeader('cseq'));
            }
            return $resolvedResponse;
        });
    }
}
