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
            
            // Get daily data if month is specified
            $daily = null;
            if ($month) {
                $daily = $this->StatisticsModel->get_daily_complaints($year, $month);
            }
            
            // Map numeric complaint types to readable names
            foreach ($by_type as &$item) {
                $item['type'] = $this->map_complaint_type($item['type']);
            }
            
            $response = [
                'status' => true,
                'total' => $total,
                'monthly' => $monthly,
                'byStatus' => $by_status,
                'byType' => $by_type
            ];
            
            // Add daily data to response if available
            if ($daily !== null) {
                $response['daily'] = $daily;
            }
            
            echo json_encode($response);
            
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
            
            // Get daily data if month is specified
            $daily = null;
            if ($month) {
                $daily = $this->StatisticsModel->get_daily_users($year, $month);
            }
            
            $response = [
                'status' => true,
                'total' => $total,
                'monthly' => $monthly,
                'byRole' => $by_role
            ];
            
            // Add daily data to response if available
            if ($daily !== null) {
                $response['daily'] = $daily;
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error retrieving user statistics: ' . $e->getMessage()
            ]);
        }
    }
    
    private function map_complaint_type($type_name) {
        // List of valid complaint types
        $valid_complaint_types = [
            'Noise Complaint',
            'Property Dispute',
            'Public Disturbance',
            'Utility Issue',
            'Environmental Concern',
            'Vandalism',
            'Illegal Construction',
            'Parking Violation',
            'Animal Complaint',
            'Others'
        ];
        
        // Trim and standardize the input
        $type_name = trim($type_name);
        
        // Direct match - if the type name is already a valid complaint type (case-insensitive)
        foreach ($valid_complaint_types as $valid_type) {
            if (strcasecmp($type_name, $valid_type) == 0) {
                return $valid_type; // Return with proper capitalization
            }
        }
        
        // Partial match for cases like "property" matching "Property Dispute"
        $type_lower = strtolower($type_name);
        $mapping = [
            'noise' => 'Noise Complaint',
            'property' => 'Property Dispute',
            'disturbance' => 'Public Disturbance',
            'utility' => 'Utility Issue',
            'environmental' => 'Environmental Concern',
            'vandalism' => 'Vandalism',
            'construction' => 'Illegal Construction',
            'parking' => 'Parking Violation',
            'animal' => 'Animal Complaint',
            'other' => 'Others'
        ];
        
        foreach ($mapping as $key => $value) {
            if (strpos($type_lower, $key) !== false) {
                return $value;
            }
        }
        
        return 'Others';
    }
}
?>
