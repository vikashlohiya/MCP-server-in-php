<?php



function anthropicHeaders(string $apiKey): array
{
    return [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
       "anthropic-version: 2023-06-01",
    "anthropic-beta: mcp-client-2025-04-04"
    ];
}


function sendClaudeRequest(string $apiUrl, string $apiKey, string $model, string $prompt): array
{
  
  $payload = [
    "model" => "claude-sonnet-4-6",
    "max_tokens" => 1024,
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ],
    "mcp_servers" => [
        [
            "type" => "url",
            "url" => "Your Server URL/mcp.php",
            "name" => "my-tools"
        ]
    ]
];

    $ch = curl_init($apiUrl);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Failed to initialize CURL.'];
    }

    $headers = anthropicHeaders($apiKey);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => 'CURL error: ' . $curlErr];
    }

    $json = json_decode($response, true);
    echo "<pre>";
    print_r($json);
    
    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'Invalid JSON response: ' . $response];
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $apiError = $json['error']['message'] ?? ('HTTP ' . $statusCode);
        return [
            'ok' => false,
            'error' => 'Claude API error: ' . $apiError,
            'model' => $model,
            'status' => $statusCode,
            'raw' => $response,
        ];
    }

    $text = '';
    if (isset($json['content']) && is_array($json['content'])) {
        foreach ($json['content'] as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text') {
                $text .= ($block['text'] ?? '');
            }
        }
    }

    return [
        'ok' => true,
        'text' => $text !== '' ? $text : json_encode($json, JSON_PRETTY_PRINT),
        'model' => $model,
    ];
}


$apiKey = 'YOUR API KEY';
$model =  'claude-sonnet-4-6';
$apiUrl = $env['API_URL'] ?? 'https://api.anthropic.com/v1/messages';
$prompt = trim($_POST['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    echo '<p>Prompt is required. <a href="index.php">Go back</a></p>';
    exit;
}

if ($apiKey === '') {
    http_response_code(500);
    echo '<p>Missing API_KEY in .env file. <a href="index.php">Go back</a></p>';
    exit;
}

$result = sendClaudeRequest($apiUrl, $apiKey, $model, $prompt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claude Response</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; max-width: 900px; }
        pre { white-space: pre-wrap; background: #f4f4f4; padding: 1rem; border-radius: 6px; }
        .error { color: #b00020; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Claude Response</h1>

    <?php if (!$result['ok']): ?>
        <p class="error"><?= htmlspecialchars((string) $result['error'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (isset($result['model'])): ?>
            <p><strong>Tried model:</strong> <?= htmlspecialchars((string) $result['model'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (isset($result['status'])): ?>
            <p><strong>HTTP status:</strong> <?= htmlspecialchars((string) $result['status'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (isset($result['raw'])): ?>
            <details>
                <summary>Raw API response</summary>
                <pre><?= htmlspecialchars((string) $result['raw'], ENT_QUOTES, 'UTF-8') ?></pre>
            </details>
        <?php endif; ?>
    <?php else: ?>
        <p><strong>Model:</strong> <?= htmlspecialchars((string) ($result['model'] ?? $model), ENT_QUOTES, 'UTF-8') ?></p>
        <pre><?= htmlspecialchars((string) $result['text'], ENT_QUOTES, 'UTF-8') ?></pre>
    <?php endif; ?>

    <p><a href="index.php">Send another prompt</a></p>
</body>
</html>


