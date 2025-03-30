<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NewsModel extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Insert a new news item into the database
     * 
     * @param array $data News data (title, description, created_at, updated_at)
     * @return bool True if inserted successfully, false otherwise
     */
    public function insert_news($data) {
        $this->db->insert('news', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get all news items, ordered by creation date descending
     * 
     * @return array List of news items
     */
    public function getAllNews() {
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('news')->result_array();
    }
}
?>