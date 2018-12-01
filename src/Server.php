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

namespace PhpBg\Rtsp;

use Evenement\EventEmitter;
use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Message\RequestParser;
use PhpBg\Rtsp\Message\Response;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;

/**
 *
 * Events
 *  * error event:
 *     The `error` event will be emitted whenever there's an error accepting or handling a connection from a client.
 *
 *     ```php
 *     $server->on('error', function (Exception $e) {
 *         echo 'error: ' . $e->getMessage() . PHP_EOL;
 *     });
 *     ```
 *
 *     Note that this is not a fatal error event, i.e. the server keeps listening for
 *     new connections even after this event.
 *
 */
class Server extends EventEmitter
{
    protected $callback;

    /**
     * Server constructor
     *
     * @param callable $callback Callback that will receive (Request, React\Socket\ConnectionInterface) and may return a Response
     */
    public function __construct(Callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Starts listening for RTSP requests on the given socket server instance
     *
     * @param ServerInterface $socket
     */
    public function listen(ServerInterface $socket)
    {
        $socket->on('connection', [$this, 'handleConnection']);
    }

    /**
     * Handle incoming connections
     *
     * @param ConnectionInterface $connection
     */
    protected function handleConnection(ConnectionInterface $connection)
    {
        $requestParser = new RequestParser();
        $requestParser->on('error', function (\Exception $exception) use ($connection) {
            $connection->close();
            $this->emit('error', [$exception]);
        });
        $requestParser->on('request', function (Request $request) use ($connection) {
            $this->handleRequest($request, $connection);
        });

        $connection->on('data', [$requestParser, 'feed']);
        $connection->on('end', function () use ($requestParser) {
            $requestParser->removeAllListeners();
        });

        $connection->on('error', function (\Exception $exception) use ($requestParser) {
            $requestParser->removeAllListeners();
            $this->emit('error', [$exception]);
        });

        $connection->on('close', function () use ($requestParser) {
            $requestParser->removeAllListeners();
        });
    }

    /**
     * Handle incoming requests
     *
     * @param Request $request
     * @param ConnectionInterface $connection
     */
    protected function handleRequest(Request $request, ConnectionInterface $connection)
    {
        $callback = $this->callback;
        try {
            $response = $callback($request, $connection);
        } catch (\Exception $e) {
            $connection->close();
            $this->emit('error', [$e]);
            return;
        }
        if (isset($response)) {
            if ($response instanceof Response) {
                $connection->write($response->toTransport());
                return;
            }
            $this->emit('error', [new ServerException("Your handler did return something, but it wasn't a Response")]);
        }
    }
}