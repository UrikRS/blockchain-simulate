<?php

namespace Blockchain\DataStructures;

class MerkleTree
{
    private $elements = [];
    public $root;

    public function __construct(array $elements)
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
        $this->build();
    }

    public static function from(array $elements): MerkleTree
    {
        $tree = new self($elements);
        return $tree;
    }

    public function add(string $element): void
    {
        $this->elements[] = self::hash($element);
    }

    private static function hash(string $text): string
    {
        return hash('sha256', $text);
    }

    public function build()
    {
        $currentLevel = $this->elements;
        while (count($currentLevel) > 1) {
            $currentLevel = self::levelUp($currentLevel);
        }
        $this->root = array_shift($currentLevel);
        return $this->root;
    }

    private static function levelUp(array $currentLevel): array
    {
        $nextLevel = [];
        for ($i = 0; $i < count($currentLevel); $i += 2) {
            $left = $currentLevel[$i];
            $right = $currentLevel[$i + 1] ?? $left; // Duplicate if odd
            $nextLevel[] = self::hash($left . $right);
        }
        return $nextLevel;
    }

    public static function getProofs(array $transactions, string $target): array
    {
        $hashes = array_map(function ($transaction) {
            return hash('sha256', $transaction);
        }, $transactions);
        $index = array_search(hash('sha256', $target), $hashes);
        if ($index === false) {
            return [];
        }

        $proofs = [];
        while (count($hashes) > 1) {
            if (count($hashes) % 2 !== 0) {
                $hashes[] = end($hashes);
            }

            $nextLevel = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                $left = $hashes[$i];
                $right = $hashes[$i + 1];
                $nextLevel[] = hash('sha256', $left . $right);
                if ($i === $index || $i + 1 === $index) {
                    $proofs[] = ($i === $index) ? [$right, true] : [$left, false];
                    $index = count($nextLevel) - 1;
                }
            }
            $hashes = $nextLevel;
        }
        return $proofs;
    }

    public static function verify(string $transaction, string $merkleRoot, array $proofs): bool
    {
        $hash = hash('sha256', $transaction);
        foreach ($proofs as [$proof, $isRight]) {
            $concated = $isRight ? $hash . $proof : $proof . $hash;
            $hash = hash('sha256', $concated);
        }
        return $hash === $merkleRoot;
    }
}
