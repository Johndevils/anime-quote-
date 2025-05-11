<?php
require 'config.php';

// Get update from Telegram
$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update["message"]["chat"]["id"];
$text = strtolower($update["message"]["text"]);

// Send a message to user
function sendMessage($chat_id, $message) {
    file_get_contents(API_URL . "sendMessage?chat_id=$chat_id&text=" . urlencode($message));
}

// Send a photo with caption
function sendPhoto($chat_id, $photo_url, $caption) {
    file_get_contents(API_URL . "sendPhoto?chat_id=$chat_id&photo=$photo_url&caption=" . urlencode($caption));
}

// Get character image from AniList
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

    $data = json_encode([
        'query' => $query,
        'variables' => $variables
    ]);

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $data
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents('https://graphql.anilist.co', false, $context);
    $responseData = json_decode($result, true);

    return $responseData['data']['Character']['image']['large'] ?? null;
}

// Handle commands
if ($text == "/start") {
    $msg = "Welcome to Anime Quote Bot!\n\nCommands:\n/quote - Get a random anime quote with character image";
    sendMessage($chat_id, $msg);

} elseif ($text == "/quote") {
    // Fetch random anime quote
    $response = file_get_contents("https://animechan.vercel.app/api/random");
    $data = json_decode($response, true);

    if (isset($data['quote'])) {
        $quote = $data['quote'];
        $character = $data['character'];
        $anime = $data['anime'];

        // Get character image
        $img_url = getCharacterImage($character);
        if (!$img_url) {
            // Fallback to Unsplash
            $query = urlencode($character . " " . $anime . " anime");
            $img_url = "https://source.unsplash.com/600x800/?" . $query;
        }

        // Send photo with quote
        $caption = "\"$quote\"\n\n– $character from $anime";
        sendPhoto($chat_id, $img_url, $caption);
    } else {
        sendMessage($chat_id, "Sorry, could not fetch quote. Try again.");
    }

} else {
    sendMessage($chat_id, "Type /quote to get a random anime quote with image.");
}
