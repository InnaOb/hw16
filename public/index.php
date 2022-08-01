<?php

require_once '../vendor/autoload.php';

$token = '5486727811:AAGmeV3qXpskG2WKFORL4O6Rk1RjuNNvWmo';
$getUpdatesUri = sprintf('https://api.telegram.org/bot%s/getUpdates', $token);
$sendMessageUri = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);
$exchangeRate = json_decode(file_get_contents('https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5'), true);
$currencies = array_column($exchangeRate, 'ccy');

$requestParameters = [
    'offset' => null
];

$exchangeInfo = [];

foreach ($exchangeRate as $item) {
    $exchangeInfo[] = [
        $item['ccy'] => [$item['sale'], $item['base_ccy']],
    ];
}

$exchangeInfo = array_merge(...$exchangeInfo);


function getResponse(int $chatId, string $text, int $messageId): array
{
    return [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_to_message_id' => $messageId,
    ];
}

while (true) {

    $data = file_get_contents($getUpdatesUri . '?' . http_build_query($requestParameters));
    $response = json_decode($data, true);

    foreach ($response['result'] as $update) {
        if ($requestParameters['offset'] == $update['update_id']) {
            continue;
        }

        $requestParameters['offset'] = $update['update_id'];
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? null;
        $messageId = $update['message']['message_id'];

        if ($text == '/start') {
            $result = getResponse($chatId, 'Hello, I am currency bot and i can help you to convert ' . implode(', ', $currencies) . '. to UAH. Please enter the amount and currency.', $messageId);

            file_get_contents($sendMessageUri . '?' . http_build_query($result));
            continue;
        }

        $message = explode(' ', mb_strtoupper($text));

        if (!is_numeric($message[0])) {
            $result = getResponse($chatId, 'Please enter the amount and currency', $messageId);

            file_get_contents($sendMessageUri . '?' . http_build_query($result));
            continue;
        }

        if (!isset($message[1])) {

            $result = getResponse($chatId, 'Please choose the currency from the list', $messageId);

            file_get_contents($sendMessageUri . '?' . http_build_query($result));
            continue;
        }

        if (!in_array($message[1], $currencies)) {

            $result = getResponse($chatId, 'You can choose only such currencies:' . implode(', ', $currencies), $messageId);

            file_get_contents($sendMessageUri . '?' . http_build_query($result));
            continue;
        }

        $result = getResponse($chatId, $message[0] * $exchangeInfo[$message[1]][0] . ' ' . $exchangeInfo[$message[1]][1], $messageId);
        file_get_contents($sendMessageUri . '?' . http_build_query($result));

        $requestParameters['offset'] = $update['update_id'] + 1;

    }

    sleep(1);
}
