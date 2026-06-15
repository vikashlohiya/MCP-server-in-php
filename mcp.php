
<?php
/**
 * Remote MCP Server over HTTP (Streamable HTTP transport)
 * Deploy this file at e.g. https://yourdomain.com/mcp.php
 * Add in Claude.ai -> Settings -> Connectors -> Add custom connector -> URL: https://yourdomain.com/mcp.php
 *
 * Exposes 2 tools: find_order, calculate
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------
// 1. TOOL DEFINITIONS
// ---------------------------------------------------------
$TOOLS = [
    [
        "name" => "find_order",
        "description" => "Find an order by its order ID and return status, items, total, customer.",
        "inputSchema" => [
            "type" => "object",
            "properties" => [
                "order_id" => [
                    "type" => "string",
                    "description" => "Order ID, e.g. ORD1001"
                ]
            ],
            "required" => ["order_id"]
        ]
    ],
    [
        "name" => "calculate",
        "description" => "Perform a basic arithmetic operation (add, subtract, multiply, divide) on two numbers.",
        "inputSchema" => [
            "type" => "object",
            "properties" => [
                "operation" => [
                    "type" => "string",
                    "enum" => ["add", "subtract", "multiply", "divide"],
                    "description" => "The operation to perform"
                ],
                "a" => ["type" => "number", "description" => "First number"],
                "b" => ["type" => "number", "description" => "Second number"]
            ],
            "required" => ["operation", "a", "b"]
        ]
    ]
];

// ---------------------------------------------------------
// 2. TOOL IMPLEMENTATIONS
// ---------------------------------------------------------
function find_order(array $args): array
{
    // Replace with real DB query
    $orders = [
        "ORD1001" => [
            "order_id" => "ORD1001",
            "customer" => "Vikash Kumar",
            "status"   => "Shipped",
            "items"    => ["Mouse", "Keyboard"],
            "total"    => 1499.00
        ],
        "ORD1002" => [
            "order_id" => "ORD1002",
            "customer" => "Vinay Kumar",
            "status"   => "Pending",
            "items"    => ["Monitor"],
            "total"    => 8999.00
        ],
    ];

    $orderId = $args['order_id'] ?? '';

    if (!isset($orders[$orderId])) {
        $text = "Order '$orderId' not found.";
    } else {
        $o = $orders[$orderId];
        $items = implode(", ", $o['items']);
        $text = "Order {$o['order_id']} | Customer: {$o['customer']} | Status: {$o['status']} | Items: $items | Total: ?{$o['total']}";
    }

    return [
        "content" => [
            ["type" => "text", "text" => $text]
        ]
    ];
}

function calculate(array $args): array
{
    $op = $args['operation'] ?? '';
    $a = (float)($args['a'] ?? 0);
    $b = (float)($args['b'] ?? 0);

    switch ($op) {
        case 'add':      $result = $a + $b; break;
        case 'subtract': $result = $a - $b; break;
        case 'multiply': $result = $a * $b; break;
        case 'divide':
            if ($b == 0) {
                return ["content" => [["type" => "text", "text" => "Error: Division by zero"]]];
            }
            $result = $a / $b;
            break;
        default:
            return ["content" => [["type" => "text", "text" => "Error: Unknown operation '$op'"]]];
    }

    return [
        "content" => [
            ["type" => "text", "text" => "$a $op $b = $result"]
        ]
    ];
}

// ---------------------------------------------------------
// 3. JSON-RPC REQUEST HANDLER
// ---------------------------------------------------------
function handleRequest(array $req): ?array
{
    global $TOOLS;

    $id     = $req['id'] ?? null;
    $method = $req['method'] ?? '';
    $params = $req['params'] ?? [];

    switch ($method) {

        case 'initialize':
            return [
                "jsonrpc" => "2.0",
                "id" => $id,
                "result" => [
                    "protocolVersion" => "2024-11-05",
                    "serverInfo" => ["name" => "php-remote-mcp", "version" => "1.0.0"],
                    "capabilities" => ["tools" => new stdClass()]
                ]
            ];

        case 'tools/list':
            return ["jsonrpc" => "2.0", "id" => $id, "result" => ["tools" => $TOOLS]];

        case 'tools/call':
            $name = $params['name'] ?? '';
            $args = $params['arguments'] ?? [];

            switch ($name) {
                case 'find_order':
                    $result = find_order($args);
                    break;
                case 'calculate':
                    $result = calculate($args);
                    break;
                default:
                    return ["jsonrpc" => "2.0", "id" => $id, "error" => ["code" => -32601, "message" => "Unknown tool: $name"]];
            }

            return ["jsonrpc" => "2.0", "id" => $id, "result" => $result];

        case 'notifications/initialized':
            return null;

        default:
            return ["jsonrpc" => "2.0", "id" => $id, "error" => ["code" => -32601, "message" => "Method not found: $method"]];
    }
}

// ---------------------------------------------------------
// 4. ENTRY POINT — read JSON-RPC request from POST body
// ---------------------------------------------------------
$input = file_get_contents("php://input");
$req = json_decode($input, true);

if ($req === null) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

$response = handleRequest($req);

if ($response !== null) {
    echo json_encode($response);
} else {
    // notification - no response body
    http_response_code(202);
}

