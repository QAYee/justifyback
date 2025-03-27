<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class ChatController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('ChatModel');
    }

    public function getMessages($complaintId = null) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        if (!$complaintId || !is_numeric($complaintId)) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Complaint ID'
            ]);
            return;
        }
    
        try {
            $messages = $this->ChatModel->get_messages_by_complaint($complaintId);
            echo json_encode([
                'status' => true,
                'messages' => $messages ?: []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error fetching messages: ' . $e->getMessage()
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
            !isset($input['complaint_id']) || 
            !isset($input['user_id']) || 
            !isset($input['message']) || 
            !isset($input['sender'])) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }
    
        $messageData = [
            'complaint_id' => (int)$input['complaint_id'],
            'user_id' => (int)$input['user_id'],
            'message' => trim($input['message']),
            'sender' => trim($input['sender']),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    
        try {
            $result = $this->ChatModel->insert_message($messageData);
    
            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'status' => true,
                    'message' => 'Message sent successfully',
                    'data' => array_merge($messageData, ['id' => $result])
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
}
?>
