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
        $this->load->helper(array('url'));
    }

    public function register() {
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Decode JSON input
        $raw_data = file_get_contents("php://input");
        error_log("Received Data: " . $raw_data); // Debugging log
        $postData = json_decode($raw_data, true);

        if (!$postData) {
            echo json_encode(["status" => "error", "message" => "Invalid request"]);
            return;
        }

        // Sanitize input
        $full_name = $this->security->xss_clean($postData['full_name'] ?? '');
        $birthdate = $this->security->xss_clean($postData['birthdate'] ?? '');
        $age = (int) $this->security->xss_clean($postData['age'] ?? 0);
        $address = $this->security->xss_clean($postData['address'] ?? '');
        $email = $this->security->xss_clean($postData['email'] ?? '');
        $password = $this->security->xss_clean($postData['password'] ?? '');
        $confirm_password = $this->security->xss_clean($postData['confirm_password'] ?? '');

        // Validate input
        if (empty($full_name) || empty($birthdate) || empty($age) || empty($address) || empty($email) || empty($password) || empty($confirm_password)) {
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

        // Hash password before saving
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Save user data
        $data = [
            "name" => $full_name,
            "birthdate" => $birthdate,
            "age" => $age,
            "address" => $address,
            "email" => $email,
            "password" => $hashed_password,
            "created_at" => date('Y-m-d H:i:s')
        ];

        $result = $this->User_model->insert_user($data);

        if ($result) {
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
    }
}
