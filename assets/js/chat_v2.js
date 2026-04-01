// /Gymora/assets/js/chat_v2.js

let currentChatUserId = 0;
let lastMessageId = 0;
let chatInterval = null;

function loadChat(userId, userName) {
    currentChatUserId = userId;
    lastMessageId = 0; // Reset for new chat
    
    // Update UI headers
    document.getElementById('chat-with-name').innerText = userName;
    
    // This safely wipes the box and shows loading, completely replacing the empty state
    document.getElementById('chat-box').innerHTML = '<div class="text-center text-muted mt-5">Loading messages...</div>';
    
    document.getElementById('message-form').style.display = 'block';

    // Highlight active contact
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active', 'bg-primary', 'text-white'));
    document.getElementById('contact-' + userId).classList.add('active', 'bg-primary', 'text-white');

    // Fetch initial messages instantly (This will now actually run!)
    fetchMessages();

    // Clear any existing polling and start a new one (poll every 4 seconds)
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(fetchMessages, 4000);
}

function fetchMessages() {
    if (currentChatUserId === 0) return;

    // The Ultimate Cache Buster: Timestamp + Random Number
    const cacheBuster = new Date().getTime() + Math.random();
    const url = `../api/get_messages.php?other_user_id=${currentChatUserId}&last_message_id=${lastMessageId}&cb=${cacheBuster}`;

    fetch(url, { cache: "no-store", headers: { 'Cache-Control': 'no-cache' } })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const chatBox = document.getElementById('chat-box');
                
                // If the database returns messages
                if (data.messages && data.messages.length > 0) {
                    if (lastMessageId === 0) chatBox.innerHTML = ''; // Clear loading text

                    data.messages.forEach(msg => {
                        const isMine = msg.is_mine;
                        const bubbleClass = isMine ? 'bg-primary text-white float-end' : 'bg-light border float-start';
                        
                        const msgHTML = `
                            <div class="clearfix mb-3">
                                <div class="p-3 rounded-3 shadow-sm ${bubbleClass}" style="max-width: 75%;">
                                    ${msg.content}
                                    <div class="small mt-1 ${isMine ? 'text-white-50' : 'text-muted'}">${msg.time_formatted}</div>
                                </div>
                            </div>
                        `;
                        chatBox.innerHTML += msgHTML;
                        lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                    });
                    
                    chatBox.scrollTop = chatBox.scrollHeight; // Scroll to bottom
                } 
                // If the database returns NO messages and it's our first time loading
                else if (lastMessageId === 0) {
                    chatBox.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Start the conversation!</div>';
                }
            } else {
                console.error("Server Error:", data.message);
            }
        })
        .catch(error => console.error('AJAX Parse Error:', error));
}

// Handle sending a new message
document.addEventListener("DOMContentLoaded", function() {
    const msgForm = document.getElementById('message-input-form');
    
    if(msgForm) {
        msgForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            const contentInput = document.getElementById('message-input');
            const content = contentInput.value.trim();
            
            if (content === '' || currentChatUserId === 0) return;
            
            const formData = new FormData();
            formData.append('receiver_id', currentChatUserId);
            formData.append('content', content);
            
            contentInput.value = ''; // Clear input instantly
            
            fetch('../api/send_messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchMessages(); // Immediately pull the new message
                }
            })
            .catch(err => console.error("Network error on send:", err));
        });
    }
});