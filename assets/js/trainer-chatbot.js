document.addEventListener('DOMContentLoaded', () => {
    const chatbotBubble = document.getElementById('chatbot-bubble');
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotMessages = document.getElementById('chatbot-messages');
    const chatbotInput = document.getElementById('chatbot-input');
    const chatbotSend = document.getElementById('chatbot-send');
    const chatbotChips = document.getElementById('chatbot-chips');
    const logoutBtn = document.querySelector('a[href="../logout.php"]'); // Target logout link

    // --- Chat History Persistence ---
    function saveChatHistory() {
        if (chatbotMessages) {
            sessionStorage.setItem('kpf_trainer_chat_history', chatbotMessages.innerHTML);
            sessionStorage.setItem('kpf_trainer_chatbot_open', !chatbotWindow.classList.contains('d-none'));
        }
    }

    function loadChatHistory() {
        const savedHistory = sessionStorage.getItem('kpf_trainer_chat_history');
        if (savedHistory && chatbotMessages) {
            chatbotMessages.innerHTML = savedHistory;
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        const wasOpen = sessionStorage.getItem('kpf_trainer_chatbot_open') === 'true';
        if (chatbotWindow) {
            if (wasOpen) {
                chatbotWindow.classList.remove('d-none');
            } else {
                chatbotWindow.classList.add('d-none');
            }
        }
    }
    
    // Clear history on logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            sessionStorage.removeItem('kpf_trainer_chat_history');
            sessionStorage.removeItem('kpf_trainer_chatbot_open');
        });
    }

    loadChatHistory();

    if (chatbotBubble && chatbotWindow && chatbotClose) {
        chatbotBubble.addEventListener('click', () => {
            chatbotWindow.classList.toggle('d-none');
            saveChatHistory();
        });

        chatbotClose.addEventListener('click', () => {
            chatbotWindow.classList.add('d-none');
            saveChatHistory();
        });

        if (chatbotSend && chatbotInput) {
            chatbotSend.addEventListener('click', sendMessage);
            chatbotInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }
        
        if (chatbotChips) {
            chatbotChips.addEventListener('click', (e) => {
                if (e.target.classList.contains('chip')) {
                    const message = e.target.getAttribute('data-message');
                    chatbotInput.value = message;
                    sendMessage();
                }
            });
        }
    }

    function sendMessage() {
        const userMessage = chatbotInput.value.trim();
        if (userMessage === '') return;

        appendMessage(userMessage, 'user');
        chatbotInput.value = '';
        
        const formData = new FormData();
        formData.append('message', userMessage);
        
        // Use global config if available, or fallback
        const csrfToken = window.trainerConfig ? window.trainerConfig.csrfToken : '';
        formData.append('csrf_token', csrfToken);

        fetch('../api/trainer_chatbot_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const navRegex = /\[NAVIGATE:(.*?)\]/;
            const match = data.reply.match(navRegex);
            
            let displayMessage = data.reply;

            if (match) {
                const url = match[1];
                displayMessage = displayMessage.replace(navRegex, '');
                appendMessage(displayMessage, 'bot');
                
                setTimeout(() => {
                    window.location.href = url;
                }, 1500);
            } else {
                appendMessage(displayMessage, 'bot');
            }
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        })
        .catch(error => {
            console.error('Error:', error);
            appendMessage("Oops! Something went wrong. Please try again later.", 'bot');
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        });
    }

    function appendMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender);
        
        const buttonRegex = /\[BUTTON:(.*?)\|(.*?)\]/g;
        
        if (sender === 'bot' && buttonRegex.test(text)) {
            const formattedText = text.replace(buttonRegex, (match, label, url) => {
                return `<br><a href="${url}" class="chip mt-2">${label}</a>`;
            });
            messageDiv.innerHTML = formattedText;
        } else {
            messageDiv.textContent = text;
        }

        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        saveChatHistory();
    }
});
