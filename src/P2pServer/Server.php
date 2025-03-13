<?php
namespace Blockchain\P2pServer;

use Blockchain\DataStructures\Block;
use Blockchain\DataStructures\Blockchain;
use DateTimeImmutable;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class Server implements MessageComponentInterface
{
    private $clients;
    private $blockchain;

    public function __construct(Blockchain $blockchain)
    {
        $this->clients = new SplObjectStorage;
        $this->blockchain = $blockchain;
    }

    public function onOpen(ConnectionInterface $connection): void
    {
        $this->clients->attach($connection);
        echo "New connection\n";
        $connection->send(json_encode(["type" => "BLOCKCHAIN", "data" => $this->blockchain->blocks]));
    }

    public function onMessage(ConnectionInterface $from, $message): void
    {
        $data = json_decode($message, true);

        if ($data['type'] === 'NEW_BLOCK') {
            $block = $data['data'];
            if ($this->blockchain->isValid() && end($this->blockchain->blocks)->getHash() === $block['previousHash']) {
                $this->blockchain->add(new Block(
                    $block['index'],
                    new DateTimeImmutable($block['createdAt']),
                    $block['transactions'],
                    $block['difficulty'],
                    $block['nonce'],
                    $block['previousHash']
                ));

                foreach ($this->clients as $client) {
                    $client->send(json_encode(["type" => "NEW_BLOCK", "data" => $block]));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $connection): void
    {
        $this->clients->detach($connection);
        echo "Connection closed\n";
    }

    public function onError(ConnectionInterface $connection, Exception $e): void
    {
        echo "Error: {$e->getMessage()}\n";
        $connection->close();
    }
}