<?php

require __DIR__ . '/vendor/autoload.php';

use function Ratchet\Client\connect;

connect('ws://localhost:8080')->then(function ($connection) {
    echo "Connected to Blockchain Network\n";

    $connection->on('message', function ($message) use ($connection) {
        $data = json_decode($message, true);

        if ($data['type'] === 'BLOCKCHAIN') {
            echo "Received blockchain data: " . count($data['data']) . " blocks\n";
        } elseif ($data['type'] === 'NEW_BLOCK') {
            echo "New block received: " . json_encode($data['data']) . "\n";
        }
    });
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});
