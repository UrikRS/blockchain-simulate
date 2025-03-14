<?php

namespace Blockchain\P2pServer;

use Blockchain\DataStructures\Block;
use Blockchain\DataStructures\Blockchain;
use Blockchain\HashDifficulties\HashDifficulty;
use DateTimeImmutable;

final class Miner
{
    public static function mine(Blockchain $blockchain, HashDifficulty $hashDifficulty): ?Block
    {
        $transactions = $blockchain->getPendingTransactions();
        if (empty($transactions)) {
            return null;
        }

        $nonce = 0;
        $lastBlock = $blockchain->getLast();
        $difficulty = $lastBlock->getDifficulty();
        $index = $lastBlock->getIndex() + 1;
        $previousHash = $lastBlock->getHash();
        $createdAt = new DateTimeImmutable();

        while (true) {
            $block = new Block($index, $createdAt, $transactions, $difficulty, $nonce, $previousHash);
            if ($hashDifficulty->match($block->getHash(), $difficulty)) {
                return $block;
            }
            $nonce++;
        }
    }
}