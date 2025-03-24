<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

class LoginController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }

    public function login() {
        $postData = json_decode(file_get_contents("php://input"), true);

        if (!$postData) {
            echo json_encode(["status" => "error", "message" => "Invalid request format"]);
            return;
        }

        $email = $this->security->xss_clean($postData['email']);
        $password = $this->security->xss_clean($postData['password']);

        // Fetch user by email
        $user = $this->User_model->get_user_by_email($email);

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "User not found"]);
            return;
        }

        // Check password
        if (!password_verify($password, $user['password'])) {
            echo json_encode(["status" => "error", "message" => "Incorrect password"]);
            return;
        }

        // Ensure admin is always an integer
        $user['admin'] = (int) $user['admin'];

        // Remove password before sending response
        unset($user['password']);

        // Log for debugging
        error_log("User Data: " . print_r($user, true));

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => $user
        ]);
    }
}
