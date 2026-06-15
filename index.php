<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claude Agent Caller</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; max-width: 900px; }
        h1 { margin-bottom: 0.5rem; }
        p { color: #444; }
        form { margin-top: 1.5rem; }
        textarea { width: 100%; min-height: 140px; padding: 0.75rem; font-size: 1rem; }
        button { margin-top: 1rem; padding: 0.7rem 1.1rem; font-size: 1rem; cursor: pointer; }
        .note { margin-top: 1rem; font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
    <h1>Claude Agent Caller</h1>
    <p>Send a prompt to Claude using your API key and agent settings from <code>.env</code>.</p>

    <form method="post" action="call_agent.php">
        <label for="prompt"><strong>Prompt</strong></label><br>
        <textarea id="prompt" name="prompt" placeholder="Type your prompt..." required></textarea><br>
        <button type="submit">Send to Claude</button>
    </form>

  
</body>
</html>
