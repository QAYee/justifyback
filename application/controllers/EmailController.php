<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class EmailController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('email');
        
        // Set CORS headers for all responses
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");
        
        // Handle preflight OPTIONS requests immediately
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }
    
    public function sendVerificationCode() {
        // No need to handle OPTIONS again, it's done in the constructor
        
        // Get request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if required fields exist
        if (!isset($data['email']) || !isset($data['code']) || !isset($data['name'])) {
            $response = array(
                'status' => 'error',
                'message' => 'Missing required fields'
            );
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode($response));
            return;
        }
        
        $to_email = $data['email'];
        $verification_code = $data['code'];
        $name = $data['name'];
        
        // Configure email settings
        $config = array(
            'protocol' => 'smtp',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_user' => 'paranoid08biboy@gmail.com',
            'smtp_pass' => 'tkni mpdq isjj baef',
            'smtp_crypto' => 'tls',
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'wordwrap' => TRUE
        );
        
        $this->email->initialize($config);
        $this->email->from('paranoid08biboy@gmail.com', 'JustiFi');
        $this->email->to($to_email);
        $this->email->subject('JustiFi Account Verification');
        
        // Email body
        $message = "
            <html>
            <head>
                <title>Email Verification</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                    }
                    .header {
                        background-color: #4a90e2;
                        color: white;
                        padding: 15px;
                        text-align: center;
                        border-radius: 5px 5px 0 0;
                    }
                    .content {
                        padding: 20px;
                    }
                    .code {
                        font-size: 24px;
                        font-weight: bold;
                        color: #4a90e2;
                        text-align: center;
                        padding: 10px;
                        margin: 20px 0;
                        border: 2px dashed #4a90e2;
                        border-radius: 5px;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #777;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>JustiFi Account Verification</h2>
                    </div>
                    <div class='content'>
                        <p>Hello $name,</p>
                        <p>Thank you for creating an account with JustiFi. To complete your registration, please verify your email address by entering the following code:</p>
                        <div class='code'>$verification_code</div>
                        <p>If you did not create an account with JustiFi, please ignore this email.</p>
                        <p>Best regards,<br>The JustiFi Team</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                        <p>&copy; " . date('Y') . " JustiFi. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $this->email->message($message);
        
        try {
            if ($this->email->send()) {
                $response = array(
                    'status' => 'success',
                    'message' => 'Verification code sent successfully'
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Failed to send verification code: ' . $this->email->print_debugger()
                );
            }
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to send verification code: ' . $e->getMessage()
            );
        }
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($response));
    }
}
?>