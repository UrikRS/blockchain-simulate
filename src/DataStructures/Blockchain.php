<?php

namespace Blockchain\DataStructures;

class Blockchain
{
    public $blocks;
    private $pendingTransactions = [];

    public function __construct()
    {
        $this->blocks = [Block::genesis()];
    }

    public function getLast(): Block
    {
        return $this->blocks[count($this->blocks) - 1];
    }

    public function add(Block $block): void
    {
        $this->blocks[] = $block;
        $this->pendingTransactions = [];
    }

    public function getBlocksAfter(string $hash): array
    {
        foreach ($this->blocks as $i => $block) {
            if ($block->getHash() === $hash) {
                return array_slice($this->blocks, $i + 1);
            }
        }
        return [];
    }

    public function addTransaction(string $transaction): void
    {
        $this->pendingTransactions[] = $transaction;
    }

    public function getPendingTransactions(): array
    {
        return $this->pendingTransactions;
    }

    public static function isValid(self $blockchain): bool
    {
        if (!$blockchain->blocks[0]->isEqual(Block::genesis())) {
            return false;
        }
        $count = count($blockchain->blocks);
        for ($i = 1; $i < $count; $i++) {
            if (!$blockchain->blocks[$i]->isNextOf($blockchain->blocks[$i - 1])) {
                return false;
            }
        }
        return true;
    }

    public function isValidTransaction($transaction): bool
    {
        return isset($transaction['from'], $transaction['to'], $transaction['amount'], $transaction['signature'])
            && $transaction['amount'] > 0;
    }


    public function verify(string $transaction, string $merkleRoot): bool
    {
        $block = $this->findBlock($merkleRoot);
        echo $merkleRoot;
        $proofs = MerkleTree::getProofs($block->transactions, $transaction);
        return MerkleTree::verify($transaction, $merkleRoot, $proofs);
    }

    public function findBlock(string $merkleRoot): ?Block
    {
        foreach ($this->blocks as $block) {
            if ($block->hasMerkleRoot($merkleRoot)) {
                return $block;
            }
        }
        return null;
    }

    public function replaceWith(array $blockchainData): void
    {
        $this->blocks = [];
        foreach ($blockchainData as $block) {
            $this->add(Block::from($block));
        }
    }
}