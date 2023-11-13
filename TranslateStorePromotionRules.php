<?php

include(__DIR__ . '/vendor/autoload.php');

$short_options = 's::t::';
$long_options = ['store-hash::', 'token::'];
$options = getopt($short_options, $long_options);

$store_hash = '';
$token = '';

if (isset($options['s']) || isset($options['store-hash'])) {
    $store_hash = isset($options['s']) ? trim($options['s']) : trim($options['store-hash']);
}

if (isset($options['t']) || isset($options['token'])) {
    $token = isset($options['t']) ? trim($options['t']) : trim($options['token']);
}

if (empty($store_hash) || empty($token)) {
    throw new Exception('Missing arguments. Make sure you pass in the store hash and token.');
}

use App\BigCommerce;

$BigCommerce = new BigCommerce($store_hash, $token);
$getProducts = $BigCommerce->getProducts(1);
$getPromotions = $BigCommerce->getPromotions('Test');
// $getPromotions = $BigCommerce->getPromotions('Bogo Station and Dispensers');

$currentPage = 1;
$totalPages = 1;

if (isset($getProducts['response']['meta']['pagination']['total_pages'])) {
    $totalPages = $getProducts['response']['meta']['pagination']['total_pages'];
}

$productArray = [];

while ($currentPage <= $totalPages) {
    $currentPage++;

    if (isset($getProducts['response']['data'])) {
        foreach ($getProducts['response']['data'] as $product) {
            $productArray[$product['id']] = [
                'id' => $product['id'],
                'sku' => $product['sku'],
                'name' => $product['id'],
            ];
        }
    }

    $getProducts = $BigCommerce->getProducts($currentPage);
}

$fileOutput = __DIR__ . '/output/promotions-' . $store_hash . '.txt';

if (!file_exists($fileOutput)) {
    touch($fileOutput);
}

file_put_contents(
    $fileOutput,
    '',
    LOCK_EX
);

if ($getPromotions['success']) {
    foreach ($getPromotions['response']['data'][0]['rules'] as $inx => $rules) {
        file_put_contents(
            $fileOutput,
            '+--------+----------------+---------------+------------+-------+' . PHP_EOL .
            '| Rule # | Condition      | Action        | Apply Once | Stop  |' . PHP_EOL .
            '+--------+----------------+---------------+------------+-------+' . PHP_EOL .
            '| ' . 
            str_pad($inx + 1, 6, " ") . ' | ' . 
            str_pad(getConditionType($rules['conditions'] ?? []), 14, " ") . ' | ' .
            str_pad(getActionType($rules['action']), 13, " ") . ' | ' .
            str_pad(($rules['apply_once'] ? 'true' : 'false'), 10, " ") . ' | ' .
            str_pad(($rules['stop'] ? 'true' : 'false'), 4, " ") . ' |' . PHP_EOL .
            '+--------+----------------+---------------+------------+-------+' . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        // Start Conditions
        file_put_contents(
            $fileOutput,
            '#### CONDITIONS{Start}: ' . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        foreach ($rules['condition'] ?? [] as $condition => $data2) {
            file_put_contents(
                $fileOutput,
                "\t" . 'Bunch of Conditions' . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }

        file_put_contents(
            $fileOutput,
            '#### CONDITIONS{End}: ' . PHP_EOL . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        // End Conditions

        // Start Actions
        file_put_contents(
            $fileOutput,
            '#### ACTIONS{Start}: ' . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        foreach ($rules['action'] ?? [] as $actions) {
            file_put_contents(
                $fileOutput,
                "\t" . 'Bunch of Actions' . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }

        file_put_contents(
            $fileOutput,
            '#### ACTIONS{End}: ' . PHP_EOL . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        // End Actions
    }
}

function getActionType(array $action = []): string
{
    if (isset($action['gift_item'])) {
        return 'Buys Products';
    } else if (isset($action['cart_items'])) {
        return 'No Conditions';
    } else {
        return 'other';
    }
}

function getConditionType(array $action = []): string
{
    if (isset($action['cart'])) {
        return 'Cart Condition';
    } else {
        return 'n/a';
    }
}

print_r($getPromotions);
