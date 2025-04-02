<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        // Load models
        $this->load->model('User_model');
        $this->load->model('MessageModel'); // Only if needed
    }
    
    /**
     * Get all users for admin view
     *
     * This endpoint retrieves all users with optional filtering and pagination support.
     * 
     * @return JSON Response with user data
     */
    public function getAllUsers() {
        header('Content-Type: application/json');
        
        // TODO: Add authentication check to ensure only admins can access this endpoint
        // if (!$this->isAdmin()) {
        //     http_response_code(403);
        //     echo json_encode([
        //         'status' => false,
        //         'message' => 'Access denied. Admin privileges required.'
        //     ]);
        //     return;
        // }
        
        try {
            // Get query parameters for filtering and pagination
            $search = $this->input->get('search');
            $page = (int)$this->input->get('page') ?: 1;
            $limit = (int)$this->input->get('limit') ?: 100; // Default to 100 users per page
            $sort_by = $this->input->get('sort') ?: 'name';
            $sort_dir = $this->input->get('dir') ?: 'asc';
            
            // Get users from model
            $users = $this->User_model->get_all_users($search, $page, $limit, $sort_by, $sort_dir);
            
            // Get total users count for pagination
            $total = $this->User_model->count_all_users($search);
            
            // Process users to include additional data if needed
            foreach ($users as &$user) {
                // Remove sensitive data
                if (isset($user['password'])) {
                    unset($user['password']);
                }
                
                // Add additional fields if needed
                if (method_exists($this->MessageModel, 'user_has_conversation')) {
                    $user['has_conversation'] = $this->MessageModel->user_has_conversation($user['id']);
                }
                
                // Format dates nicely
                if (isset($user['created_at'])) {
                    $user['created_at_formatted'] = date('M j, Y', strtotime($user['created_at']));
                }
            }
            
            echo json_encode([
                'status' => true,
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ]);
            log_message('error', 'Error in getAllUsers: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a specific user by ID
     *
     * @param int $id User ID
     * @return JSON User data
     */
    public function getUser($id = null) {
        header('Content-Type: application/json');
        
        // If no ID provided in URL, try to get from query string
        if (!$id) {
            $id = $this->input->get('id');
        }
        
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid or missing user ID'
            ]);
            return;
        }
        
        try {
            $user = $this->User_model->get_user_by_id($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'User not found'
                ]);
                return;
            }
            
            // Remove sensitive data
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            echo json_encode([
                'status' => true,
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error retrieving user: ' . $e->getMessage()
            ]);
            log_message('error', 'Error in getUser: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper method to check if current user is admin
     * Implement according to your auth system
     */
    private function isAdmin() {
        // Example implementation - customize based on your auth system
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) return false;
        
        $user = $this->User_model->get_user_by_id($user_id);
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }
}
?>