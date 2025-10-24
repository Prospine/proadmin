// ==========================================================
// NEW & CORRECTED CHAT.JS
// ==========================================================

// ---
// 1. Global Variables & DOM Selectors
// ---
let chatModal,
    userListContainer,
    userSearchInput,
    chatHeaderName,       // Changed from chatHeader
    chatMessagesContainer,
    messageInput,
    sendButton,
    refreshButton,        // This is the user-list refresh
    chatLoader,
    chatMain,
    chatWelcomeMain;

let activePartner = {
    id: null,
    username: null
};

// This is defined in your <script> tag in dashboard.php
// const currentUserId = ...; 

// ---
// 2. Basic Show/Hide Functions
// ---
function openInbox() {
    const chatContainer = chatModal.querySelector('.chat-container');
    chatModal.style.display = 'block';
    chatContainer.classList.remove('animate-fade-out', 'animate-slide-out-bottom-5');
    chatContainer.classList.add('animate-fade-in', 'animate-slide-in-bottom-5');
    fetchBranchUsers(); // Fetch users when the inbox is opened
}

function closeInbox() {
    const chatContainer = chatModal.querySelector('.chat-container');
    chatContainer.classList.remove('animate-fade-in', 'animate-slide-in-bottom-5');
    chatContainer.classList.add('animate-fade-out', 'animate-slide-out-bottom-5');
    setTimeout(() => {
        if (chatModal) {
            chatModal.style.display = 'none';
        }
        if (chatMain && chatWelcomeMain) {
            chatMain.classList.add('hidden');
            chatMain.classList.remove('flex'); // Make sure it's not flex
            chatWelcomeMain.classList.remove('hidden');
            chatWelcomeMain.classList.add('flex');
        }
    }, 300); // Match animation duration
}

// ---
// 3. Fetch and Render the User List
// ---
async function fetchBranchUsers() {
    setLoading(true);
    try {
        // CORRECTED: Using your API endpoint
        const response = await fetch('../api/fetch_branch_users.php');
        if (!response.ok) throw new Error('Network response was not ok.');

        const data = await response.json();

        // CORRECTED: Using your data structure
        if (data.success && data.users) {
            renderUserList(data.users);
        } else {
            userListContainer.innerHTML = `<div class="chat-loader">${data.message || 'Failed to load users.'}</div>`;
        }
    } catch (error) {
        console.error("Fetch error:", error);
        userListContainer.innerHTML = `<div class="chat-loader">Error loading users.</div>`;
    } finally {
        setLoading(false);
    }
}

/**
 * Renders the list of users with Tailwind classes
 */
function renderUserList(users) {
    userListContainer.innerHTML = '';
    if (users.length === 0) {
        userListContainer.innerHTML = `<div class="chat-loader">No other users in this branch.</div>`;
        return;
    }

    users.forEach(user => {
        // CORRECTED: Using user.username and user.role
        const userInitial = user.username.charAt(0).toUpperCase();

        const userElement = document.createElement('div');
        // ADDED: Tailwind classes for the user item
        userElement.className = 'flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors chat-user-item';
        userElement.dataset.userId = user.id;
        userElement.dataset.username = user.username;

        // ADDED: Tailwind-styled HTML for the avatar and info
        userElement.innerHTML = `
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-800 flex items-center justify-center font-semibold text-teal-700 dark:text-teal-200">
                ${userInitial}
            </div>
            <div class="ml-3 overflow-hidden">
                <div class="font-semibold text-sm text-gray-800 dark:text-gray-100 truncate">${user.username}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">${user.role}</div>
            </div>
        `;

        userElement.addEventListener('click', () => selectUser(userElement));
        userListContainer.appendChild(userElement);
    });
}

// ---
// 4. Core Chat Functions
// ---

function selectUser(selectedElement) {
    // 1. Highlight selected user
    const allUsers = userListContainer.querySelectorAll('.chat-user-item');
    allUsers.forEach(u => u.classList.remove('bg-gray-100', 'dark:bg-gray-700')); // Use Tailwind's 'active' style
    selectedElement.classList.add('bg-gray-100', 'dark:bg-gray-700');

    // 2. Set active partner
    activePartner.id = selectedElement.dataset.userId;
    activePartner.username = selectedElement.dataset.username;

    // 3. Switch from welcome screen to chat screen
    chatWelcomeMain.classList.add('hidden');
    chatWelcomeMain.classList.remove('flex');
    chatMain.classList.remove('hidden');
    chatMain.classList.add('flex'); // Make it visible as a flex column

    // 4. Update Header
    // CORRECTED: This now targets the <h2> tag inside the header
    chatHeaderName.textContent = `Chat with ${activePartner.username}`;

    // 5. Enable inputs
    messageInput.disabled = false;
    sendButton.disabled = false;
    messageInput.focus();

    // 6. Fetch messages
    chatMessagesContainer.innerHTML = `<div class="chat-loader text-center p-4 text-sm text-gray-500">Loading messages...</div>`;
    fetchMessages(activePartner.id);
}

