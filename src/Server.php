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
use PhpBg\Rtsp\Message\MessageFactory;
use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Message\RequestParser;
use PhpBg\Rtsp\Message\Response;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
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
     * @param callable $callback
     *   Callback that will receive (PhpBg\Rtsp\Message\Request, React\Socket\ConnectionInterface) and return
     *     * a PhpBg\Rtsp\Message\Response
     *     * a React\Promise\PromiseInterface that will resolve in a Response
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
        // Convert all non-promise response to promises
        // There is little overhead for this, but this allows simpler code
        if (!isset($response) || !$response instanceof PromiseInterface) {
            $response = new FulfilledPromise($response);
        }

        $response->done(function ($resolvedResponse) use ($connection) {
            if ($resolvedResponse instanceof Response) {
                $connection->write($resolvedResponse->toTransport());
                return;
            }
            $message = 'The response callback is expected to resolve with an object implementing PhpBg\Rtsp\Message\Response, but resolved with "%s" instead.';
            $message = sprintf($message, is_object($resolvedResponse) ? get_class($resolvedResponse) : gettype($resolvedResponse));
            $this->emit('error', [new ServerException($message)]);
        }, function ($error) use ($connection) {
            $message = 'The response callback is expected to resolve with an object implementing PhpBg\Rtsp\Message\Response, but rejected with "%s" instead.';
            $message = sprintf($message, is_object($error) ? get_class($error) : gettype($error));
            $previous = null;
            if ($error instanceof \Throwable || $error instanceof \Exception) {
                $previous = $error;
            }
            $exception = new \RuntimeException($message, null, $previous);
            $this->emit('error', [$exception]);

            $rtspResponse = MessageFactory::response(500, [], null, 'internal server error');
            $connection->write($rtspResponse->toTransport());
        });
    }

}