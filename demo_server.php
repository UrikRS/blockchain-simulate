<?php

require __DIR__ . '/vendor/autoload.php';

use Blockchain\DataStructures\Blockchain;
use Blockchain\P2pServer\Server;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

$blockchain = new Blockchain();
$server = IoServer::factory(new HttpServer(new WsServer(new Server($blockchain))), 8080);
$server->run();
