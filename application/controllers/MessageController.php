<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class MessageController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('MessageModel');
        $this->load->model('User_model');
    }

    public function getConversation($userId = null) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        if (!$userId || !is_numeric($userId)) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid User ID'
            ]);
            return;
        }
    
        try {
            // Get or create a conversation for this user
            $conversation = $this->MessageModel->get_or_create_conversation($userId);
            
            if (!$conversation) {
                throw new Exception('Could not retrieve conversation');
            }
            
            // Get messages for this conversation
            $messages = $this->MessageModel->get_conversation_messages($conversation['id']);
            
            // Format messages for frontend
            $formattedMessages = [];
            foreach ($messages as $message) {
                $formattedMessages[] = [
                    'id' => (int)$message['id'],
                    'text' => $message['message'],
                    'senderId' => (int)$message['sender_id'],
                    'isAdmin' => (bool)$message['is_admin'],
                    'timestamp' => $message['timestamp'],
                    'status' => $message['status']
                ];
            }
            
            echo json_encode([
                'status' => true,
                'conversation_id' => $conversation['id'],
                'messages' => $formattedMessages
            ]);
            
            // Mark all admin messages as read
            if (!empty($formattedMessages)) {
                $this->MessageModel->mark_messages_as_read($conversation['id'], $userId, true);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error fetching conversation: ' . $e->getMessage()
            ]);
        }
    }

    public function sendMessage() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
    
        if (!$input || 
            !isset($input['user_id']) || 
            !isset($input['message']) ||
            !isset($input['is_admin'])) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }
    
        try {
            // Get or create a conversation
            $conversation = $this->MessageModel->get_or_create_conversation($input['user_id']);
            
            if (!$conversation) {
                throw new Exception('Could not create or retrieve conversation');
            }
            
            $messageData = [
                'conversation_id' => $conversation['id'],
                'sender_id' => $input['user_id'],
                'is_admin' => (bool)$input['is_admin'] ? 1 : 0,
                'message' => trim($input['message']),
                'status' => 'sent',
                'timestamp' => date('Y-m-d H:i:s')
            ];
    
            $result = $this->MessageModel->insert_message($messageData);
    
            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'status' => true,
                    'message' => 'Message sent successfully',
                    'data' => [
                        'id' => $result,
                        'text' => $messageData['message'],
                        'senderId' => (int)$messageData['sender_id'],
                        'isAdmin' => (bool)$messageData['is_admin'],
                        'timestamp' => $messageData['timestamp'],
                        'status' => $messageData['status']
                    ]
                ]);
            } else {
                throw new Exception('Failed to send message');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateMessageStatus() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
    
        if (!$input || 
            !isset($input['message_id']) || 
            !isset($input['status'])) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }
        
        // Validate status
        $validStatuses = ['sent', 'delivered', 'read'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid status value'
            ]);
            return;
        }
    
        try {
            $result = $this->MessageModel->update_message_status(
                $input['message_id'], 
                $input['status']
            );
    
            if ($result) {
                echo json_encode([
                    'status' => true,
                    'message' => 'Message status updated'
                ]);
            } else {
                throw new Exception('Failed to update message status');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getAdminConversations() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Ensure this endpoint is only accessible by admins
        // You should add proper authorization here
        
        try {
            $conversations = $this->MessageModel->get_all_conversations();
            
            // Enhance with user details and message counts
            foreach ($conversations as &$conversation) {
                $user = $this->User_model->get_user_by_id($conversation['user_id']);
                $conversation['user_name'] = $user ? $user['name'] : 'Unknown User';
                $conversation['user_email'] = $user ? $user['email'] : 'unknown@example.com';
                
                // Get unread message count
                $conversation['unread_count'] = $this->MessageModel->count_unread_messages(
                    $conversation['id'], 
                    $conversation['user_id']
                );
                
                // Get last message preview
                $lastMessage = $this->MessageModel->get_last_message($conversation['id']);
                $conversation['last_message'] = $lastMessage ? $lastMessage['message'] : '';
                $conversation['last_message_time'] = $lastMessage ? $lastMessage['timestamp'] : $conversation['created_at'];
            }
            
            echo json_encode([
                'status' => true,
                'conversations' => $conversations
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error fetching conversations: ' . $e->getMessage()
            ]);
        }
    }
}
?>
