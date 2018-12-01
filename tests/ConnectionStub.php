<?php

namespace PhpBg\Rtsp\Tests;

use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class ConnectionStub extends EventEmitter implements ConnectionInterface
{


    public function getRemoteAddress()
    {
        // TODO: Implement getRemoteAddress() method.
    }


    public function getLocalAddress()
    {
        // TODO: Implement getLocalAddress() method.
    }


    public function isReadable()
    {
        // TODO: Implement isReadable() method.
    }


    public function pause()
    {
        // TODO: Implement pause() method.
    }


    public function resume()
    {
        // TODO: Implement resume() method.
    }


    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        // TODO: Implement pipe() method.
    }


    public function close()
    {
        // TODO: Implement close() method.
    }


    public function isWritable()
    {
        // TODO: Implement isWritable() method.
    }


    public function write($data)
    {
        // TODO: Implement write() method.
    }


    public function end($data = null)
    {
        // TODO: Implement end() method.
    }
}