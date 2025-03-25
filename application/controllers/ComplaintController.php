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
    }
    public function create() {
        // Handle CORS OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        header("Content-Type: application/json");
    
        // Get JSON input data
        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);
    
        // Validate input data
        if (!$data || !is_array($data)) {
            log_message('error', 'Invalid input data: ' . $inputJSON);
            http_response_code(400);
            echo json_encode([
                'status' => false, 
                'message' => 'Invalid request data'
            ]);
            return;
        }
    
        // Validate required fields
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('user_id', 'User ID', 'required|integer');
        $this->form_validation->set_rules('complainant', 'Complainant', 'required|trim|max_length[100]'); // ✅ Added complainant validation
        $this->form_validation->set_rules('respondent', 'Respondent', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('details', 'Complaint Details', 'required|trim|max_length[1000]');
        $this->form_validation->set_rules('incident_date', 'Date of Incident', 'required|callback_validate_date');
    
        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            log_message('error', 'Validation failed: ' . json_encode($errors));
            
            http_response_code(422);
            echo json_encode([
                'status' => false,
                'message' => reset($errors) // Send first error
            ]);
            return;
        }
    
        // Ensure 'incident_date' is a valid string before using strtotime
        if (!isset($data['incident_date']) || empty($data['incident_date'])) {
            log_message('error', 'Incident date is missing or empty.');
            http_response_code(422);
            echo json_encode([
                'status' => false,
                'message' => 'The Date of Incident is required and must be valid.'
            ]);
            return;
        }
    
        // Convert date safely
        $incident_date = date('Y-m-d', strtotime($data['incident_date']));
        if (!$incident_date) {
            log_message('error', 'Invalid date format provided: ' . $data['incident_date']);
            http_response_code(422);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid date format. Please use YYYY-MM-DD.'
            ]);
            return;
        }
    
        // Prepare complaint data
        $complaintData = [
            'user_id' => $data['user_id'],
            'complainant' => $data['complainant'], // ✅ Now storing complainant's name
            'respondent' => $data['respondent'],
            'details' => $data['details'],
            'incident_date' => $incident_date
        ];
    
        // Attempt to insert complaint
        try {
            $result = $this->ComplaintModel->insert_complaint($complaintData);
            
            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'status' => true, 
                    'message' => 'Complaint submitted successfully'
                ]);
            } else {
                throw new Exception('Database insert failed');
            }
        } catch (Exception $e) {
            log_message('error', 'Complaint submission error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => false, 
                'message' => 'Failed to submit complaint. Please try again.'
            ]);
        }
    }
    
    public function validate_date($date) {
        if (empty($date) || !strtotime($date)) {
            $this->form_validation->set_message('validate_date', 'The Date of Incident is not a valid date.');
            return false;
        }
        return true;
    }
    
    public function getAllComplaints() {
        header("Content-Type: application/json");
    
        $complaints = $this->ComplaintModel->get_all_complaints();
        
        if ($complaints) {
            echo json_encode([
                'status' => true,
                'complaints' => $complaints
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'No complaints found'
            ]);
        }
    }
    
    
}
?>
