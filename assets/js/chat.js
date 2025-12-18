// ============================================
// CHAT/MESSAGING SYSTEM JAVASCRIPT
// ============================================

$(document).ready(function() {
    initChatSystem();
    initMessageNotifications();
    initTypingIndicator();
    initFileSharing();
    initVoiceMessages();
});

function initChatSystem() {
    // Open chat with user
    $(document).on('click', '.start-chat', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        
        openChat(userId, userName);
    });
    
    // Send message
    $(document).on('submit', '#messageForm', function(e) {
        e.preventDefault();
        sendMessage();
    });
    
    // Load conversation history
    $(document).on('click', '.conversation-item', function() {
        const conversationId = $(this).data('conversation-id');
        loadConversation(conversationId);
    });
    
    // Search conversations
    $(document).on('keyup', '#searchConversations', function() {
        const searchTerm = $(this).val().trim();
        searchConversations(searchTerm);
    });
    
    // Mark as read
    $(document).on('click', '.conversation-item', function() {
        const conversationId = $(this).data('conversation-id');
        markAsRead(conversationId);
    });
    
    // Delete conversation
    $(document).on('click', '.delete-conversation', function(e) {
        e.stopPropagation();
        const conversationId = $(this).data('conversation-id');
        deleteConversation(conversationId);
    });
}

function openChat(userId, userName) {
    // Open chat modal
    $('#chatModal').modal('show');
    $('#chatUserName').text(userName);
    $('#chatUserId').val(userId);
    
    // Load messages
    loadMessages(userId);
    
    // Start polling for new messages
    startMessagePolling(userId);
}

function sendMessage() {
    const messageInput = $('#messageInput');
    const message = messageInput.val().trim();
    const receiverId = $('#chatUserId').val();
    
    if (!message) return;
    
    // Add message to UI immediately
    addMessageToUI({
        id: 'temp-' + Date.now(),
        sender_id: 'current',
        message: message,
        created_at: new Date().toISOString(),
        is_read: false
    });
    
    // Clear input
    messageInput.val('');
    
    // Send to server
    $.ajax({
        url: 'api/chat.php?action=send',
        method: 'POST',
        data: {
            receiver_id: receiverId,
            message: message
        },
        success: function(response) {
            if (response.success) {
                // Replace temp message with actual message
                replaceTempMessage(response.message);
            }
        }
    });
}

function addMessageToUI(message) {
    const isCurrentUser = message.sender_id === 'current';
    const messageClass = isCurrentUser ? 'message-out' : 'message-in';
    
    const messageHtml = `
        <div class="message ${messageClass}" data-id="${message.id}">
            <div class="message-content">
                <p>${escapeHtml(message.message)}</p>
                <div class="message-time">
                    ${formatMessageTime(message.created_at)}
                    ${isCurrentUser ? 
                        `<span class="message-status ${message.is_read ? 'read' : 'sent'}">
                            <i class="fas fa-check${message.is_read ? '-double' : ''}"></i>
                        </span>` : ''}
                </div>
            </div>
        </div>
    `;
    
    $('#messagesContainer').append(messageHtml);
    scrollToBottom();
}

function replaceTempMessage(message) {
    const tempMessage = $(`[data-id^="temp-"]`);
    if (tempMessage.length) {
        tempMessage.attr('data-id', message.id);
        tempMessage.find('.message-status').addClass('sent');
    }
}

function loadMessages(userId, lastMessageId = null) {
    $.ajax({
        url: 'api/chat.php?action=get_messages',
        method: 'GET',
        data: {
            user_id: userId,
            last_message_id: lastMessageId
        },
        success: function(response) {
            if (response.success) {
                if (lastMessageId) {
                    // Append older messages
                    response.messages.reverse().forEach(addMessageToUI);
                } else {
                    // Load all messages
                    $('#messagesContainer').empty();
                    response.messages.forEach(addMessageToUI);
                    scrollToBottom();
                }
            }
        }
    });
}

function loadConversation(conversationId) {
    $.ajax({
        url: 'api/chat.php?action=get_conversation',
        method: 'GET',
        data: { conversation_id: conversationId },
        success: function(response) {
            if (response.success) {
                $('#messagesContainer').empty();
                response.messages.forEach(addMessageToUI);
                scrollToBottom();
            }
        }
    });
}

function startMessagePolling(userId) {
    if (window.messagePollInterval) {
        clearInterval(window.messagePollInterval);
    }
    
    window.messagePollInterval = setInterval(() => {
        checkNewMessages(userId);
    }, 3000); // Check every 3 seconds
}

function checkNewMessages(userId) {
    const lastMessageId = $('#messagesContainer .message:last').data('id');
    
    if (!lastMessageId || lastMessageId.startsWith('temp-')) return;
    
    $.ajax({
        url: 'api/chat.php?action=check_new',
        method: 'GET',
        data: {
            user_id: userId,
            last_message_id: lastMessageId
        },
        success: function(response) {
            if (response.success && response.messages.length > 0) {
                response.messages.forEach(addMessageToUI);
                
                // Play notification sound
                playNotificationSound();
                
                // Update unread count
                updateUnreadCount();
            }
        }
    });
}

