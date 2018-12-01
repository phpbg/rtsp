<?php

namespace PhpBg\Rtsp\Tests;

use PhpBg\Rtsp\Message\MessageException;
use PhpBg\Rtsp\Message\MessageFactory;
use PhpBg\Rtsp\Message\Request;
use PhpBg\Rtsp\Server;
use PHPUnit\Framework\TestCase;
use React\Socket\ConnectionInterface;

class ServerTest extends TestCase
{
    public function setUp()
    {
        $this->socket = new SocketServerStub();
        $this->connection = $this->getConnection();
    }

    private function getConnection(): ConnectionInterface {
        return $this->getMockBuilder('React\Socket\Connection')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'write',
                    'end',
                    'close',
                    'pause',
                    'resume',
                    'isReadable',
                    'isWritable',
                    'getRemoteAddress',
                    'getLocalAddress',
                    'pipe'
                )
            )
            ->getMock();
    }

    public function testSimpleRequest() {
        $called = null;
        $server = new Server(function ($request, $connection) use (&$called) {
            ++$called;
            $this->assertInstanceOf(Request::class, $request);
            $this->assertInstanceOf(ConnectionInterface::class, $connection);

            // Just do basic checks over $request
            /** @var Request $request */
            $this->assertSame(1, count($request->headers));
            $this->assertSame('1', $request->getHeader('cseq'));
        });

        $server->on('error', function($exception) {
            throw $exception;
        });

        $server->listen($this->socket);

        $this->socket->emit('connection', array($this->connection));
        $this->connection->emit('data', array("OPTIONS / RTSP/1.0\r\nCseq:1\r\n\r\n"));

        $this->assertSame(1, $called);
    }

    public function testInvalidMethodRequest() {
        $server = new Server(function () use (&$called) {
            throw new \Exception();
        });

        $error = null;
        $server->on('error', function($exception) use (&$error) {
            $error = $exception;
        });

        $server->listen($this->socket);
        $this->socket->emit('connection', array($this->connection));
        $this->connection->emit('data', array("FOO_METHOD / RTSP/1.0\r\nCseq:1\r\n\r\n"));

        $this->assertNotNull($error);
        $this->assertInstanceOf(MessageException::class, $error);
    }

    /**
     * @expectedException \PhpBg\Rtsp\ServerException
     */
    public function testCallbackDoNotReturnResponse() {
        $server = new Server(function () {
            return false;
        });

        $server->on('error', function($exception) {
            throw $exception;
        });

        $server->listen($this->socket);

        $this->socket->emit('connection', array($this->connection));
        $this->connection->emit('data', array("OPTIONS / RTSP/1.0\r\nCseq:1\r\n\r\n"));
    }

    public function testRequestWithResponse() {
        $called = null;
        $server = new Server(function () use (&$called) {
            ++$called;
            return MessageFactory::response(200, ['foo'=>'bar'], 'baz', 'qux');
        });

        $server->on('error', function($exception) {
            throw $exception;
        });

        $server->listen($this->socket);

        $this->connection->expects($this->exactly(1))->method('write')->with($this->identicalTo("RTSP/1.0 200 qux\r\nfoo: bar\r\n\r\nbaz"));

        $this->socket->emit('connection', array($this->connection));
        $this->connection->emit('data', array("OPTIONS / RTSP/1.0\r\nCseq:1\r\n\r\n"));

        $this->assertSame(1, $called);
    }

    public function testNoMemoryLeak() {
        $called = null;
        $server = new Server(function () use (&$called) {
            ++$called;
            return MessageFactory::response(200, ['foo'=>'bar'], 'baz', 'qux');
        });

        $server->on('error', function($exception) {
            throw $exception;
        });

        $server->listen($this->socket);

        for ($i = 0; $i < 100000; $i++) {
            if ($i === 100) {
                //Warm a bit then take initial memory usage
                $initialMemoryUsage = memory_get_usage(false);
            }
            $connection = new ConnectionStub();
            $this->socket->emit('connection', array($connection));
            $connection->emit('data', array("OPTIONS / RTSP/1.0\r\nCseq:1\r\n\r\n"));
            $connection->emit('close');
            $connection->removeAllListeners();
        }
        $finalMemoryUsage = memory_get_usage(false);
        $this->assertTrue($finalMemoryUsage <= 1.001 * $initialMemoryUsage, "Memory grown from $initialMemoryUsage to $finalMemoryUsage");

        $this->assertTrue($called !== null && $called>0);
    }
}