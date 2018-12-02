<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

//Normal port is 554 but we use 5540 instead to avoid root required
$socket = new \React\Socket\TcpServer('tcp://0.0.0.0:5540', $loop);

$middlewares = [
    function (\PhpBg\Rtsp\Message\Request $request, \React\Socket\ConnectionInterface $connection, $next) {
        // echo request middleware
        echo $request;
        return $next($request, $connection);
    },
    function (\PhpBg\Rtsp\Message\Request $request, \React\Socket\ConnectionInterface $connection, $next) {
        // echo response middleware
        $response = $next($request, $connection);
        if (!($response instanceof \React\Promise\PromiseInterface)) {
            $response = new \React\Promise\FulfilledPromise($response);
        }
        return $response->then(function($resolvedResponse) {
            echo $resolvedResponse."\r\n";
            return $resolvedResponse;
        });
    },
    new \PhpBg\Rtsp\Middleware\AutoCseq(),
    new \PhpBg\Rtsp\Middleware\AutoContentLength(),
    function (\PhpBg\Rtsp\Message\Request $request, \React\Socket\ConnectionInterface $connection) {
        // 200 OK response middleware
        return \PhpBg\Rtsp\Message\MessageFactory::response();
    }
];

$server = new \PhpBg\Rtsp\Server(new \PhpBg\Rtsp\Middleware\MiddlewareStack($middlewares));

$server->on('error', function (\Exception $e) {
    echo $e->getMessage() . "\r\n";
    echo $e->getTraceAsString() . "\r\n";
});

$server->listen($socket);
echo "Server started\r\n";
echo "Open any decent video player (e.g. vlc, mpv) and open rtsp://localhost:5540\r\n";
$loop->run();