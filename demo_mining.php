<?php

require __DIR__ . '/vendor/autoload.php';

use Blockchain\DataStructures\Blockchain;
use Blockchain\HashDifficulties\ZeroPrefix;
use Blockchain\P2pServer\Miner;

// =========================
// AUTOMATED MINING LOOP
// =========================
$blockchain = new Blockchain();

function randomTransaction()
{
    $users = ['Alice', 'Bob', 'Charlie', 'Dave', 'Eve'];
    $sender = $users[array_rand($users)];
    $receivers = array_diff($users, [$sender]);
    $receiver = $receivers[array_rand($receivers)];
    $amount = rand(1, 10);
    return "$sender pays $receiver $amount";
}

while (true) {
    if (rand(1, 5) > 3) { // 40% chance to create a transaction
        $transaction = randomTransaction();
        $blockchain->addTransaction($transaction);
        echo "[+] New Transaction: $transaction\n";
    }

    if (count($blockchain->getPendingTransactions()) === 10) {
        echo "[*] Mining a new block...\n";
        $block = Miner::mine($blockchain, new ZeroPrefix);
        if (!empty($block)) {
            $blockchain->add($block);
            echo "[✓] Block mined! Hash: {$block->getHash()}\n";
        }
    }
    sleep(1);
}