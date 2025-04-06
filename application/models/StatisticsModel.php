<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class StatisticsModel extends CI_Model {
    
    /**
     * Get total number of complaints
     */
    public function get_total_complaints($year, $month = null) {
        if ($year) {
            $this->db->where('YEAR(created_at)', $year);
        }
        
        if ($month) {
            // Convert month name to number if provided as name
            $month_num = date('n', strtotime("1 $month"));
            $this->db->where('MONTH(created_at)', $month_num);
        }
        
        return $this->db->count_all_results('complaints');
    }
    
    /**
     * Get monthly complaint counts
     */
    public function get_monthly_complaints($year, $month = null) {
        // Use a simpler query approach to avoid GROUP BY issues
        $sql = "SELECT MONTH(created_at) as month_num, 
                LEFT(MONTHNAME(created_at), 3) as month, 
                COUNT(*) as count
                FROM complaints
                WHERE YEAR(created_at) = ?";
        
        $params = [$year];
        
        if ($month) {
            $month_num = date('n', strtotime("1 $month"));
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month_num;
        }
        
        $sql .= " GROUP BY month_num, month 
                  ORDER BY month_num ASC";
        
        $query = $this->db->query($sql, $params);
        $result = $query->result_array();
        
        // Ensure all months are represented with zero counts
        $all_months = [];
        $month_abbr = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        foreach ($month_abbr as $i => $abbr) {
            $found = false;
            $month_num = $i + 1;
            
            foreach ($result as $row) {
                if ((int)$row['month_num'] === $month_num) {
                    $all_months[] = [
                        'month' => $abbr,
                        'count' => (int)$row['count']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $all_months[] = [
                    'month' => $abbr,
                    'count' => 0
                ];
            }
        }
        
        return $all_months;
    }
    
    /**
     * Get complaints by status
     */
    public function get_complaints_by_status($year, $month = null) {
        $sql = "SELECT status, COUNT(*) as count 
                FROM complaints 
                WHERE YEAR(created_at) = ?";
                
        $params = [$year];
        
        if ($month) {
            $month_num = date('n', strtotime("1 $month"));
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month_num;
        }
        
        $sql .= " GROUP BY status";
        
        $query = $this->db->query($sql, $params);
        $result = $query->result_array();
        
        // Make sure all statuses are represented
        $statuses = ['New', 'Under review', 'In progress', 'Resolved', 'Closed', 'Rejected'];
        $formatted_result = [];
        
        foreach ($statuses as $status) {
            $found = false;
            foreach ($result as $row) {
                if ($row['status'] === $status) {
                    $formatted_result[] = [
                        'status' => $status,
                        'count' => (int)$row['count']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $formatted_result[] = [
                    'status' => $status,
                    'count' => 0
                ];
            }
        }
        
        return $formatted_result;
    }
    
    /**
     * Get complaints by type
     */
    public function get_complaints_by_type($year, $month = null) {
        $sql = "SELECT complaint_type as type, COUNT(*) as count 
                FROM complaints 
                WHERE YEAR(created_at) = ?";
                
        $params = [$year];
        
        if ($month) {
            $month_num = date('n', strtotime("1 $month"));
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month_num;
        }
        
        $sql .= " GROUP BY type";
        
        $query = $this->db->query($sql, $params);
        $result = $query->result_array();
        
        // Define expected complaint types based on the controller's valid types
        $expected_types = [
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
        
        // Make sure all types are represented
        $formatted_result = [];
        $others_count = 0;
        $processed_rows = [];
        
        // First, process all known types
        foreach ($expected_types as $type) {
            if ($type === 'Others') continue; // Skip Others for now, we'll add it at the end
            
            $found = false;
            foreach ($result as $index => $row) {
                // Compare case-insensitive
                if (strcasecmp($row['type'], $type) == 0) {
                    $formatted_result[] = [
                        'type' => $type, // Use standardized type name
                        'count' => (int)$row['count']
                    ];
                    $found = true;
                    $processed_rows[] = $index;
                    break;
                }
            }
            
            if (!$found) {
                $formatted_result[] = [
                    'type' => $type,
                    'count' => 0
                ];
            }
        }
        
        // Now add up all the complaint types that don't match our expected types into "Others"
        foreach ($result as $index => $row) {
            if (!in_array($index, $processed_rows)) {
                $others_count += (int)$row['count'];
            }
        }
        
        // Add Others to the formatted result
        $formatted_result[] = [
            'type' => 'Others',
            'count' => $others_count
        ];
        
        return $formatted_result;
    }
    
    /**
     * Get total number of users
     */
    public function get_total_users($year, $month = null) {
        if ($year) {
            $this->db->where('YEAR(created_at)', $year);
        }
        
        if ($month) {
            // Convert month name to number if provided as name
            $month_num = date('n', strtotime("1 $month"));
            $this->db->where('MONTH(created_at)', $month_num);
        }
        
        return $this->db->count_all_results('users');
    }
    
    /**
     * Get monthly user registrations
     */
    public function get_monthly_users($year, $month = null) {
        // Use a simpler query approach to avoid GROUP BY issues
        $sql = "SELECT MONTH(created_at) as month_num, 
                LEFT(MONTHNAME(created_at), 3) as month, 
                COUNT(*) as count
                FROM users
                WHERE YEAR(created_at) = ?";
        
        $params = [$year];
        
        if ($month) {
            $month_num = date('n', strtotime("1 $month"));
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month_num;
        }
        
        $sql .= " GROUP BY month_num, month 
                  ORDER BY month_num ASC";
        
        $query = $this->db->query($sql, $params);
        $result = $query->result_array();
        
        // Ensure all months are represented with zero counts
        $all_months = [];
        $month_abbr = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        foreach ($month_abbr as $i => $abbr) {
            $found = false;
            $month_num = $i + 1;
            
            foreach ($result as $row) {
                if ((int)$row['month_num'] === $month_num) {
                    $all_months[] = [
                        'month' => $abbr,
                        'count' => (int)$row['count']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $all_months[] = [
                    'month' => $abbr,
                    'count' => 0
                ];
            }
        }
        
        return $all_months;
    }
    
    /**
     * Get users by role
     */
    public function get_users_by_role($year, $month = null) {
        // Check if the 'admin' column exists instead of 'role'
        $table_info = $this->db->query("SHOW COLUMNS FROM users LIKE 'admin'");
        $admin_column_exists = $table_info->num_rows() > 0;
        
        if (!$admin_column_exists) {
            // Return default data if neither 'role' nor 'admin' column exists
            return [
                ['role' => 'Admin', 'count' => 0],
                ['role' => 'User', 'count' => 0]
            ];
        }
        
        // Build query based on admin column
        $sql = "SELECT 
                    CASE 
                        WHEN admin = 1 THEN 'Admin' 
                        ELSE 'User' 
                    END as role, 
                    COUNT(*) as count 
                FROM users 
                WHERE YEAR(created_at) = ?";
                
        $params = [$year];
        
        if ($month) {
            $month_num = date('n', strtotime("1 $month"));
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month_num;
        }
        
        $sql .= " GROUP BY admin";
        
        $query = $this->db->query($sql, $params);
        $result = $query->result_array();
        
        // Make sure all roles are represented
        // Note: We've removed 'Staff' since your schema only has Admin and User
        $roles = ['Admin', 'User'];
        $formatted_result = [];
        
        foreach ($roles as $role) {
            $found = false;
            foreach ($result as $row) {
                if ($row['role'] === $role) {
                    $formatted_result[] = [
                        'role' => $role,
                        'count' => (int)$row['count']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $formatted_result[] = [
                    'role' => $role,
                    'count' => 0
                ];
            }
        }
        
        return $formatted_result;
    }

    /**
     * Get daily complaint statistics for a specific year and month
     */
    public function get_daily_complaints($year, $month) {
        // Convert month name to month number
        $month_num = date('m', strtotime("1 $month"));
        
        $this->db->select("DAY(created_at) as day, DATE_FORMAT(created_at, '%d %b') as date, COUNT(*) as count");
        $this->db->from('complaints');
        $this->db->where('YEAR(created_at)', $year);
        $this->db->where('MONTH(created_at)', $month_num);
        $this->db->group_by('DAY(created_at)');
        $this->db->order_by('DAY(created_at)', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get daily user registration statistics for a specific year and month
     */
    public function get_daily_users($year, $month) {
        // Convert month name to month number
        $month_num = date('m', strtotime("1 $month"));
        
        $this->db->select("DAY(created_at) as day, DATE_FORMAT(created_at, '%d %b') as date, COUNT(*) as count");
        $this->db->from('users');
        $this->db->where('YEAR(created_at)', $year);
        $this->db->where('MONTH(created_at)', $month_num);
        $this->db->group_by('DAY(created_at)');
        $this->db->order_by('DAY(created_at)', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
}
?>
