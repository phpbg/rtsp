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
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

/**
 * Middleware that run a stack of middlewares
 */
class MiddlewareStack
{
    /**
     * @var callable[]
     */
    protected $middlewares;

    /**
     * @param callable[] $middlewares Stack of middlewares you want to run
     */
    public function __construct(array $middlewares)
    {
        if (empty($middlewares)) {
            throw new \RuntimeException('No middleware to run');
        }
        $this->middlewares = array_values($middlewares);
    }

    /**
     * This middleware just looks like a standard middleware: invokable, receive request and return response
     * It has no $next handler because it is meant to be the last middleware in stack
     *
     * @param Request $request
     * @param ConnectionInterface $connection
     * @return Response|PromiseInterface
     */
    public function __invoke(Request $request, ConnectionInterface $connection)
    {
        return $this->call($request, $connection, 0);
    }

    /**
     * Recursively executes middlewares
     *
     * @param Request $request
     * @param ConnectionInterface $connection
     * @param int $position
     * @return mixed
     */
    protected function call(Request $request, ConnectionInterface $connection, int $position)
    {
        // final request handler will be invoked without a next handler
        if (!isset($this->middlewares[$position + 1])) {
            $handler = $this->middlewares[$position];
            return $handler($request, $connection);
        }

        $next = function (Request $request, ConnectionInterface $connection) use ($position) {
            return $this->call($request, $connection, $position + 1);
        };

        // invoke middleware request handler with next handler
        $handler = $this->middlewares[$position];
        return $handler($request, $connection, $next);
    }
}
