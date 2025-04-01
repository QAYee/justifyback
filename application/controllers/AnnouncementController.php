<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class AnnouncementController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('AnnouncementModel');
        $this->load->model('User_model'); // Load the user model
        $this->load->library(['form_validation', 'upload']);
        $this->load->helper(['url', 'file']);
    }

    public function addAnnouncement() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        header("Content-Type: application/json");
    
        // Validate required fields
        if (!isset($_POST['title']) || !isset($_POST['description'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Title and description are required'
            ]);
            return;
        }
    
        // Check for recipients
        $target_all = isset($_POST['target_all']) ? (bool)$_POST['target_all'] : false;
        $recipient_ids = isset($_POST['recipient_ids']) ? json_decode($_POST['recipient_ids'], true) : [];
        
        // Validate recipients
        if (!$target_all && empty($recipient_ids)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Please select recipients or choose to target all users'
            ]);
            return;
        }
    
        // Prepare announcement data
        $announcementData = [
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'contentType' => 'announcement',
            'target_all' => $target_all ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
    
        // Handle image upload if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $config['upload_path'] = './uploads/announcements/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size'] = 2048; // 2MB max
            $config['file_name'] = uniqid('announcement_');
    
            // Create directory if it doesn't exist
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }
    
            $this->upload->initialize($config);
    
            if (!$this->upload->do_upload('image')) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Image upload failed: ' . $this->upload->display_errors('', '')
                ]);
                return;
            }
    
            $upload_data = $this->upload->data();
            $announcementData['image'] = base_url('uploads/announcements/' . $upload_data['file_name']);
        }
    
        try {
            $this->db->trans_begin(); // Start transaction
            
            // Insert announcement
            $announcement_id = $this->AnnouncementModel->insert_announcement($announcementData);
    
            if (!$announcement_id) {
                throw new Exception('Failed to create announcement');
            }
            
            // If targeting specific users, add recipients
            if (!$target_all && !empty($recipient_ids)) {
                foreach ($recipient_ids as $user_id) {
                    $this->AnnouncementModel->add_recipient($announcement_id, $user_id);
                }
            } else if ($target_all) {
                // If targeting all users, get all valid user IDs and add them
                $all_users = $this->User_model->get_users();
                foreach ($all_users as $user) {
                    $this->AnnouncementModel->add_recipient($announcement_id, $user['id']);
                }
            }
            
            $this->db->trans_commit(); // Commit transaction
            
            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Announcement created successfully',
                'announcement' => array_merge($announcementData, ['id' => $announcement_id])
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback(); // Rollback on error
            
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getAnnouncements() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        try {
            $announcements = $this->AnnouncementModel->get_all_announcements();
            
            // Add recipient information
            foreach ($announcements as &$announcement) {
                if ($announcement['target_all'] == 1) {
                    $announcement['recipients'] = 'All Users';
                    $announcement['recipient_count'] = $this->User_model->get_users_count();
                } else {
                    $recipients = $this->AnnouncementModel->get_announcement_recipients($announcement['id']);
                    $announcement['recipient_count'] = count($recipients);
                    
                    // Add simplified recipient list (first 5)
                    $recipient_names = [];
                    foreach (array_slice($recipients, 0, 5) as $recipient) {
                        $user = $this->User_model->get_user_by_id($recipient['user_id']);
                        if ($user) {
                            $recipient_names[] = $user['name'];
                        }
                    }
                    $announcement['recipient_preview'] = $recipient_names;
                    
                    if (count($recipients) > 5) {
                        $announcement['recipient_preview'][] = '...and ' . (count($recipients) - 5) . ' more';
                    }
                }
            }
            
            echo json_encode([
                'status' => true,
                'announcements' => $announcements
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getUserAnnouncements($user_id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        // Validate user_id
        if (!$user_id) {
            $user_id = $this->input->get('user_id');
        }
        
        if (!$user_id) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'User ID is required'
            ]);
            return;
        }
        
        try {
            // Get announcements for this user
            $announcements = $this->AnnouncementModel->get_user_announcements($user_id);
            
            echo json_encode([
                'status' => true,
                'announcements' => $announcements
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function markAsRead() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
        
        if (!isset($input['announcement_id']) || !isset($input['user_id'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Announcement ID and User ID are required'
            ]);
            return;
        }
        
        try {
            $result = $this->AnnouncementModel->mark_announcement_read(
                $input['announcement_id'],
                $input['user_id']
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Announcement marked as read'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateAnnouncement() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        header("Content-Type: application/json");
    
        // Validate required fields
        if (!isset($_POST['id']) || !isset($_POST['title']) || !isset($_POST['description'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'ID, title and description are required'
            ]);
            return;
        }
    
        $announcement_id = $this->input->post('id');
        
        // Check if announcement exists
        $existing_announcement = $this->AnnouncementModel->get_announcement_by_id($announcement_id);
        if (!$existing_announcement) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Announcement not found'
            ]);
            return;
        }
    
        // Prepare update data
        $updateData = [
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    
        // Handle image upload if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $config['upload_path'] = './uploads/announcements/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size'] = 2048; // 2MB max
            $config['file_name'] = uniqid('announcement_');
    
            // Create directory if it doesn't exist
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }
    
            $this->upload->initialize($config);
    
            if (!$this->upload->do_upload('image')) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Image upload failed: ' . $this->upload->display_errors('', '')
                ]);
                return;
            }
    
            $upload_data = $this->upload->data();
            $updateData['image'] = base_url('uploads/announcements/' . $upload_data['file_name']);
            
            // Delete old image file if exists
            if (!empty($existing_announcement['image'])) {
                $old_image_path = str_replace(base_url(), FCPATH, $existing_announcement['image']);
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        }
    
        try {
            $result = $this->AnnouncementModel->update_announcement($announcement_id, $updateData);
    
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Announcement updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update announcement');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deleteAnnouncement() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        header("Content-Type: application/json");
    
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
    
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Announcement ID is required'
            ]);
            return;
        }
    
        $announcement_id = $input['id'];
        
        // Get announcement data for image deletion
        $announcement = $this->AnnouncementModel->get_announcement_by_id($announcement_id);
        
        try {
            $result = $this->AnnouncementModel->delete_announcement($announcement_id);
    
            if ($result) {
                // Delete image file if exists
                if ($announcement && !empty($announcement['image'])) {
                    $image_path = str_replace(base_url(), FCPATH, $announcement['image']);
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Announcement deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete announcement');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Add a new method to get all users for recipient selection
    public function getUsers() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        try {
            $users = $this->User_model->get_users();
            
            // Only return necessary user info
            $simplified_users = [];
            foreach ($users as $user) {
                $simplified_users[] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ];
            }
            
            echo json_encode([
                'status' => true,
                'users' => $simplified_users
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}


