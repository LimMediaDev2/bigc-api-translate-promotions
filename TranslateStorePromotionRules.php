<?php

include(__DIR__ . '/vendor/autoload.php');

$short_options = 's::t::p::';
$long_options = ['store-hash::', 'token::', 'promotion::'];
$options = getopt($short_options, $long_options);

$store_hash = '';
$token = '';
$promotion_name = '';

if (isset($options['s']) || isset($options['store-hash'])) {
    $store_hash = isset($options['s']) ? trim($options['s']) : trim($options['store-hash']);
}

if (isset($options['t']) || isset($options['token'])) {
    $token = isset($options['t']) ? trim($options['t']) : trim($options['token']);
}

if (isset($options['p']) || isset($options['promotion'])) {
    $promotion_name = isset($options['p']) ? trim($options['p']) : trim($options['promotion']);
}

if (empty($store_hash) || empty($token) || empty($promotion_name)) {
    echo date('Y-m-d\TH:i:s') . '::End' . PHP_EOL;
    throw new Exception('Missing arguments. Make sure you pass in the `store hash`, `token` and the `promotion name`.');
}

use App\BigCommerce;

$currentPage = 1;
$totalPages = 1;

$BigCommerce = new BigCommerce($store_hash, $token);
$getProducts = $BigCommerce->getProducts($currentPage, 250);
$getPromotions = $BigCommerce->getPromotions($promotion_name);

if (!isset($getPromotions['success']) || $getPromotions['success'] === false) {
    echo date('Y-m-d\TH:i:s') . '::End' . PHP_EOL;
    throw new Exception('Error getting promotions:' . print_r($getPromotions, true));
}

if (isset($getProducts['response']['meta']['pagination']['total_pages'])) {
    $totalPages = $getProducts['response']['meta']['pagination']['total_pages'];
}

$productArray = [];

while ($currentPage <= $totalPages) {
    $currentPage++;

    if (isset($getProducts['response']['data'])) {
        foreach ($getProducts['response']['data'] as $product) {
            $getProductModifiers = $BigCommerce->getProductModifiers($product['id']);
            $getProductVariants = $BigCommerce->getProductVariants($product['id']);
            $productArray[$product['id']] = [
                'id' => $product['id'],
                'sku' => $product['sku'],
                'name' => $product['name'],
                'modifiers' => $getProductModifiers['response']['data'] ?? [],
                'variants' => $getProductVariants['response']['data'] ?? []
            ];
        }
    }

    $getProducts = $BigCommerce->getProducts($currentPage);
}

$fileOutput = __DIR__ . '/output/promotions-' . $store_hash . '-analysis.csv';

if (!file_exists($fileOutput)) {
    touch($fileOutput);
}

file_put_contents(
    $fileOutput,
    'ID;SKU;Name;Modifiers;Variants;Rule;Condition;Promotion' . PHP_EOL,
    LOCK_EX
);

if ($getPromotions['success']) {
    foreach ($getPromotions['response']['data'][0]['rules'] as $inx => $rules) {

        $product_id = $rules['action']['gift_item']['variant_id'] ?? $rules['action']['gift_item']['product_id'] ?? null;
        $rule = getActionType($rules['action']);
        $product = $productArray[$product_id] ?? [];

        file_put_contents(
            $fileOutput,
            ($product_id ?? '"null"') . ';' .
            '"' . addslashes($product['sku'] ?? 'null') . '";' .
            '"' . addslashes($product['name'] ?? 'null') . '";' .
            (isset($product['modifiers']) && sizeof($product['modifiers']) > 0 ? '"Yes"' : '"No"') . ';' .
            (isset($product['variants']) && sizeof($product['variants']) > 0 ? '"Yes"' : '"No"') . ';' .
            '"' . addslashes($rule) . '";' .
            '"' . addslashes(json_encode($rules['action'] ?? [])) . '";' .
            '"' . addslashes(json_encode($rules['condition'] ?? [])) . '"' . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        /*$rewardTitle = getRewardTitle($rules);
        $rewardTitleLength = strlen($rewardTitle);

        $r_dashes = '';
        $r_length = 0;
        $r_length_title = 0;
        $r_spaces = '';
        $r_spaces_title = '';

        if ($rewardTitleLength > 6) {
            $r_length = $rewardTitleLength - 6;
            $r_length_title = 0;
        } else {
            $r_length = 0;
            $r_length_title = 6 - $rewardTitleLength;
        }

        for ($i = 0; $i < $r_length; $i++) {
            $r_dashes .= '-';
            $r_spaces .= ' ';
        }

        for ($i = 0; $i < $r_length_title; $i++) {
            $r_spaces_title .= ' ';
        }

        file_put_contents(
            $fileOutput,
            'r_length->' . $r_length . PHP_EOL .
            'r_length_title->' . $r_length_title . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        file_put_contents(
            $fileOutput,
            '+--------+----------------+--------' . $r_dashes . '+------------+-------+' . PHP_EOL .
            '| Rule # | Condition      | Reward ' . $r_spaces . '| Apply Once | Stop  |' . PHP_EOL .
            '+--------+----------------+--------' . $r_dashes . '+------------+-------+' . PHP_EOL .
            '| ' . 
            str_pad($inx + 1, 6, " ") . ' | ' . 
            str_pad(getConditionType($rules['conditions'] ?? []), 14, " ") . ' | ' .
            $rewardTitle . $r_spaces_title . ' | ' .
            str_pad(($rules['apply_once'] ? 'true' : 'false'), 10, " ") . ' | ' .
            str_pad(($rules['stop'] ? 'true' : 'false'), 4, " ") . ' |' . PHP_EOL .
            '+--------+----------------+--------' . $r_dashes . '+------------+-------+' . PHP_EOL,
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
        // End Actions*/
    }
}

function getRewardTitle($rules = [])
{
    if (isset($rules['action']['fixed_price_set'])) {
        return 'Fixed price for # of products';
    } else {
        return 'n/a';
    }
}

function getActionType(array $action = []): string
{
    if (isset($action['gift_item'])) {
        return 'Buy 1, Get 1 Free';
    } else if (isset($action['cart_items'])) {
        return 'No Conditions';
    } else if (isset($action['cart_value'])) {
        return 'Discount on Subtotal';
    } else if (isset($action['shipping'])) {
        return 'Free Shipping';
    } else if (isset($action['discount'])) {
        return 'Discount on products';
    } else {
        return 'Other';
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

// print_r($getPromotions);