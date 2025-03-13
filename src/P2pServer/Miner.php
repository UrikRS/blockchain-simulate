<?php

namespace Blockchain\P2pServer;

use Blockchain\DataStructures\Block;
use Blockchain\DataStructures\Blockchain;
use Blockchain\HashDifficulties\HashDifficulty;
use DateTimeImmutable;

final class Miner
{
    public $blockchain;
    private $hashDifficulty;
    private $peerNodes = ["http://127.0.0.1:8080"];

    public function __construct(Blockchain $blockchain, HashDifficulty $hashDifficulty)
    {
        $this->blockchain = $blockchain;
        $this->hashDifficulty = $hashDifficulty;
    }

    public function mine(): ?Block
    {
        $transactions = $this->blockchain->getPendingTransactions();
        if (empty($transactions)) {
            return null;
        }

        $nonce = 0;
        $lastBlock = $this->blockchain->getLast();
        $difficulty = $lastBlock->getDifficulty();
        $index = $lastBlock->getIndex() + 1;
        $previousHash = $lastBlock->getHash();
        $createdAt = new DateTimeImmutable();

        while (true) {
            $block = new Block($index, $createdAt, $transactions, $difficulty, $nonce, $previousHash);
            if ($this->hashDifficulty->match($block->getHash(), $difficulty)) {
                $this->blockchain->add($block);
                return $block;
            }
            $nonce++;
        }
    }

    public function replaceBlockchain(Blockchain $blockchain): void
    {
        if (!$blockchain->isValid()) {
            return;
        }
        $this->blockchain = $blockchain;
    }

    public function sync()
    {
        foreach ($this->peerNodes as $node) {
            $response = file_get_contents($node . "/blockchain");
            $remoteBlockchain = json_decode($response, true);

            if (count($remoteBlockchain) > count($this->blockchain->blocks)) {
                $this->blockchain->blocks = array_map(function ($block) {
                    return new Block(
                        $block['index'],
                        new DateTimeImmutable($block['createdAt']),
                        $block['transactions'],
                        $block['difficulty'],
                        $block['nonce'],
                        $block['previousHash']
                    );
                }, $remoteBlockchain);

                echo "Blockchain updated from peer!\n";
            }
        }
    }
}