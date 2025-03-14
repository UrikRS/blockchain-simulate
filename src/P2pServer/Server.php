<?php
namespace Blockchain\P2pServer;

use Blockchain\DataStructures\Block;
use Blockchain\DataStructures\Blockchain;
use Blockchain\HashDifficulties\ZeroPrefix;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class Server implements MessageComponentInterface
{
    private $connection;
    private $clients;
    private $peers = [];
    private $blockchain;

    public function __construct(Blockchain $blockchain)
    {
        $this->clients = new SplObjectStorage;
        $this->blockchain = $blockchain;
    }

    public function onOpen(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->clients->attach($connection);
        echo "New connection\n";

        $nodeId = uniqid('node_');
        $connection->send(json_encode(['type' => 'NODE_ID', 'data' => $nodeId]));
        $connection->send(json_encode(['type' => 'REQUEST_PEERS']));
        $connection->send(json_encode(['type' => 'REQUEST_LATEST_BLOCK']));
        $this->broadcast('NEW_PEER', $connection->remoteAddress);
    }

    public function onMessage(ConnectionInterface $from, $message): void
    {
        $data = json_decode($message, true);

        switch ($data['type']) {
            case 'NEW_BLOCK':
                $block = $data['data'];
                if (Blockchain::isValid($this->blockchain) && $this->blockchain->getLast()->getHash() === $block['previousHash']) {
                    $this->blockchain->add(Block::from($block));
                    $this->broadcast('NEW_BLOCK', $block);
                }
                break;
            case 'NEW_TRANSACTION':
                $transaction = $data['data'];
                if ($this->blockchain->isValidTransaction($transaction)) {
                    $this->blockchain->addTransaction($transaction);
                    $this->broadcast('NEW_TRANSACTION', $transaction);
                }
                break;
            case 'REQUEST_CHAIN':
                $from->send(json_encode([
                    'type' => 'FULL_CHAIN',
                    'data' => $this->blockchain->blocks
                ]));
                break;
            case 'FULL_CHAIN':
                $receivedChain = $data['data'];
                // Validate and replace if the received chain is longer
                if (Blockchain::isValid($receivedChain) && count($receivedChain) > count($this->blockchain->blocks)) {
                    $this->blockchain->replaceWith($receivedChain);
                }
                break;
            case 'REQUEST_BLOCKS':
                $sinceHash = $data['data'];
                $missingBlocks = $this->blockchain->getBlocksAfter($sinceHash);
                $from->send(json_encode(['type' => 'SYNC_BLOCKS', 'data' => $missingBlocks]));
                break;
            case 'SYNC_BLOCKS':
                $missingBlocks = $data['data'];
                foreach ($missingBlocks as $block) {
                    $this->blockchain->add(Block::from($block));
                }
                break;
            case 'REQUEST_LATEST_BLOCK':
                $from->send(json_encode([
                    'type' => 'LATEST_BLOCK',
                    'data' => $this->blockchain->getLast()
                ]));
                break;
            case 'LATEST_BLOCK':
                $latestBlock = $data['data'];
                if (Blockchain::isValid($this->blockchain) && $this->blockchain->getLast()->getHash() === $latestBlock['previousHash']) {
                    $this->blockchain->add(Block::from($latestBlock));
                } else {
                    $from->send(json_encode(['type' => 'REQUEST_CHAIN']));
                }
                break;
            case 'NEW_PEER':
                $peer = $data['data'];
                if (!in_array($peer, $this->peers)) {
                    $this->peers[] = $peer;
                    $this->broadcast('NEW_PEER', $peer);
                }
                break;
            case 'REQUEST_PEERS':
                $peers = $this->getConnectedPeerAddresses();
                $from->send(json_encode([
                    'type' => 'PEER_LIST',
                    'data' => $peers
                ]));
                break;
            case 'PEER_LIST':
                $peers = $data['data'];
                foreach ($peers as $peer) {
                    if (!in_array($peer, $this->peers)) {
                        $this->peers[] = $peer;
                    }
                }
                break;
            case 'MINE_BLOCK':
                $block = Miner::mine($this->blockchain, new ZeroPrefix);
                if (!empty($block)) {
                    $this->broadcast('NEW_BLOCK', $block);
                }
                break;
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

    private function broadcast(string $type, $data): void
    {
        foreach ($this->clients as $client) {
            if ($client !== $this->connection) {
                $client->send(json_encode(['type' => $type, 'data' => $data]));
            }
        }
    }

    private function getConnectedPeerAddresses(): array
    {
        $peers = [];
        foreach ($this->clients as $client) {
            $socket = $client->stream;
            stream_socket_get_name($socket, true);
            $peerList[] = stream_socket_get_name($socket, true);
        }
        return $peers;
    }
}