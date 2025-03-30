<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Allow Cross-Origin Requests (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

class RegisterController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(array('url', 'file'));
    }

    public function register() {
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Get form data
        $name = $this->input->post('name');
        $birthdate = $this->input->post('birthdate');
        $age = $this->input->post('age');
        $address = $this->input->post('address');
        $email = $this->input->post('email');
        $phone = $this->input->post('phone');
        $password = $this->input->post('password');
        $confirm_password = $this->input->post('confirm_password');

        // Sanitize input
        $name = trim($this->security->xss_clean($name));
        $birthdate = trim($this->security->xss_clean($birthdate));
        $age = (int) $age;
        $address = trim($this->security->xss_clean($address));
        $email = trim($this->security->xss_clean($email));
        $phone = trim($this->security->xss_clean($phone));
        $password = trim($this->security->xss_clean($password));
        $confirm_password = trim($this->security->xss_clean($confirm_password));

        // Validate input
        if (
            empty($name) || empty($birthdate) || $age <= 0 ||
            empty($address) || empty($email) || empty($password) || empty($confirm_password)
        ) {
            echo json_encode(["status" => "error", "message" => "All fields are required"]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Invalid email format"]);
            return;
        }

        if ($password !== $confirm_password) {
            echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
            return;
        }

        if ($this->User_model->check_email_exists($email)) {
            echo json_encode(["status" => "error", "message" => "Email already exists"]);
            return;
        }

        // Handle image upload
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $config['upload_path'] = './uploads/images/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size'] = 2048; // 2MB max
            $config['file_name'] = uniqid('profile_');

            // Create directory if it doesn't exist
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('image')) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Image upload failed: " . $this->upload->display_errors('', '')
                ]);
                return;
            }

            $upload_data = $this->upload->data();
            $image = $upload_data['file_name'];
        }

        // Hash password before saving
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare user data for insertion
        $data = [
            "name" => $name,
            "birthdate" => $birthdate,
            "age" => $age,
            "address" => $address,
            "email" => $email,
            "phone" => $phone,
            "password" => $hashed_password,
            "image" => $image,
            "created_at" => date('Y-m-d H:i:s')
        ];

        // Insert user into the database
        $result = $this->User_model->insert_user($data);

        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "User registered successfully",
                "image" => $image ? base_url('uploads/images/' . $image) : null
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
    }

    public function get_users() {
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        try {
            // Get pagination parameters
            $page = (int) $this->input->get('page', TRUE) ?: 1;
            $limit = (int) $this->input->get('limit', TRUE) ?: 10;
            $offset = ($page - 1) * $limit;

            // Get filter parameters
            $filters = array();
            if ($search = $this->input->get('search')) {
                $filters['search'] = $this->security->xss_clean($search);
            }

            // Get users with pagination
            $users = $this->User_model->get_users($filters, 'created_at', 'DESC', $limit, $offset);
            $total = $this->User_model->get_users_count($filters);

            // Process user data to include image URLs and remove sensitive info
            foreach ($users as &$user) {
                if (!empty($user['image'])) {
                    $user['image_url'] = base_url('uploads/images/' . $user['image']);
                }
                unset($user['password']); // Remove sensitive data
            }

            echo json_encode([
                "status" => "success",
                "data" => [
                    "users" => $users,
                    "pagination" => [
                        "current_page" => $page,
                        "per_page" => $limit,
                        "total" => $total,
                        "total_pages" => ceil($total / $limit)
                    ]
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to fetch users: " . $e->getMessage()
            ]);
        }
    }

    public function get_user($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        if (!$id || !is_numeric($id)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid user ID"
            ]);
            return;
        }

        try {
            $user = $this->User_model->get_user_id($id);

            if (!$user) {
                echo json_encode([
                    "status" => "error",
                    "message" => "User not found"
                ]);
                return;
            }

            // Add image URL if exists
            if (!empty($user['image'])) {
                $user['image_url'] = base_url('uploads/images/' . $user['image']);
            }

            // Remove sensitive data
            unset($user['password']);

            echo json_encode([
                "status" => "success",
                "data" => $user
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to fetch user: " . $e->getMessage()
            ]);
        }
    }
}
