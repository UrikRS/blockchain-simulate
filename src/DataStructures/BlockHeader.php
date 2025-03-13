<?php

namespace Blockchain\DataStructures;

use DateTimeImmutable;

class BlockHeader
{
    public $index;
    public $createdAt;
    public $merkleRoot;
    public $difficulty;
    public $nonce;
    public $previousHash;
    public $hash;

    public function __construct(
        int $index,
        DateTimeImmutable $createdAt,
        array $transactions,
        int $difficulty,
        int $nonce,
        string $previousHash = ''
    ) {
        $this->index = $index;
        $this->createdAt = $createdAt;
        $this->merkleRoot = MerkleTree::from($transactions)->root;
        $this->difficulty = $difficulty;
        $this->nonce = $nonce;
        $this->previousHash = $previousHash;
        $this->hash = self::hash($this);
    }

    public static function hash(BlockHeader $header): string
    {
        $data = $header->index
            . $header->createdAt->getTimestamp()
            . $header->previousHash
            . $header->merkleRoot
            . $header->difficulty
            . $header->nonce;
        return hash('sha256', $data);
    }

    public function isNextOf(BlockHeader $target): bool
    {
        return $this->index === $target->index + 1
            && $this->previousHash === $target->hash
            && $this->hash === self::hash($this);
    }

    public function isEqual(BlockHeader $target): bool
    {
        return $this->index === $target->index
            && $this->hash === $target->hash
            && $this->previousHash === $target->previousHash
            && $this->createdAt->getTimestamp() === $target->createdAt->getTimestamp()
            && $this->merkleRoot === $target->merkleRoot
            && $this->difficulty === $target->difficulty
            && $this->nonce === $target->nonce;
    }
}