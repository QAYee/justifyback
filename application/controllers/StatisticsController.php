<?php

defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class StatisticsController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('StatisticsModel');
        $this->db->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    }
    
    public function complaints() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        // Get filter parameters
        $year = $this->input->get('year') ? (int)$this->input->get('year') : date('Y');
        $month = $this->input->get('month') ? $this->input->get('month') : null;
        
        try {
            // Get statistics data
            $total = $this->StatisticsModel->get_total_complaints($year, $month);
            $monthly = $this->StatisticsModel->get_monthly_complaints($year, $month);
            $by_status = $this->StatisticsModel->get_complaints_by_status($year, $month);
            $by_type = $this->StatisticsModel->get_complaints_by_type($year, $month);
            
            // Map numeric complaint types to readable names
            foreach ($by_type as &$item) {
                $item['type'] = $this->map_complaint_type($item['type']);
            }
            
            echo json_encode([
                'status' => true,
                'total' => $total,
                'monthly' => $monthly,
                'byStatus' => $by_status,
                'byType' => $by_type
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error retrieving complaint statistics: ' . $e->getMessage()
            ]);
        }
    }
    
    public function users() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        header("Content-Type: application/json");
        
        // Get filter parameters
        $year = $this->input->get('year') ? (int)$this->input->get('year') : date('Y');
        $month = $this->input->get('month') ? $this->input->get('month') : null;
        
        try {
            // Get statistics data
            $total = $this->StatisticsModel->get_total_users($year, $month);
            $monthly = $this->StatisticsModel->get_monthly_users($year, $month);
            $by_role = $this->StatisticsModel->get_users_by_role($year, $month);
            
            echo json_encode([
                'status' => true,
                'total' => $total,
                'monthly' => $monthly,
                'byRole' => $by_role
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error retrieving user statistics: ' . $e->getMessage()
            ]);
        }
    }
    
    private function map_complaint_type($type_id) {
        $complaint_types = [
            '1' => 'Noise Complaint',
            '2' => 'Property Dispute',
            '3' => 'Public Disturbance',
            '4' => 'Maintenance Issue',
            '5' => 'Other'
        ];
        
        return isset($complaint_types[$type_id]) ? $complaint_types[$type_id] : 'Unknown';
    }
}
?>