function searchConversations(searchTerm) {
    $.ajax({
        url: 'api/chat.php?action=search',
        method: 'GET',
        data: { q: searchTerm },
        success: function(response) {
            if (response.success) {
                $('#conversationsList').html(response.html);
            }
        }
    });
}

function markAsRead(conversationId) {
    $.ajax({
        url: 'api/chat.php?action=mark_read',
        method: 'POST',
        data: { conversation_id: conversationId }
    });
}

function deleteConversation(conversationId) {
    if (confirm('Delete this conversation?')) {
        $.ajax({
            url: 'api/chat.php?action=delete',
            method: 'POST',
            data: { conversation_id: conversationId },
            success: function(response) {
                if (response.success) {
                    $(`.conversation-item[data-conversation-id="${conversationId}"]`).remove();
                    showNotification('Conversation deleted', 'info');
                }
            }
        });
    }
}

function initMessageNotifications() {
    // Check for new messages on page load
    updateUnreadCount();
    
    // Play sound on new message
    function playNotificationSound() {
        const audio = new Audio('assets/sounds/notification.mp3');
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
    
    // Desktop notifications
    function showDesktopNotification(title, message) {
        if (!("Notification" in window)) return;
        
        if (Notification.permission === "granted") {
            new Notification(title, { body: message });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification(title, { body: message });
                }
            });
        }
    }
}

function updateUnreadCount() {
    $.ajax({
        url: 'api/chat.php?action=unread_count',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('.message-count').text(response.count).toggle(response.count > 0);
            }
        }
    });
}

function initTypingIndicator() {
    let typingTimeout;
    
    $('#messageInput').on('keyup', function() {
        const receiverId = $('#chatUserId').val();
        if (!receiverId) return;
        
        // Send typing indicator
        sendTypingIndicator(receiverId, true);
        
        // Clear previous timeout
        clearTimeout(typingTimeout);
        
        // Set timeout to stop typing indicator
        typingTimeout = setTimeout(() => {
            sendTypingIndicator(receiverId, false);
        }, 1000);
    });
    
    // Show typing indicator from others
    function showTypingIndicator(userId, userName) {
        $('#typingIndicator').text(userName + ' is typing...').fadeIn();
        
        setTimeout(() => {
            $('#typingIndicator').fadeOut();
        }, 3000);
    }
}

function sendTypingIndicator(receiverId, isTyping) {
    $.ajax({
        url: 'api/chat.php?action=typing',
        method: 'POST',
        data: {
            receiver_id: receiverId,
            is_typing: isTyping ? 1 : 0
        }
    });
}

function initFileSharing() {
    // File upload
    $('#fileUpload').on('change', function(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        
        uploadFile(files[0]);
    });
    
    // Drag and drop
    $('#messageInput').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave', function() {
        $(this).removeClass('drag-over');
    }).on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadFile(files[0]);
        }
    });
}

function uploadFile(file) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        showNotification('File size must be less than 10MB', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('receiver_id', $('#chatUserId').val());
    
    $.ajax({
        url: 'api/chat.php?action=upload_file',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                addFileMessageToUI(response.file);
            }
        }
    });
}

function addFileMessageToUI(file) {
    const message = {
        id: 'file-' + Date.now(),
        sender_id: 'current',
        message: `File: ${file.name} (${formatFileSize(file.size)})`,
        file_url: file.url,
        file_type: file.type,
        created_at: new Date().toISOString(),
        is_read: false
    };
    
    addMessageToUI(message);
}

function initVoiceMessages() {
    let mediaRecorder;
    let audioChunks = [];
    
    $('#startRecording').on('mousedown touchstart', function() {
        startRecording();
    }).on('mouseup touchend', function() {
        stopRecording();
    });
    
    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                uploadVoiceMessage(audioBlob);
                stream.getTracks().forEach(track => track.stop());
            };
            
            mediaRecorder.start();
            $('#startRecording').addClass('recording');
        } catch (err) {
            console.error('Error recording:', err);
            showNotification('Microphone access denied', 'error');
        }
    }
    
    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            $('#startRecording').removeClass('recording');
        }
    }
}

function uploadVoiceMessage(audioBlob) {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'voice-message.webm');
    formData.append('receiver_id', $('#chatUserId').val());
    
    $.ajax({
        url: 'api/chat.php?action=upload_voice',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                addVoiceMessageToUI(response.audio);
            }
        }
    });
}

function addVoiceMessageToUI(audio) {
    const message = {
        id: 'voice-' + Date.now(),
        sender_id: 'current',
        message: 'Voice message',
        audio_url: audio.url,
        duration: audio.duration,
        created_at: new Date().toISOString(),
        is_read: false
    };
    
    addMessageToUI(message);
}

// Utility functions
function scrollToBottom() {
    const container = $('#messagesContainer');
    container.scrollTop(container[0].scrollHeight);
}

function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize chat on page load
$(document).ready(function() {
    // Auto-open chat if in URL
    const urlParams = new URLSearchParams(window.location.search);
    const chatUserId = urlParams.get('chat');
    if (chatUserId) {
        openChat(chatUserId, 'User');
    }
});
