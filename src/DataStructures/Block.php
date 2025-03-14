<?php

namespace Blockchain\DataStructures;

use DateTimeImmutable;

class Block
{
    public $header;
    public $transactions;

    public function __construct(
        int $index,
        DateTimeImmutable $createdAt,
        array $transactions,
        int $difficulty,
        int $nonce,
        string $previousHash = ''
    ) {
        $this->transactions = $transactions;
        $this->header = new BlockHeader($index, $createdAt, $transactions, $difficulty, $nonce, $previousHash);
    }

    public static function genesis(): Block
    {
        $createdAt = new DateTimeImmutable('2025-03-11 00:00:00');
        return new self(0, $createdAt, [], 1, 0);
    }

    public static function from(array $data): Block
    {
        return new self(
            $data['index'],
            new DateTimeImmutable($data['createdAt']),
            $data['transactions'],
            $data['difficulty'],
            $data['nonce'],
            $data['previousHash']
        );
    }

    public static function hash(Block $block): string
    {
        return BlockHeader::hash($block->header);
    }

    public function isNextOf(Block $target): bool
    {
        return $this->header->isNextOf($target->header);
    }

    public function isEqual(Block $target): bool
    {
        return $this->header->isEqual($target->header)
            && $this->transactions === $target->transactions;
    }

    public function getIndex(): int
    {
        return $this->header->index;
    }

    public function getDifficulty(): int
    {
        return $this->header->difficulty;
    }

    public function getHash(): string
    {
        return $this->header->hash;
    }

    public function getMerkleRoot(): string
    {
        return $this->header->merkleRoot;
    }

    public function hasMerkleRoot(string $hash): bool
    {
        return $this->header->merkleRoot === $hash;
    }
}