<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AnnouncementModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Insert a new announcement into the database
     * 
     * @param array $data Announcement data
     * @return int|bool The inserted ID on success, false on failure
     */
    public function insert_announcement($data) {
        $this->db->insert('announcements', $data);
        
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        } else {
            log_message('error', 'Announcement Insert Error: ' . $this->db->last_query());
            return false;
        }
    }

    /**
     * Get all announcements ordered by most recent first
     * 
     * @return array List of announcements
     */
    public function get_all_announcements() {
        $this->db->where('contentType', 'announcement');
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get('announcements');
        return $query->result_array();
    }

    /**
     * Get a specific announcement by ID
     * 
     * @param int $id Announcement ID
     * @return array|null Announcement data or null if not found
     */
    public function get_announcement_by_id($id) {
        $this->db->where('id', $id);
        $this->db->where('contentType', 'announcement');
        $query = $this->db->get('announcements');
        
        $result = $query->row_array();
        return $result ?: null;
    }

    /**
     * Update an existing announcement
     * 
     * @param int $id Announcement ID
     * @param array $data Updated announcement data
     * @return bool True on success, false on failure
     */
    public function update_announcement($id, $data) {
        $this->db->where('id', $id);
        $this->db->where('contentType', 'announcement');
        $this->db->update('announcements', $data);
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'Announcement Update Error: ' . $this->db->last_query());
            return false;
        }
    }

    /**
     * Delete an announcement
     * 
     * @param int $id Announcement ID
     * @return bool True on success, false on failure
     */
    public function delete_announcement($id) {
        $this->db->where('id', $id);
        $this->db->where('contentType', 'announcement');
        $this->db->delete('announcements');
        
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'Announcement Delete Error: ' . $this->db->last_query());
            return false;
        }
    }

    /**
     * Count total announcements
     * 
     * @return int Total count of announcements
     */
    public function count_announcements() {
        $this->db->where('contentType', 'announcement');
        return $this->db->count_all_results('announcements');
    }
    
    /**
     * Add a recipient for an announcement
     * 
     * @param int $announcement_id
     * @param int $user_id
     * @return bool
     */
    public function add_recipient($announcement_id, $user_id) {
        $data = [
            'announcement_id' => $announcement_id,
            'user_id' => $user_id,
            'is_read' => 0
        ];
        
        $this->db->insert('announcement_recipients', $data);
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Get all recipients for an announcement
     * 
     * @param int $announcement_id
     * @return array
     */
    public function get_announcement_recipients($announcement_id) {
        $this->db->where('announcement_id', $announcement_id);
        $query = $this->db->get('announcement_recipients');
        return $query->result_array();
    }
    
    /**
     * Get all announcements for a specific user
     * 
     * @param int $user_id
     * @return array
     */
    public function get_user_announcements($user_id) {
        // Either user is a direct recipient OR announcement is marked as target_all
        $this->db->select('a.*, IFNULL(ar.is_read, 0) as is_read');
        $this->db->from('announcements a');
        $this->db->join('announcement_recipients ar', 'a.id = ar.announcement_id AND ar.user_id = ' . $user_id, 'left');
        $this->db->where('(ar.user_id = ' . $user_id . ' OR a.target_all = 1)');
        $this->db->where('a.contentType', 'announcement'); // Make sure we only get announcements
        $this->db->order_by('a.created_at', 'DESC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Mark an announcement as read for a user
     * 
     * @param int $announcement_id
     * @param int $user_id
     * @return bool
     */
    public function mark_announcement_read($announcement_id, $user_id) {
        // First check if entry exists
        $this->db->where('announcement_id', $announcement_id);
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('announcement_recipients');
        
        if ($query->num_rows() > 0) {
            // Update existing record
            $this->db->where('announcement_id', $announcement_id);
            $this->db->where('user_id', $user_id);
            $this->db->update('announcement_recipients', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Create new record for this user (might happen for "target_all" announcements)
            $this->db->insert('announcement_recipients', [
                'announcement_id' => $announcement_id,
                'user_id' => $user_id,
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $this->db->affected_rows() > 0;
    }
}
?>
