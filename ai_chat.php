
<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Send a JSON response and stop execution.
 */
function send_json(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Only logged-in Clients can use this endpoint.
$user = require_role(['Client']);

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    send_json([
        'answer' => 'Method not allowed.'
    ], 405);
}

// Validate the CSRF token.
verify_csrf();

// Get and validate the customer's question.
$question = trim($_POST['question'] ?? '');

if ($question === '') {
    send_json([
        'answer' => 'Please enter a question.'
    ]);
}

// Ensure the required multibyte string functions are available.
if (
    !function_exists('mb_strtolower')
    || !function_exists('mb_strlen')
    || !function_exists('mb_strpos')
    || !function_exists('mb_strimwidth')
) {
    error_log(
        'AI Assistant requires the PHP mbstring extension.'
    );

    send_json([
        'answer' => 'The assistant is temporarily unavailable. Please contact the Manager.'
    ], 500);
}

try {
    /**
     * Retrieve approved knowledge entries.
     */
    $stmt = $pdo->query(
        'SELECT title, content
         FROM ai_knowledge
         WHERE enabled = 1
         ORDER BY updated_at DESC'
    );

    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Split the question into searchable words.
     */
    $tokens = preg_split(
        '/\W+/u',
        mb_strtolower($question),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if ($tokens === false) {
        $tokens = [];
    }

    /**
     * Find the knowledge entry with the most matching words.
     */
    $bestEntry = null;
    $bestScore = 0;

    foreach ($entries as $entry) {
        $title = $entry['title'] ?? '';
        $content = $entry['content'] ?? '';

        $searchableText = mb_strtolower(
            $title . ' ' . $content
        );

        $score = 0;

        foreach ($tokens as $token) {
            // Ignore very short words.
            if (mb_strlen($token) <= 2) {
                continue;
            }

            if (mb_strpos($searchableText, $token) !== false) {
                $score++;
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestEntry = $entry;
        }
    }

    /**
     * Questions involving pricing, quotations, estimates,
     * specific projects, or timelines require Manager review.
     */
    $requiresManager = preg_match(
        '/price|cost|quote|quotation|how much|estimate|specific|my project|timeline|when can/i',
        $question
    );

    $answer = '';
    $escalated = false;

    /**
     * Escalate questions that cannot be reliably answered.
     */
    if (
        $requiresManager === 1
        || !$bestEntry
        || $bestScore < 1
    ) {
        $answer =
            'I can’t reliably answer that from the approved service information. '
            . 'I’ve forwarded your question to the Manager, who can review '
            . 'your project details and respond.';

        $pdo->beginTransaction();

        try {
            // Save the question for Manager review.
            $stmt = $pdo->prepare(
                "INSERT INTO ai_escalations
                    (client_id, question, status)
                 VALUES (?, ?, 'open')"
            );

            $stmt->execute([
                $user['id'],
                $question
            ]);

            $escalationId = (int) $pdo->lastInsertId();

            // Notify active Managers.
            $stmt = $pdo->query(
                "SELECT id
                 FROM users
                 WHERE role = 'Manager'
                   AND is_active = 1"
            );

            $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($managers as $manager) {
                notify_user(
                    $pdo,
                    (int) $manager['id'],
                    'AI question needs review',
                    'A client asked: '
                    . mb_strimwidth(
                        $question,
                        0,
                        160,
                        '…'
                    )
                );
            }

            // Record the escalation.
            audit(
                $pdo,
                'AI question escalated',
                'ai_escalation',
                $escalationId
            );

            $pdo->commit();

            $escalated = true;

        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

    } else {
        /**
         * Return the best matching approved knowledge entry.
         */
        $answer =
            ($bestEntry['title'] ?? 'Service information')
            . ': '
            . ($bestEntry['content'] ?? '')
            . ' If you need advice for a specific site, measurement, '
            . 'price, or schedule, I can forward that question to the Manager.';
    }

    // Return the response to the JavaScript chat interface.
    send_json([
        'answer' => $answer,
        'escalated' => $escalated
    ]);

} catch (Throwable $exception) {
    // Keep technical details in server logs, not customer responses.
    error_log(
        'AI Assistant error: ' . $exception->getMessage()
    );

    send_json([
        'answer' => 'The assistant is temporarily unavailable. Please contact the Manager.',
        'escalated' => false
    ], 500);
}
