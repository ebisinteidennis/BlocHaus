<?php
session_start();

// Check if user is logged in and is admin/consultant
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'consultant'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get all active consultations with user info
$query = "SELECT c.*, u.full_name as user_name, u.email, u.interest_type,
                 (SELECT COUNT(*) FROM chat_messages cm WHERE cm.consultation_id = c.id AND cm.sender_id != ? AND cm.is_read = 0) as unread_count,
                 (SELECT cm.created_at FROM chat_messages cm WHERE cm.consultation_id = c.id ORDER BY cm.created_at DESC LIMIT 1) as last_message_time
          FROM consultations c 
          JOIN users u ON c.user_id = u.id 
          WHERE c.status IN ('pending', 'active')
          ORDER BY last_message_time DESC, c.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(1, $_SESSION['user_id']);
$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Management - BlockHaus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-sidebar {
            height: calc(100vh - 56px);
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }
        
        .chat-main {
            height: calc(100vh - 56px);
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        
        .consultation-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .consultation-item:hover {
            background-color: #f8f9fa;
        }
        
        .consultation-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 10px;
            max-width: 70%;
        }
        
        .message.user {
            background: #e3f2fd;
            color: #333;
            margin-right: auto;
        }
        
        .message.consultant {
            background: #2196f3;
            color: white;
            margin-left: auto;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt me-2"></i>BlockHaus Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-1"></i>Users
                </a>
                <a class="nav-link" href="properties.php">
                    <i class="fas fa-building me-1"></i>Properties
                </a>
                <a class="nav-link active" href="chat.php">
                    <i class="fas fa-comments me-1"></i>Chat
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Consultations Sidebar -->
            <div class="col-md-4 col-lg-3">
                <div class="chat-sidebar">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">Active Consultations</h5>
                        <small class="text-muted"><?php echo count($consultations); ?> active chats</small>
                    </div>
                    
                    <?php if (empty($consultations)): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No active consultations</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($consultations as $consultation): ?>
                            <div class="consultation-item" onclick="loadConsultation(<?php echo $consultation['id']; ?>)" data-consultation-id="<?php echo $consultation['id']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($consultation['user_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($consultation['email']); ?></small>
                                        <br>
                                        <small class="text-primary"><?php echo ucfirst($consultation['interest_type']); ?></small>
                                    </div>
                                    <?php if ($consultation['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $consultation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php 
                                        if ($consultation['last_message_time']) {
                                            echo 'Last message: ' . date('M j, g:i A', strtotime($consultation['last_message_time']));
                                        } else {
                                            echo 'Started: ' . date('M j, g:i A', strtotime($consultation['created_at']));
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Main Area -->
            <div class="col-md-8 col-lg-9">
                <div class="chat-main" id="chatMain">
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-comment-dots fa-4x mb-3"></i>
                            <h4>Select a consultation to start chatting</h4>
                            <p>Choose a consultation from the sidebar to view and respond to messages</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentConsultationId = null;
        let chatInterval = null;

        function loadConsultation(consultationId) {
            // Update active consultation in sidebar
            document.querySelectorAll('.consultation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-consultation-id="${consultationId}"]`).classList.add('active');

            currentConsultationId = consultationId;
            
            // Load chat interface
            loadChatInterface(consultationId);
            
            // Start polling for new messages
            if (chatInterval) {
                clearInterval(chatInterval);
            }
            chatInterval = setInterval(() => loadMessages(consultationId), 3000);
        }

        async function loadChatInterface(consultationId) {
            try {
                const response = await fetch(`../api/chat.php?action=consultation&consultation_id=${consultationId}`);
                const data = await response.json();
                
                if (data.success) {
                    const consultation = data.data;
                    
                    document.getElementById('chatMain').innerHTML = `
                        <div class="p-3 border-bottom bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">${consultation.user_name}</h5>
                                    <small class="text-muted">${consultation.email} • Interested in ${consultation.interest_type}</small>
                                </div>
                                <span class="badge bg-success">${consultation.status}</span>
                            </div>
                        </div>
                        <div class="chat-messages" id="chatMessages">
                            <!-- Messages will be loaded here -->
                        </div>
                        <div class="chat-input">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInput" placeholder="Type your response..." onkeypress="handleKeyPress(event)">
                                <button class="btn btn-primary" onclick="sendMessage()">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </div>
                    `;
                    
                    loadMessages(consultationId);
                }
            } catch (error) {
                console.error('Error loading consultation:', error);
            }
        }

        async function loadMessages(consultationId) {
            try {
                const response = await fetch(`../api/chat.php?action=messages&consultation_id=${consultationId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayMessages(data.data);
                    
                    // Update unread count in sidebar
                    const consultationItem = document.querySelector(`[data-consultation-id="${consultationId}"]`);
                    const unreadBadge = consultationItem.querySelector('.unread-badge');
                    if (unreadBadge) {
                        unreadBadge.remove();
                    }
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function displayMessages(messages) {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            messagesContainer.innerHTML = '';
            
            if (messages.length === 0) {
                messagesContainer.innerHTML = `
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-comment-dots fa-2x mb-3"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
                return;
            }
            
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                const senderClass = message.role === 'user' ? 'user' : 'consultant';
                messageDiv.className = `message ${senderClass}`;
                
                const timestamp = new Date(message.created_at).toLocaleString();
                messageDiv.innerHTML = `
                    <div class="mb-1">
                        <strong>${message.full_name}</strong>
                        <small class="text-muted ms-2">${timestamp}</small>
                    </div>
                    <div>${message.message}</div>
                `;
                
                messagesContainer.appendChild(messageDiv);
            });
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message && currentConsultationId) {
                try {
                    const response = await fetch('../api/chat.php?action=send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            message: message,
                            consultation_id: currentConsultationId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        input.value = '';
                        loadMessages(currentConsultationId);
                    } else {
                        alert('Failed to send message: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message. Please try again.');
                }
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (chatInterval) {
                clearInterval(chatInterval);
            }
        });

        // Auto-refresh consultation list every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
