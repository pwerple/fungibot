jQuery(document).ready(function($) {
    const Fungibot = {
        init() {
            this.elements = {
                container: $('#fungitown-chatbot'),
                input: $('#fungitown-chatbot input'),
                button: $('#fungitown-chatbot button'),
                messages: $('#fungitown-chatbot .chat-messages')
            };
            
            this.messages = [];
            this.bindEvents();
        },

        bindEvents() {
            this.elements.input.on('keypress', (e) => {
                if (e.key === 'Enter') this.handleSend();
            });
            
            this.elements.button.on('click', () => this.handleSend());
        },

        async handleSend() {
            const userMessage = this.elements.input.val().trim();
            if (!userMessage) return;

            this.addMessage('user', userMessage);
            this.elements.input.val('').prop('disabled', true);
            this.showTyping(true);

            // Add to message history
            this.messages.push({ role: "user", content: userMessage });

            try {
                const response = await $.ajax({
                    url: fungiChatData.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'www_chatbot',
                        security: fungiChatData.nonce,
                        messages: JSON.stringify(this.messages)
                    },
                    traditional: true // Important for WordPress AJAX
                });

                if (!response.success) {
                    throw new Error(response.data || 'Server error');
                }

                const botReply = response.data.reply;
                this.addMessage('assistant', botReply);
                this.messages.push({ role: "assistant", content: botReply });

            } catch (error) {
                console.error('Full error:', {
                    status: error.status,
                    response: error.responseText,
                    message: error.message
                });
                
                this.addMessage('assistant', 
                    '🍄 Technical difficulty! ' + 
                    (error.responseJSON?.data || 'Please try again'));
            } finally {
                this.showTyping(false);
                this.elements.input.prop('disabled', false).focus();
            }
        },

        addMessage(role, content) {
            const messageClass = role + '-message';
            this.elements.messages.append(
                `<div class="message ${messageClass}">${
                    role === 'assistant' ? '🍄 ' : ''
                }${content}</div>`
            ).scrollTop(this.elements.messages[0].scrollHeight);
        },

        showTyping(show) {
            const typingId = 'fungibot-typing';
            if (show) {
                this.elements.messages.append(`
                    <div id="${typingId}" class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                `);
            } else {
                $(`#${typingId}`).remove();
            }
        }
    };

    Fungibot.init();
});