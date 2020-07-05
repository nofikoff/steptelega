<?php
require_once("vendor/autoload.php");

use Viber\Client;

$apiKey = '49a207034aa7d6f5-f8fea5c369e7e3c-8ef11c9102ebd422'; // from "Edit Details" page
$webhookUrl = 'https://steptelega.protection.kiev.ua/viber/'; // for exmaple https://my.com/bot.php

try {
    $client = new Client([ 'token' => $apiKey ]);
    $result = $client->setWebhook($webhookUrl);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: ". $e->getError() ."\n";
}