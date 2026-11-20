
/* =========================================
   PASSWORD VISIBILITY TOGGLE
========================================= */
function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (!input || !button) {
        return;
    }

    const showPassword = input.type === "password";

    input.type = showPassword ? "text" : "password";
    button.textContent = showPassword ? "Hide" : "Show";
    button.setAttribute(
        "aria-label",
        showPassword ? "Hide password" : "Show password"
    );
}


/* =========================================
   AI ASSISTANT CHAT
========================================= */
async function askAssistant(form) {
    const input = form.querySelector('input[name="question"]');
    const chatBox = document.getElementById("chat-messages");

    if (!input || !chatBox) {
        return false;
    }

    const question = input.value.trim();

    if (!question) {
        return false;
    }

    // Display the user's message.
    const userMessage = document.createElement("div");
    userMessage.className = "chat-msg user";
    userMessage.textContent = question;

    chatBox.appendChild(userMessage);

    input.value = "";

    // Display a loading message.
    const waitingMessage = document.createElement("div");
    waitingMessage.className = "chat-msg";
    waitingMessage.textContent = "Checking service information…";

    chatBox.appendChild(waitingMessage);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const csrfInput = form.querySelector('[name="csrf_token"]');

        if (!csrfInput) {
            throw new Error("Security token is missing.");
        }

        const formData = new FormData();

        formData.append("question", question);
        formData.append("csrf_token", csrfInput.value);

        const response = await fetch("ai_chat.php", {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        });

        if (!response.ok) {
            throw new Error("The assistant request failed.");
        }

        const result = await response.json();

        waitingMessage.textContent =
            result.answer || "I will forward this to the Manager.";

        if (result.escalated) {
            const notice = document.createElement("div");

            notice.className = "small";
            notice.textContent =
                "Your question was forwarded to the Manager.";

            chatBox.appendChild(notice);
        }
    } catch (error) {
        console.error("AI Assistant error:", error);

        waitingMessage.textContent =
            "Assistant is temporarily unavailable. Please contact the Manager.";
    }

    chatBox.scrollTop = chatBox.scrollHeight;

    return false;
}

/* =========================================
   MOBILE HAMBURGER NAVIGATION
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.querySelector(".sidebar");
    const toggle = document.getElementById("mobileMenuToggle");
    const menu = document.getElementById("mobileNavigation");

    if (!sidebar || !toggle || !menu) {
        return;
    }

    function isMobile() {
        return window.matchMedia("(max-width: 720px)").matches;
    }

    function openMenu() {

        sidebar.classList.add("drawer-open");

        toggle.setAttribute("aria-expanded", "true");
        toggle.setAttribute("aria-label", "Close navigation menu");

        const icon = toggle.querySelector(".hamburger-icon");

        if (icon) {
            icon.textContent = "✕";
        }
    }

    function closeMenu() {

        sidebar.classList.remove("drawer-open");

        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Open navigation menu");

        const icon = toggle.querySelector(".hamburger-icon");

        if (icon) {
            icon.textContent = "☰";
        }
    }

    // Toggle hamburger menu
    toggle.addEventListener("click", function (event) {

        event.stopPropagation();

        const isOpen =
            sidebar.classList.contains("drawer-open");

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }

    });

    // Close when a navigation link is selected
    menu.querySelectorAll("a").forEach(function (link) {

        link.addEventListener("click", function () {
            closeMenu();
        });

    });

    // Close when clicking outside the menu
    document.addEventListener("click", function (event) {

        if (
            sidebar.classList.contains("drawer-open") &&
            !sidebar.contains(event.target)
        ) {
            closeMenu();
        }

    });

    // Close with Escape
    document.addEventListener("keydown", function (event) {

        if (
            event.key === "Escape" &&
            sidebar.classList.contains("drawer-open")
        ) {
            closeMenu();
            toggle.focus();
        }

    });

    // Reset menu when switching to desktop
    window.addEventListener("resize", function () {

        if (!isMobile()) {
            closeMenu();
        }

    });

});
