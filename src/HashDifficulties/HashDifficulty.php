<?php

namespace Blockchain\HashDifficulties;

interface HashDifficulty
{
    public function match(string $hash, int $difficulty): bool;
}
