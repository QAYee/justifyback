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
}
