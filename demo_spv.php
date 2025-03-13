<?php

require __DIR__ . '/vendor/autoload.php';

use Blockchain\DataStructures\Blockchain;
use Blockchain\HashDifficulties\ZeroPrefix;
use Blockchain\P2pServer\Miner;

$blockchain = new Blockchain();
$hashDifficulty = new ZeroPrefix();

function randomTransaction()
{
    $users = ['Alice', 'Bob', 'Charlie', 'Dave', 'Eve'];
    $sender = $users[array_rand($users)];
    $receivers = array_diff($users, [$sender]);
    $receiver = $receivers[array_rand($receivers)];
    $amount = rand(1, 10);
    return "$sender pays $receiver $amount";
}

$transactions = [];
foreach (range(1, 10) as $i) {
    $transaction = randomTransaction();
    $blockchain->addTransaction($transaction);
    echo "[+] New Transaction: $transaction\n";
    $transactions[] = $transaction;
}

$miner = new Miner($blockchain, $hashDifficulty);

echo "\n[*] Mining a new block...\n";
$block = $miner->mine();
if (!empty($block)) {
    echo "[✓] Block mined! Hash: {$block->getHash()}\n";
}

$existed = $transactions[array_rand($transactions)];
$notExisted = '啊吧啊吧啊吧啊吧';

echo "\n[*] Checking if transaction '$existed' exists...\n";
if ($miner->blockchain->verify($existed, $block->getMerkleRoot())) {
    echo "[✓] Transaction '$existed' exists!\n";
} else {
    echo "[!] Transaction '$existed' does not exist!\n";
}

echo "\n[*] Checking if transaction '$notExisted' exists...\n";
if ($miner->blockchain->verify($notExisted, $block->getMerkleRoot())) {
    echo "[✓] Transaction '$notExisted' exists!\n";
} else {
    echo "[!] Transaction '$notExisted' does not exist!\n";
}