<?php

namespace Blockchain\HashDifficulties;

final class ZeroPrefix implements HashDifficulty
{
    public function match(string $hash, int $difficulty): bool
    {
        if ($difficulty === 0) {
            return true;
        }
        $count = 0;
        foreach (str_split($hash) as $digit) {
            if ($digit === 0) {
                $count += 4;
            } else {
                $decimal = hexdec($digit);
                $count += 3 - (int) log($decimal, 2);
                break;
            }
            if ($count >= $difficulty) {
                return true;
            }
        }
        return $count >= $difficulty;
    }
}