// --- Basic open/close logic for the popup ---
function openInbox() {
    document.getElementById("myInbox").style.display = 'block';
    fetchBranchUsers(); // Fetch users when the inbox is opened
}

function closeInbox() {
    document.getElementById("myInbox").style.display = 'none';
}

// --- DOM Element References ---
const userListContainer = document.getElementById('chat-user-list');
const userSearchInput = document.getElementById('chat-user-search');
const chatHeader = document.getElementById('chat-header');
const chatMessagesContainer = document.getElementById('chat-messages');
const messageInput = document.getElementById('chat-message-input');
const sendButton = document.getElementById('chat-send-btn');
const refreshButton = document.getElementById('chat-refresh-btn');

let activePartner = {
    id: null,
    username: null
};
// REMOVED: No more messagePollingInterval variable

// --- Fetch and Render the User List ---
async function fetchBranchUsers() {
    try {
        const response = await fetch('../api/fetch_branch_users.php');
        if (!response.ok) throw new Error('Network response was not ok.');
        const data = await response.json();
        if (data.success && data.users) {
            renderUserList(data.users);
        } else {
            userListContainer.innerHTML = `<div class="chat-loader">${data.message || 'Failed to load users.'}</div>`;
        }
    } catch (error) {
        console.error("Fetch error:", error);
        userListContainer.innerHTML = `<div class="chat-loader">Error loading users.</div>`;
    }
}

function renderUserList(users) {
    userListContainer.innerHTML = '';
    if (users.length === 0) {
        userListContainer.innerHTML = `<div class="chat-loader">No other users in this branch.</div>`;
        return;
    }
    users.forEach(user => {
        const userInitial = user.username.charAt(0).toUpperCase();
        const userElement = document.createElement('div');
        userElement.className = 'chat-user-item';
        userElement.dataset.userId = user.id;
        userElement.dataset.username = user.username;
        userElement.innerHTML = `
<div class="chat-user-avatar">${userInitial}</div>
<div class="chat-user-info">
    <div class="name">${user.username}</div>
    <div class="role">${user.role}</div>
</div>`;
        userElement.addEventListener('click', () => selectUser(userElement));
        userListContainer.appendChild(userElement);
    });
}

// --- Search Functionality ---
userSearchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const allUsers = userListContainer.querySelectorAll('.chat-user-item');
    allUsers.forEach(user => {
        const username = user.dataset.username.toLowerCase();
        user.style.display = username.includes(searchTerm) ? 'flex' : 'none';
    });
});

// --- Core Chat Functions ---

// 1. Select a user to chat with
function selectUser(selectedElement) {
    // REMOVED: No more clearInterval
    const allUsers = userListContainer.querySelectorAll('.chat-user-item');
    allUsers.forEach(u => u.classList.remove('active'));
    selectedElement.classList.add('active');

    activePartner.id = selectedElement.dataset.userId;
    activePartner.username = selectedElement.dataset.username;

    const headerContent = document.createElement('div');
    headerContent.innerHTML = `Chat with <strong>${activePartner.username}</strong>`;
    const encryptionStatus = document.querySelector('.encryption-status').cloneNode(true);
    chatHeader.innerHTML = '';
    chatHeader.appendChild(headerContent);
    chatHeader.appendChild(encryptionStatus);

    messageInput.disabled = false;
    sendButton.disabled = false;
    refreshButton.disabled = false;
    messageInput.focus();

    // Set initial loading state and fetch messages for the first time.
    chatMessagesContainer.innerHTML = `<div class="chat-loader">Loading messages...</div>`;
    fetchMessages(activePartner.id);

    // REMOVED: No more setInterval
}

// 2. Fetch message history from the API
async function fetchMessages(partnerId) {
    if (!partnerId) return;

    try {
        const response = await fetch(`../api/chat_api.php?action=fetch&partner_id=${partnerId}`);
        const data = await response.json();
        if (data.success) {
            renderMessages(data.messages);
        } else {
            // Only update if it's still in the initial loading state
            if (chatMessagesContainer.querySelector('.chat-loader')) {
                chatMessagesContainer.innerHTML = `<div class="chat-loader">${data.message}</div>`;
            }
        }
    } catch (error) {
        console.error("Fetch messages error:", error);
        if (chatMessagesContainer.querySelector('.chat-loader')) {
            chatMessagesContainer.innerHTML = `<div class="chat-loader">Error loading messages.</div>`;
        }
    }
}

// 3. Render the messages in the chat window
function renderMessages(messages) {
    const currentScroll = chatMessagesContainer.scrollTop;
    const maxScroll = chatMessagesContainer.scrollHeight - chatMessagesContainer.clientHeight;
    const isScrolledToBottom = maxScroll - currentScroll <= 20;

    chatMessagesContainer.innerHTML = '';
    if (messages.length === 0) {
        chatMessagesContainer.innerHTML = `<div class="chat-loader">No messages yet. Start the conversation!</div>`;
        return;
    }
    messages.forEach(msg => {
        const messageElement = document.createElement('div');
        const isSender = parseInt(msg.sender_id) === currentUserId;
        messageElement.className = isSender ? 'chat-message sent' : 'chat-message received';
        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let ticksHtml = '';
        if (isSender) {
            // Use 'read' for read messages (is_read=1), and 'unread' for sent but not read messages (is_read=0)
            const readClass = parseInt(msg.is_read) === 1 ? 'read' : 'unread';
            // The HTML always includes two checks, but CSS will hide the second one for the 'unread' class.
            ticksHtml = `<span class="ticks ${readClass}"><i class="fa-solid fa-check"></i><i class="fa-solid fa-check"></i></span>`;
        }

        messageElement.innerHTML = `
            <div class="message-bubble">${msg.message_text || ''}</div>
            <div class="message-time">${time} ${ticksHtml}</div>
        `;
        chatMessagesContainer.appendChild(messageElement);
    });

    if (isScrolledToBottom) {
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    }
}

// 4. Send a new message
async function sendMessage() {
    const messageText = messageInput.value.trim();
    if (messageText === '' || !activePartner.id) return;
    sendButton.disabled = true;
    try {
        const response = await fetch('../api/chat_api.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receiver_id: activePartner.id, message_text: messageText })
        });
        const data = await response.json();
        if (data.success) {
            messageInput.value = '';
            // Fetch messages after successfully sending one
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

// --- Event Listeners ---
sendButton.addEventListener('click', sendMessage);
messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});

// Refresh Button Logic (remains the same)
refreshButton.addEventListener('click', () => {
    if (!activePartner.id || refreshButton.disabled) {
        return;
    }
    refreshButton.disabled = true;
    refreshButton.classList.add('loading');
    fetchMessages(activePartner.id).finally(() => {
        setTimeout(() => {
            refreshButton.disabled = false;
            refreshButton.classList.remove('loading');
        }, 4000);
    });
});
