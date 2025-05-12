<?php
require 'config.php';

$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

function sendMessage($chat_id, $message, $keyboard = null) {
    $payload = [
        'chat_id' => $chat_id,
        'text' => $message
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
    }
    file_get_contents(API_URL . "sendMessage?" . http_build_query($payload));
}

function sendPhoto($chat_id, $photo_url, $caption) {
    file_get_contents(API_URL . "sendPhoto?chat_id=$chat_id&photo=$photo_url&caption=" . urlencode($caption));
}

function getCharacterImage($characterName) {
    $query = '
        query ($search: String) {
          Character(search: $search) {
            image {
              large
            }
          }
        }
    ';
    $variables = ['search' => $characterName];

    $data = json_encode(['query' => $query, 'variables' => $variables]);

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $data
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents('https://graphql.anilist.co', false, $context);
    $responseData = json_decode($result, true);

    return $responseData['data']['Character']['image']['large'] ?? null;
}

function fetchQuote($anime = null) {
    $url = $anime
        ? "https://api.animechan.io/v1/quotes/anime?title=" . urlencode($anime)
        : "https://api.animechan.io/v1/quotes/random";

    $response = @file_get_contents($url);
    if (!$response) return null;

    $data = json_decode($response, true);

    if (isset($data['quote'])) return [$data]; // Single quote format
    if (is_array($data) && count($data)) return $data; // Multiple quotes

    return null;
}

// Command handling
if (strpos($text, "/start") === 0) {
    $msg = "Welcome to Anime Quote Bot!\n\nCommands:\n/quote - Random quote\n/quote <anime name> - Quote from that anime";
    $keyboard = [
        [['text' => 'Get Random Quote', 'callback_data' => 'get_random']]
    ];
    sendMessage($chat_id, $msg, $keyboard);

} elseif (strpos($text, "/quote") === 0) {
    $parts = explode(" ", $text, 2);
    $anime = $parts[1] ?? null;

    $quotes = fetchQuote($anime);
    if (!$quotes) {
        sendMessage($chat_id, "No quote found. Try again with another anime.");
        return;
    }

    $quoteData = $quotes[array_rand($quotes)];
    $quote = $quoteData['quote'];
    $character = $quoteData['character'];
    $animeName = $quoteData['anime'];

    $img_url = getCharacterImage($character);
    if (!$img_url) {
        $query = urlencode($character . " " . $animeName . " anime");
        $img_url = "https://source.unsplash.com/600x800/?" . $query;
    }

    $caption = "\"$quote\"\n\n– $character from $animeName";
    sendPhoto($chat_id, $img_url, $caption);

} elseif (isset($update["callback_query"])) {
    $data = $update["callback_query"]["data"];
    $callback_chat_id = $update["callback_query"]["message"]["chat"]["id"];
    if ($data == "get_random") {
        $quotes = fetchQuote();
        if (!$quotes) {
            sendMessage($callback_chat_id, "Could not get a quote right now.");
            return;
        }

        $quoteData = $quotes[0];
        $quote = $quoteData['quote'];
        $character = $quoteData['character'];
        $animeName = $quoteData['anime'];

        $img_url = getCharacterImage($character);
        if (!$img_url) {
            $query = urlencode($character . " " . $animeName . " anime");
            $img_url = "https://source.unsplash.com/600x800/?" . $query;
        }

        $caption = "\"$quote\"\n\n– $character from $animeName";
        sendPhoto($callback_chat_id, $img_url, $caption);
    }
} else {
    sendMessage($chat_id, "Type /quote or /quote Naruto to get an anime quote.");
}
