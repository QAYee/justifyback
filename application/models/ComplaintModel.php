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
            $data['incident_date'] = date('Y-m-d H:i:s', strtotime($data['incident_date']));
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
    public function get_user_complaints($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('complaints');  // Replace 'complaints' with your actual table name
        return $query->result_array();
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
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('complaints');
        return $query->result_array();
    }

    /**
     * Update the status of a complaint
     * 
     * @param int $complaint_id The ID of the complaint
     * @param string $new_status The new status to set
     * @return bool True if updated successfully, false otherwise
     */
    public function updateStatus($complaint_id, $new_status) {
        $this->db->where('id', $complaint_id);
        $this->db->update('complaints', ['status' => $new_status]);
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'Status Update Error: ' . $this->db->last_query());
            return false;
        }
    }

    /**
     * Get the current status of a complaint
     * 
     * @param int $complaint_id The ID of the complaint
     * @return string|null The current status or null if not found
     */
    public function getComplaintStatus($complaint_id) {
        $this->db->select('status');
        $this->db->where('id', $complaint_id);
        $query = $this->db->get('complaints');
        $result = $query->row();
        
        return $result ? $result->status : null;
    }

    /**
     * Log a status change in the status_changes table
     * 
     * @param array $data Status change data
     * @return bool True if logged successfully, false otherwise
     */
    public function logStatusChange($data) {
        $log_data = [
            'complaint_id' => $data['complaint_id'],
            'old_status' => $data['old_status'],
            'new_status' => $data['new_status'],
            'changed_by' => $data['changed_by'],
            'changed_at' => $data['changed_at']
        ];

        $this->db->insert('status_changes', $log_data);
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'Status Log Error: ' . $this->db->last_query());
            return false;
        }
    }
    
}
?>
