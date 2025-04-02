<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Ensure database is loaded
    }

    // Check if email already exists
    public function check_email_exists($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        return $query->num_rows() > 0; // Returns true if email exists
    }

    // Insert a new user
    public function insert_user($data) {
        $this->db->insert('users', $data);
        return $this->db->affected_rows() > 0; // Return true if insert successful
    }

    // Fetch all users
    public function get_users() {
        return $this->db->get('users')->result_array();
    }

    // Fetch a single user by ID
    public function get_user_id($id) {
        $this->db->where('id', $id);
        $this->db->where('Valid', 1);
        $query = $this->db->get('users');
        return $query->result();
    }

    // Update user details
  
    public function get_user_by_email($email) {
        $query = $this->db->get_where('users', ['email' => $email]);
        return $query->row_array(); // ✅ Ensures it returns an array
    }

    // Fetch user by ID
    public function get_user_by_id($user_id) {
        $query = $this->db->select('name')
                          ->from('users')
                          ->where('id', $user_id)
                          ->get();

        return $query->row_array(); // Return user data as an associative array
    }
    
    /**
     * Get total count of users
     * 
     * @return int Count of users
     */
    public function get_users_count() {
        return $this->db->count_all_results('users');
    }

    /**
     * Get all users with filtering and pagination
     *
     * @param string|null $search Search term to filter by name/email
     * @param int $page Page number
     * @param int $limit Users per page
     * @param string $sort_by Column to sort by
     * @param string $sort_dir Sort direction (asc/desc)
     * @return array List of users
     */
    public function get_all_users($search = null, $page = 1, $limit = 100, $sort_by = 'name', $sort_dir = 'asc') {
        // Validate sort parameters
        $allowed_sort_fields = ['id', 'name', 'email', 'created_at'];
        $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'name';
        $sort_dir = strtolower($sort_dir) === 'desc' ? 'DESC' : 'ASC';
        
        // Apply search filter if provided
        if ($search) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('email', $search);
            $this->db->group_end();
        }
        
        // Apply pagination
        if ($page > 0 && $limit > 0) {
            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);
        }
        
        // Order results
        $this->db->order_by($sort_by, $sort_dir);
        
        // Get results
        $query = $this->db->get('users');
        return $query->result_array();
    }

    /**
     * Count total users with filtering
     *
     * @param string|null $search Search term to filter by name/email
     * @return int Total count of users
     */
    public function count_all_users($search = null) {
        // Apply search filter if provided
        if ($search) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('email', $search);
            $this->db->group_end();
        }
        
        return $this->db->count_all_results('users');
    }
}
