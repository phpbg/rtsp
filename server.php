<?php

require __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

//Normal port is 554 but we use 5540 instead to avoid root required
$socket = new \React\Socket\TcpServer('tcp://0.0.0.0:5540', $loop);

$server = new \PhpBg\Rtsp\Server(function (\PhpBg\Rtsp\Message\Request $request, \React\Socket\ConnectionInterface $connection) {
    echo $request;
    $response = \PhpBg\Rtsp\Message\MessageFactory::response();
    $response->setHeader('cseq', $request->getHeader('cseq'));
    return $response;
});

$server->on('error', function (\Exception $e) {
    echo $e->getMessage() . "\r\n";
    echo $e->getTraceAsString() . "\r\n";
});

$server->listen($socket);
echo "Server started\r\n";
echo "Open any decent video player (e.g. vlc, mpv) and open rtsp://localhost:5540\r\n";
$loop->run();