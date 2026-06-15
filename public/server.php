<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Domain\AccountRepository;
use App\Application\AccountService;
use App\Http\EventController;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use React\EventLoop\Loop;

$controller = new EventController(
    new AccountService(new AccountRepository())
);

$http = new HttpServer(
    static fn ($request) => $controller->handle($request)
);

$port = getenv('PORT') ?: '8080';
$socket = new SocketServer('0.0.0.0:' . $port);
$http->listen($socket);

echo "Listening on http://0.0.0.0:{$port}\n";
Loop::run();