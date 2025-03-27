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
        $this->load->library('form_validation');
        $this->load->helper('url');
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header("Content-Type: application/json");

        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Invalid request data']);
            return;
        }

        // Validate required fields
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('user_id', 'User ID', 'required|integer');
        $this->form_validation->set_rules('complainant', 'Complainant', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('respondent', 'Respondent', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('details', 'Complaint Details', 'required|trim|max_length[1000]');
        $this->form_validation->set_rules('incident_date', 'Date of Incident', 'required|callback_validate_date');
        $this->form_validation->set_rules('complaint_type', 'Complaint Type', 'required|integer');

        if ($this->form_validation->run() == FALSE) {
            http_response_code(422);
            echo json_encode(['status' => false, 'message' => reset($this->form_validation->error_array())]);
            return;
        }

        $incident_date = date('Y-m-d', strtotime($data['incident_date']));
        if (!$incident_date) {
            http_response_code(422);
            echo json_encode(['status' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
            return;
        }

        // Generate a unique reference number
    

        // Prepare complaint data
        $complaintData = [
            'user_id' => $data['user_id'],
            'complainant' => $data['complainant'],
            'respondent' => $data['respondent'],
            'details' => $data['details'],
            'incident_date' => $incident_date,
            'complaint_type' => $data['complaint_type'],
        
        ];

        try {
            $result = $this->ComplaintModel->insert_complaint($complaintData);

            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'status' => true, 
                    'message' => 'Complaint submitted successfully!',
                    
                ]);
            } else {
                throw new Exception('Database insert failed');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => false, 'message' => 'Failed to submit complaint. Try again.']);
        }
    }

    public function validate_date($date) {
        if (empty($date) || !strtotime($date)) {
            $this->form_validation->set_message('validate_date', 'Invalid Date of Incident.');
            return false;
        }
        return true;
    }

    public function getAllComplaints() {
    header("Content-Type: application/json");

    // Get user_id from query parameters
    $user_id = $this->input->get('user_id');

    // Validate user_id if provided
    if ($user_id !== null) {
        if (!is_numeric($user_id)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
            return;
        }
        $complaints = $this->ComplaintModel->get_user_complaints($user_id);
    } else {
        $complaints = $this->ComplaintModel->get_all_complaints();
    }
    
    if ($complaints) {
        echo json_encode(['status' => true, 'complaints' => $complaints]);
    } else {
        echo json_encode(['status' => false, 'message' => 'No complaints found']);
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
            // Begin transaction
            $this->db->trans_start();

            // Get current status before update
            $old_status = $this->ComplaintModel->getComplaintStatus($data['complaint_id']);
            
            // Update complaint status
            $result = $this->ComplaintModel->updateStatus(
                $data['complaint_id'],
                $data['status']
            );

            // Log the status change
            $log_data = [
                'complaint_id' => $data['complaint_id'],
                'old_status' => $old_status,
                'new_status' => $data['status'],
                'changed_by' => $this->session->userdata('user_id') ?? 1,
                'changed_at' => date('Y-m-d H:i:s')
            ];
            
            $this->ComplaintModel->logStatusChange($log_data);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Failed to update status');
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>
