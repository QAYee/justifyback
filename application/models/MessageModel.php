<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MessageModel extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get or create a conversation for a user
     * 
     * @param int $user_id The user ID
     * @return array|bool Conversation data or false on failure
     */
    public function get_or_create_conversation($user_id) {
        // First try to get existing conversation
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('conversations');
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        // If not found, create new conversation
        $data = [
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('conversations', $data);
        
        if ($this->db->affected_rows() > 0) {
            $data['id'] = $this->db->insert_id();
            return $data;
        }
        
        return false;
    }

    /**
     * Get all messages for a conversation
     * 
     * @param int $conversation_id The conversation ID
     * @return array List of messages
     */
    public function get_conversation_messages($conversation_id) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->order_by('timestamp', 'ASC');
        $query = $this->db->get('messages');
        return $query->result_array();
    }

    /**
     * Insert a new message
     * 
     * @param array $data Message data
     * @return int|bool Message ID on success, false on failure
     */
    public function insert_message($data) {
        $this->db->insert('messages', $data);
        
        if ($this->db->affected_rows() > 0) {
            $message_id = $this->db->insert_id();
            
            // Update conversation last activity time
            $this->db->where('id', $data['conversation_id']);
            $this->db->update('conversations', ['updated_at' => date('Y-m-d H:i:s')]);
            
            return $message_id;
        }
        
        return false;
    }

    /**
     * Update message status
     * 
     * @param int $message_id The message ID
     * @param string $status New status (sent, delivered, read)
     * @return bool True on success, false on failure
     */
    public function update_message_status($message_id, $status) {
        $this->db->where('id', $message_id);
        $this->db->update('messages', ['status' => $status]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Mark all messages in a conversation as read for a specific sender
     * 
     * @param int $conversation_id The conversation ID
     * @param int $recipient_id The ID of the message recipient
     * @param bool $is_admin Whether the messages to mark are from admin
     * @return bool True on success, false on failure
     */
    public function mark_messages_as_read($conversation_id, $recipient_id, $is_admin = false) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->where('is_admin', $is_admin ? 1 : 0);
        $this->db->where('sender_id !=', $recipient_id);
        $this->db->where('status !=', 'read');
        $this->db->update('messages', ['status' => 'read']);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get all conversations for admin view
     * 
     * @return array List of all conversations
     */
    public function get_all_conversations() {
        $this->db->order_by('updated_at', 'DESC');
        $query = $this->db->get('conversations');
        return $query->result_array();
    }

    /**
     * Count unread messages in a conversation for a user
     * 
     * @param int $conversation_id The conversation ID
     * @param int $user_id The user ID
     * @return int Count of unread messages
     */
    public function count_unread_messages($conversation_id, $user_id) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->where('sender_id !=', $user_id);
        $this->db->where('status !=', 'read');
        return $this->db->count_all_results('messages');
    }

    /**
     * Get the last message in a conversation
     * 
     * @param int $conversation_id The conversation ID
     * @return array|null The last message or null if none
     */
    public function get_last_message($conversation_id) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->order_by('timestamp', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('messages');
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return null;
    }
}
?>
