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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use React\Socket\ConnectionInterface;

/**
 * Middleware that log requests and responses
 */
class Log
{

    private $logger;
    private $level;
    private $errorLevel;

    /**
     * Log constructor.
     * @param LoggerInterface $logger
     * @param string $level Standard request / response log level
     * @param string $errorLevel Errors log level
     */
    public function __construct(LoggerInterface $logger, $level = LogLevel::DEBUG, $errorLevel = LogLevel::WARNING)
    {
        $this->logger = $logger;
        $this->level = $level;
        $this->errorLevel = $errorLevel;
    }

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
        $this->logger->log($this->level, "Request:\r\n$request");
        $response = $next($request, $connection);
        if (!($response instanceof PromiseInterface)) {
            $response = new FulfilledPromise($response);
        }
        return $response->then(function (Response $resolvedResponse) use ($request) {
            $this->logger->log($this->level, "Response:\r\n$resolvedResponse");
            return $resolvedResponse;
        }, function($reason) {
            if ($reason instanceof \Exception) {
                $this->logger->log($this->errorLevel, "Internal server error", ['exception' => $reason]);
            } else {
                $this->logger->log($this->errorLevel, "Unexpected internal server error", ['data' => $reason]);
            }
            return reject($reason);
        });
    }
}
