
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

// Only Clients can access the AI Assistant.
$user = require_role(['Client']);

// Display the page header.
page_header(
    'AI assistant',
    'Answers are based only on Manager-approved service information'
);

?>

<div class="content-grid">

    <!-- AI Assistant chat section -->
    <section class="panel span-8">

        <h2>Ask about our services</h2>

        <p class="muted">
            Ask about services, common materials, or the project process.
            Project-specific pricing and uncertain questions are forwarded
            to the Manager.
        </p>

        <!-- Chat messages appear here -->
        <div
            class="chat-box"
            id="chat-messages"
            aria-live="polite"
            aria-label="AI Assistant conversation"
        >

            <div class="chat-msg">
                Hello! I can answer general questions using the service
                information maintained by the Manager. What would you
                like to know?
            </div>

        </div>

        <!-- Question submission form -->
        <form
            class="chat-form"
            onsubmit="return askAssistant(this)"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="text"
                name="question"
                placeholder="Ask about services, materials, or process…"
                aria-label="Enter your question"
                required
            >

            <button
                type="submit"
                class="btn primary"
            >
                Send
            </button>

        </form>

    </section>

    <!-- Example questions section -->
    <section class="panel span-4">

        <h2>Example questions</h2>

        <div class="notice">
            What services do you provide?
        </div>

        <div class="notice">
            What materials are commonly used?
        </div>

        <div class="notice">
            What is the project process?
        </div>

        <div class="notice">
            For a quotation, submit an inquiry with measurements and photos.
        </div>

    </section>

</div>

<!-- Load the JavaScript for the chat functionality -->
<script src="app.js"></script>

<?php

page_footer();

?>
