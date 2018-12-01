# Pure PHP RTSP 1.0 server
This is a library that allow you to build a [RTSP/1.0](https://www.ietf.org/rfc/rfc2326.txt) server.

# Examples
This is a very minimal server that will
 * dump requests
 * reply with 200 OK (which is not very useful :-)):

```php
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
```

# Install

```
composer require phpbg/rtsp
```

# Contribute
## Tests
To run unit tests launch:

    php vendor/phpunit/phpunit/phpunit --coverage-text -c phpunit.xml
    
NB: launching with code coverage increase greatly the time required for tests to run, especially memory leak searching tests
