<?php

namespace Blockchain\HashDifficulties;

class TargetBased implements HashDifficulty
{
    public function match(string $hash, int $difficulty): bool
    {
        if ($difficulty === 0) {
            return true;
        }
        $target = str_repeat('f', $difficulty);
        return hexdec($hash) >= hexdec($target);
    }
}