async function fetchMessages(partnerId) {
    if (!partnerId) return;

    try {
        // CORRECTED: Using your API endpoint
        const response = await fetch(`../api/chat_api.php?action=fetch&partner_id=${partnerId}`);
        const data = await response.json();

        // CORRECTED: Using your data structure
        if (data.success && data.messages) {
            renderMessages(data.messages);
        } else {
            if (chatMessagesContainer.querySelector('.chat-loader')) {
                chatMessagesContainer.innerHTML = `<div class="chat-loader">${data.message || 'No messages'}</div>`;
            }
        }
    } catch (error) {
        console.error("Fetch messages error:", error);
        if (chatMessagesContainer.querySelector('.chat-loader')) {
            chatMessagesContainer.innerHTML = `<div class="chat-loader">Error loading messages.</div>`;
        }
    }
}

/**
 * Renders messages with Tailwind "chat bubble" classes
 */
function renderMessages(messages) {
    const isScrolledToBottom = chatMessagesContainer.scrollHeight - chatMessagesContainer.clientHeight <= chatMessagesContainer.scrollTop + 20;

    chatMessagesContainer.innerHTML = ''; // Clear previous messages
    if (messages.length === 0) {
        chatMessagesContainer.innerHTML = `<div class="chat-loader text-center p-4 text-sm text-gray-500">No messages yet. Start the conversation!</div>`;
        return;
    }

    messages.forEach(msg => {
        const messageElement = document.createElement('div');
        // CORRECTED: Using msg.sender_id
        const isSender = parseInt(msg.sender_id) === currentUserId;

        // ADDED: Tailwind classes for the message row (left or right)
        messageElement.className = `flex ${isSender ? 'justify-end' : 'justify-start'}`;

        // CORRECTED: Using msg.message_text, msg.created_at, msg.is_read
        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let ticksHtml = '';
        if (isSender) {
            // ADDED: Tailwind classes for read/unread ticks
            const readClass = parseInt(msg.is_read) === 1
                ? 'text-blue-400' // Read color
                : 'text-teal-100/70'; // Sent (unread) color
            ticksHtml = `<span class="ml-2 ${readClass}"><i class="fa-solid fa-check-double"></i></span>`;
        }

        // ADDED: Tailwind-styled HTML for the chat bubble
        messageElement.innerHTML = `
            <div class="p-3 rounded-lg max-w-xs md:max-w-md shadow-sm ${isSender ? 'bg-teal-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100'}">
                <div class="text-sm">${msg.message_text || ''}</div>
                <div class="text-xs mt-1 text-right ${isSender ? 'text-teal-100/70' : 'text-gray-500 dark:text-gray-400'}">
                    ${time}
                    ${ticksHtml}
                </div>
            </div>
        `;
        chatMessagesContainer.appendChild(messageElement);
    });

    if (isScrolledToBottom) {
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    }
}

async function sendMessage() {
    // CORRECTED: Using msg.message_text
    const messageText = messageInput.value.trim();
    if (messageText === '' || !activePartner.id) return;

    sendButton.disabled = true;

    try {
        // CORRECTED: Using your API endpoint and data structure
        const response = await fetch('../api/chat_api.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                receiver_id: activePartner.id,
                message_text: messageText
            })
        });

        const data = await response.json();
        if (data.success) {
            messageInput.value = '';
            // Re-fetch messages after sending
            fetchMessages(activePartner.id);
        } else {
            alert('Failed to send message: ' + data.message);
        }
    } catch (error) {
        console.error("Send message error:", error);
        alert('An error occurred while sending the message.');
    } finally {
        sendButton.disabled = false;
        messageInput.focus();
    }
}

// ---
// 5. Helpers & Initial Setup
// ---
function setLoading(isLoading) {
    if (chatLoader) {
        chatLoader.style.display = isLoading ? 'block' : 'none';
    }
    if (refreshButton) {
        refreshButton.disabled = isLoading;
        const icon = refreshButton.querySelector('i');
        if (icon) {
            isLoading ? icon.classList.add('fa-spin') : icon.classList.remove('fa-spin');
        }
    }
}

// --- Event Listeners (Run when the page loads) ---
document.addEventListener("DOMContentLoaded", () => {
    // Find all elements
    chatModal = document.getElementById('myInbox');
    userListContainer = document.getElementById('chat-user-list');
    userSearchInput = document.getElementById('chat-user-search');
    chatHeaderName = document.getElementById('chat-header-name'); // CORRECTED ID
    chatMessagesContainer = document.getElementById('chat-messages');
    messageInput = document.getElementById('chat-message-input');
    sendButton = document.getElementById('chat-send-btn');
    refreshButton = document.getElementById('chat-refresh-btn'); // This is the user list refresh
    chatLoader = document.querySelector('.chat-loader');
    chatMain = document.querySelector('.chat-main');
    chatWelcomeMain = document.querySelector('.chat-welcome-main');

    // Check if new elements exist
    if (!chatHeaderName || !chatMain || !chatWelcomeMain) {
        console.error('Chat UI elements are missing! Make sure you are using the new HTML for chat-inbox.');
        return;
    }

    // --- Attach Listeners ---
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Refresh Button (for user list)
    refreshButton.addEventListener('click', fetchBranchUsers);

    // Search Functionality
    userSearchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const allUsers = userListContainer.querySelectorAll('.chat-user-item');
        allUsers.forEach(user => {
            const username = user.dataset.username.toLowerCase();
            user.style.display = username.includes(searchTerm) ? 'flex' : 'none';
        });
    });
});