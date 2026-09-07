<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load the Yii application service manually for this CLI test.
require __DIR__ . '/services/AiService.php';

use app\services\AiService;

try {
    $ai = new AiService();

    $summary = $ai->summarizeTicket(
        'Cannot login',
        'The customer reset their password but is still unable to log into their account.'
    );

    echo "AI SUMMARY:\n";
    echo $summary . "\n";

} catch (\Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
}