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

    public function addTransaction(string $transaction): void
    {
        $this->pendingTransactions[] = $transaction;
    }

    public function getPendingTransactions(): array
    {
        return $this->pendingTransactions;
    }

    public function isValid(): bool
    {
        if (!$this->blocks[0]->isEqual(Block::genesis())) {
            return false;
        }
        $count = count($this->blocks);
        for ($i = 1; $i < $count; $i++) {
            if (!$this->blocks[$i]->isNextOf($this->blocks[$i - 1])) {
                return false;
            }
        }
        return true;
    }

    public function verify(string $transaction, string $merkleRoot): bool
    {
        $block = $this->findBlock($merkleRoot);
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
}