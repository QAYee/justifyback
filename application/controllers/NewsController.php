<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class NewsController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('NewsModel');
        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url'));
    }

    public function addNews() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        $title = $this->input->post('title', true);
        $description = $this->input->post('description', true);
        
        // Validate input
        $this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('description', 'Description', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode([
                "status" => "error", 
                "message" => strip_tags(validation_errors())
            ]);
            return;
        }

        $imagePath = null;

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            $config['upload_path'] = './uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048; // Max 2MB
            $config['file_name'] = time() . '_' . $_FILES['image']['name'];

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('image')) {
                $uploadData = $this->upload->data();
                $imagePath = base_url('uploads/' . $uploadData['file_name']);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => $this->upload->display_errors()
                ]);
                return;
            }
        }

        // Save data
        $data = [
            'title' => $title,
            'description' => $description,
            'image' => $imagePath,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $result = $this->NewsModel->insert_news($data);
            if ($result) {
                echo json_encode([
                    "status" => "success",
                    "message" => "News added successfully"
                ]);
            } else {
                throw new Exception("Failed to add news");
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function getNews() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        try {
            $news = $this->NewsModel->getAllNews();
            echo json_encode([
                "status" => true,
                "news" => $news
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
