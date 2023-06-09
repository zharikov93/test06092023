<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$fileContent = file_get_contents($argv[1]);
$rows = explode("\n", $fileContent);

$client = new Client();

$response = $client->get('https://api.apilayer.com/exchangerates_data/latest', [
    'headers' => [
        'Content-Type' => 'application/json',
        'apikey' => 'QoDh1DkvmwPAry1A5AE7uD83f6vRLeYd'
    ]
]);

$jsonResponse = json_decode($response->getBody(), true);

foreach ($rows as $row) {
    if (empty($row)) {
        break;
    }

    $parameters = explode(",", $row);
    $value = extractValuesFromParameters($parameters);

    $binResponse = $client->get('https://lookup.binlist.net/' . $value[0]);
    $binData = json_decode($binResponse->getBody());

    $isEu = isEu($binData->country->alpha2);

    $rate = $jsonResponse['rates'][$value[2]];

    $amntFixed = calculateAmount($value, $rate);

    $result = ceil($amntFixed * ($isEu == 'yes' ? 0.01 : 0.02) * 100) / 100;

    echo $result;
    echo "\n";
}

function extractValuesFromParameters($parameters)
{
    $value = [];
    foreach ($parameters as $param) {
        $p2 = explode(':', $param);
        $trimmedValue = str_replace(['"', '}'], '', $p2[1]);
        $value[] = trim($trimmedValue);
    }

    return $value;
}

function calculateAmount($value, $rate)
{
    if ($value[2] == 'EUR' || $rate == 0) {
        return $value[1];
    }

    if ($value[2] != 'EUR' || $rate > 0) {
        return $value[1] / $rate;
    }
}

function isEu($c)
{
    $euCountries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES',
        'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU',
        'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    return in_array($c, $euCountries) ? 'yes' : 'no';
}