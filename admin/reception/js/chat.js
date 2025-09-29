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

let activePartner = {
    id: null,
    username: null
};

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
    userListContainer.innerHTML = ''; // Clear loader or previous list
    if (users.length === 0) {
        userListContainer.innerHTML = `<div class="chat-loader">No other users in this branch.</div>`;
        return;
    }

    users.forEach(user => {
        const userInitial = user.username.charAt(0);
        const userElement = document.createElement('div');
        userElement.className = 'chat-user-item';
        userElement.dataset.userId = user.id;
        userElement.dataset.username = user.username;

        userElement.innerHTML = `
<div class="chat-user-avatar">${userInitial}</div>
<div class="chat-user-info">
    <div class="name">${user.username}</div>
    <div class="role">${user.role}</div>
</div>
`;
        // Add click listener to start a chat
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
        if (username.includes(searchTerm)) {
            user.style.display = 'flex';
        } else {
            user.style.display = 'none';
        }
    });
});

// --- Core Chat Functions ---

// 1. Select a user to chat with
function selectUser(selectedElement) {
    const allUsers = userListContainer.querySelectorAll('.chat-user-item');
    allUsers.forEach(u => u.classList.remove('active'));
    selectedElement.classList.add('active');

    activePartner.id = selectedElement.dataset.userId;
    activePartner.username = selectedElement.dataset.username;

    chatHeader.innerHTML = `Chat with <strong>${activePartner.username}</strong>`;
    messageInput.disabled = false;
    sendButton.disabled = false;
    messageInput.focus();

    // Fetch the message history with this user
    fetchMessages(activePartner.id);
}

// 2. Fetch message history from the API
async function fetchMessages(partnerId) {
    // We need currentUserId here, which we will define in the HTML.
    chatMessagesContainer.innerHTML = `<div class="chat-loader">Loading messages...</div>`;
    try {
        const response = await fetch(`../api/chat_api.php?action=fetch&partner_id=${partnerId}`);
        const data = await response.json();
        if (data.success) {
            renderMessages(data.messages);
        } else {
            chatMessagesContainer.innerHTML = `<div class="chat-loader">${data.message}</div>`;
        }
    } catch (error) {
        console.error("Fetch messages error:", error);
        chatMessagesContainer.innerHTML = `<div class="chat-loader">Error loading messages.</div>`;
    }
}

// 3. Render the messages in the chat window
function renderMessages(messages) {
    // We also need currentUserId here.
    chatMessagesContainer.innerHTML = '';
    if (messages.length === 0) {
        chatMessagesContainer.innerHTML = `<div class="chat-loader">No messages yet. Start the conversation!</div>`;
        return;
    }
    messages.forEach(msg => {
        const messageElement = document.createElement('div');
        const isSender = parseInt(msg.sender_id) === currentUserId;
        messageElement.className = isSender ? 'chat-message sent' : 'chat-message received';

        const time = new Date(msg.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageElement.innerHTML = `
<div class="message-bubble">${msg.message_text}</div>
<div class="message-time">${time}</div>
`;
        chatMessagesContainer.appendChild(messageElement);
    });
    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
}

// 4. Send a new message
async function sendMessage() {
    const messageText = messageInput.value.trim();
    if (messageText === '' || !activePartner.id) {
        return;
    }

    sendButton.disabled = true;

    try {
        const response = await fetch('../api/chat_api.php?action=send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                receiver_id: activePartner.id,
                message_text: messageText
            })
        });
        const data = await response.json();
        if (data.success) {
            messageInput.value = '';
            fetchMessages(activePartner.id); // Refresh messages
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

// --- Event Listeners for Sending ---
sendButton.addEventListener('click', sendMessage);
messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});