<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserDashboardController extends CI_Controller {
    
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
        
        // Load models and helpers
        $this->load->model('User_model');
        $this->load->helper('file');
        $this->load->library('session');
    }
    
    /**
     * Get authenticated user details
     * 
     * @return JSON User data
     */
    public function getUserDetails() {
        header('Content-Type: application/json');
        
        // Get user ID from request
        $userId = $this->input->get('user_id');
        
        // If no user ID provided, try to get from request body
        if (!$userId) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, TRUE);
            $userId = isset($input['user_id']) ? $input['user_id'] : null;
        }
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'User ID is required'
            ]);
            return;
        }
        
        try {
            $user = $this->User_model->get_user_by_id($userId);
            
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
            
            // Format dates for consistency
            if (isset($user['created_at'])) {
                $user['created_at_formatted'] = date('M j, Y', strtotime($user['created_at']));
            }
            
            // Calculate age if birthdate is present but age is not
            if (isset($user['birthdate']) && !isset($user['age'])) {
                $birthdate = new DateTime($user['birthdate']);
                $today = new DateTime('today');
                $user['age'] = (string)$birthdate->diff($today)->y;
            }
            
            echo json_encode([
                'status' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error retrieving user'
            ]);
            log_message('error', 'Error in getUserDetails: ' . $e->getMessage());
        }
    }
    
    /**
     * Update user profile information
     * 
     * @return JSON Response with status
     */
    public function updateUser() {
        header('Content-Type: application/json');
        
        // Parse the input JSON
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid input data'
            ]);
            return;
        }
        
        // Get user ID from request
        $userId = isset($input['id']) ? $input['id'] : null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'User ID is required'
            ]);
            return;
        }
        
        try {
            // Validate required fields
            if (!isset($input['name']) || trim($input['name']) === '') {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Name is required'
                ]);
                return;
            }
            
            if (!isset($input['address']) || trim($input['address']) === '') {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Address is required'
                ]);
                return;
            }
            
            // Validate phone number if provided
            if (isset($input['phone']) && trim($input['phone']) !== '') {
                // Basic phone validation - adjust regex as needed for your requirements
                if (!preg_match('/^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/', $input['phone'])) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => false,
                        'message' => 'Invalid phone number format'
                    ]);
                    return;
                }
            }
            
            // Prepare update data
            $updateData = [
                'name' => $input['name'],
                'address' => $input['address']
            ];
            
            // Add optional fields if they exist
            if (isset($input['phone'])) $updateData['phone'] = $input['phone'];
            if (isset($input['birthdate'])) $updateData['birthdate'] = $input['birthdate'];
            if (isset($input['age'])) $updateData['age'] = $input['age'];
            
            // Update the user
            $result = $this->User_model->update_user($userId, $updateData);
            
            if ($result) {
                // Get the updated user data
                $updatedUser = $this->User_model->get_user_by_id($userId);
                
                // Remove sensitive data
                if (isset($updatedUser['password'])) {
                    unset($updatedUser['password']);
                }
                
                echo json_encode([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $updatedUser
                ]);
            } else {
                throw new Exception('Failed to update profile');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
            log_message('error', 'Error in updateUser: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload profile image
     * 
     * @return JSON Response with status
     */
    public function uploadProfileImage() {
        header('Content-Type: application/json');
        
        // Get user ID from POST data
        $userId = $this->input->post('user_id');
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'User ID is required'
            ]);
            return;
        }
        
        try {
            if (!isset($_FILES['profile_image'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'No image file uploaded'
                ]);
                return;
            }
            
            // Configure upload
            $config['upload_path'] = './uploads/images/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size'] = 2048; // 2MB
            $config['file_name'] = 'profile_' . $userId . '_' . time();
            
            // Make sure the directory exists
            if (!file_exists($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }
            
            // Load upload library
            $this->load->library('upload', $config);
            
            if (!$this->upload->do_upload('profile_image')) {
                $error = $this->upload->display_errors('', '');
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Image upload failed: ' . $error
                ]);
                return;
            }
            
            // Get upload data
            $uploadData = $this->upload->data();
            $imageName = $uploadData['file_name'];
            
            // Update user record with new image
            $updateData = ['image' => $imageName];
            $result = $this->User_model->update_user($userId, $updateData);
            
            if ($result) {
                echo json_encode([
                    'status' => true,
                    'message' => 'Profile image updated successfully',
                    'data' => [
                        'image' => $imageName,
                        'image_url' => base_url('uploads/images/' . $imageName)
                    ]
                ]);
            } else {
                // Delete the uploaded file if user update failed
                @unlink($uploadData['full_path']);
                throw new Exception('Failed to update profile with new image');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
            log_message('error', 'Error in uploadProfileImage: ' . $e->getMessage());
        }
    }
}