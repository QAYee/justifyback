<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChatModel extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_messages_by_complaint($complaint_id) {
        $this->db->select('chat_messages.*, users.name as sender_name');
        $this->db->from('chat_messages');
        $this->db->join('users', 'users.id = chat_messages.user_id', 'left');
        $this->db->where('complaint_id', $complaint_id);
        $this->db->order_by('chat_messages.timestamp', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function insert_message($data) {
        $this->db->insert('chat_messages', $data);
        return $this->db->insert_id();
    }

    public function get_latest_messages($complaint_id, $limit = 50) {
        $this->db->select('chat_messages.*, users.name as sender_name');
        $this->db->from('chat_messages');
        $this->db->join('users', 'users.id = chat_messages.user_id', 'left');
        $this->db->where('complaint_id', $complaint_id);
        $this->db->order_by('chat_messages.timestamp', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get();
        return $query->result_array();
    }
}