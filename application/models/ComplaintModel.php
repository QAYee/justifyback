<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ComplaintModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Load database
    }

    /**
     * Insert a new complaint into the database.
     * 
     * @param array $data Complaint data (user_id, complainant, respondent, details, incident_date)
     * @return bool True if inserted successfully, false otherwise
     */
    public function insert_complaint($data) {
        // Ensure incident_date is correctly formatted
        if (isset($data['incident_date'])) {
            $data['incident_date'] = date('Y-m-d', strtotime($data['incident_date']));
        }

        $this->db->insert('complaints', $data);
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'DB Insert Error: ' . $this->db->last_query()); // Log failed query
            return false;
        }
    }

    /**
     * Retrieve all complaints for a given user.
     * 
     * @param int $user_id The ID of the user
     * @return array List of complaints
     */
    public function get_complaints_by_user($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('complaints');
        return $query->result_array();
    }

    /**
     * Retrieve a specific complaint by ID.
     * 
     * @param int $complaint_id The ID of the complaint
     * @return array|null Complaint data or null if not found
     */
    public function get_complaint_by_id($complaint_id) {
        $this->db->where('id', $complaint_id);
        $query = $this->db->get('complaints');
        return $query->row_array();
    }

    /**
     * Delete a complaint by ID.
     * 
     * @param int $complaint_id The ID of the complaint
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete_complaint($complaint_id) {
        $this->db->where('id', $complaint_id);
        $this->db->delete('complaints');
        return $this->db->affected_rows() > 0;
    }

    public function get_all_complaints() {
        return $this->db->get('complaints')->result_array();
    }
    
}
?>
