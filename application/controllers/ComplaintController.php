<?php

defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class ComplaintController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('ComplaintModel');
        $this->load->library(['form_validation', 'upload']);
        $this->load->helper(['url', 'file']);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header("Content-Type: application/json");

        // Get form data
        $user_id = $this->input->post('user_id');
        $complainant = $this->input->post('complainant');
        $respondent = $this->input->post('respondent');
        $details = $this->input->post('details');
        $incident_date = $this->input->post('incident_date');
        $complaint_type = $this->input->post('complaint_type');

        // Validate required fields
        if (!$user_id || !$complainant || !$respondent || !$details || !$incident_date || !$complaint_type) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'All required fields must be filled']);
            return;
        }

        // Handle image upload if present
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $config['upload_path'] = './uploads/complaints/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size'] = 2048; // 2MB max
            $config['file_name'] = uniqid('complaint_');

            // Create directory if it doesn't exist
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('image')) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Image upload failed: ' . $this->upload->display_errors('', '')
                ]);
                return;
            }

            $upload_data = $this->upload->data();
            $image = $upload_data['file_name'];
        }

        try {
            // Prepare complaint data
            $complaintData = [
                'user_id' => $user_id,
                'complainant' => $complainant,
                'respondent' => $respondent,
                'details' => $details,
                'incident_date' => $incident_date,
                'complaint_type' => $complaint_type,
                'image' => $image,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->ComplaintModel->insert_complaint($complaintData);

            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'status' => true,
                    'message' => 'Complaint submitted successfully!',
                    'complaint_id' => $result,
                    'image_url' => $image ? base_url('uploads/complaints/' . $image) : null
                ]);
            } else {
                throw new Exception('Failed to insert complaint');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Failed to submit complaint: ' . $e->getMessage()
            ]);
        }
    }

    public function getAllComplaints() {
        header("Content-Type: application/json");

        // Get query parameters
        $user_id = $this->input->get('user_id');
        $status = $this->input->get('status');
        $type = $this->input->get('type');

        try {
            if ($user_id !== null) {
                if (!is_numeric($user_id)) {
                    throw new Exception('Invalid user ID');
                }
                $complaints = $this->ComplaintModel->get_user_complaints($user_id, $status, $type);
            } else {
                $complaints = $this->ComplaintModel->get_all_complaints($status, $type);
            }

            // Format image URLs and dates
            foreach ($complaints as &$complaint) {
                if (!empty($complaint['image'])) {
                    $complaint['image_url'] = base_url('uploads/complaints/' . $complaint['image']);
                }
                $complaint['incident_date'] = date('Y-m-d', strtotime($complaint['incident_date']));
                $complaint['created_at'] = date('Y-m-d H:i:s', strtotime($complaint['created_at']));
            }

            echo json_encode([
                'status' => true,
                'complaints' => $complaints
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header("Content-Type: application/json");

        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        // Validate input
        if (!isset($data['complaint_id']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }

        // Validate status value
        $valid_statuses = ['pending', 'processing', 'resolved', 'rejected'];
        if (!in_array($data['status'], $valid_statuses)) {
            http_response_code(422);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid status value'
            ]);
            return;
        }

        try {
            $result = $this->ComplaintModel->updateStatus(
                $data['complaint_id'],
                $data['status']
            );

            if ($result) {
                echo json_encode([
                    'status' => true,
                    'message' => 'Status updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update status');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function validate_date($date) {
        return !empty($date) && strtotime($date) !== false;
    }
}
?>
