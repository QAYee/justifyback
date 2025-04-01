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

        $imageName = null;

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            // Create directory if it doesn't exist
            if (!is_dir('./uploads/')) {
                mkdir('./uploads/', 0777, true);
            }

            $config['upload_path'] = './uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048; // Max 2MB
            $config['file_name'] = time() . '_' . $_FILES['image']['name'];

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('image')) {
                $uploadData = $this->upload->data();
                // Store only the filename, not the full URL
                $imageName = $uploadData['file_name'];
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
            'image' => $imageName, // Store just the filename
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

    public function updateNews() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        $id = $this->input->post('id', true);
        $title = $this->input->post('title', true);
        $description = $this->input->post('description', true);
        
        // Validate input
        if (!$id || !is_numeric($id) || !$title || !$description) {
            echo json_encode([
                "status" => "error", 
                "message" => "All fields are required and ID must be valid"
            ]);
            return;
        }

        // Get existing news to check if it exists
        $existing_news = $this->NewsModel->get_news_by_id($id);
        if (!$existing_news) {
            echo json_encode([
                "status" => "error",
                "message" => "News item not found"
            ]);
            return;
        }

        // Initialize image name with existing one
        $imageName = $existing_news['image'];

        // Handle file upload if new image is provided
        if (!empty($_FILES['image']['name'])) {
            // Create directory if it doesn't exist
            if (!is_dir('./uploads/')) {
                mkdir('./uploads/', 0777, true);
            }

            $config['upload_path'] = './uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048; // Max 2MB
            $config['file_name'] = time() . '_' . $_FILES['image']['name'];

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('image')) {
                $uploadData = $this->upload->data();
                
                // Delete old image if it exists
                if ($imageName && file_exists('./uploads/' . $imageName)) {
                    unlink('./uploads/' . $imageName);
                }
                
                // Update with new image name
                $imageName = $uploadData['file_name'];
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => $this->upload->display_errors()
                ]);
                return;
            }
        }

        // Update data
        $data = [
            'title' => $title,
            'description' => $description,
            'image' => $imageName,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $result = $this->NewsModel->update_news($id, $data);
            if ($result) {
                echo json_encode([
                    "status" => "success",
                    "message" => "News updated successfully"
                ]);
            } else {
                throw new Exception("Failed to update news");
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function deleteNews() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $id = isset($data['id']) ? $data['id'] : null;
        
        // Validate input
        if (!$id || !is_numeric($id)) {
            echo json_encode([
                "status" => "error", 
                "message" => "Valid news ID is required"
            ]);
            return;
        }

        // Get image name before deleting the news
        $news = $this->NewsModel->get_news_by_id($id);
        
        try {
            $result = $this->NewsModel->delete_news($id);
            
            if ($result) {
                // Delete image file if it exists
                if ($news && !empty($news['image'])) {
                    $image_path = './uploads/' . $news['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                echo json_encode([
                    "status" => "success",
                    "message" => "News deleted successfully"
                ]);
            } else {
                throw new Exception("Failed to delete news");
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
            
            // Format date and ensure image path is correct
            foreach ($news as &$item) {
                // Format the date in a readable format
                if (!empty($item['created_at'])) {
                    $item['created_at'] = date('M d, Y h:i A', strtotime($item['created_at']));
                }
                
                // Add full URL for images
                if (!empty($item['image'])) {
                    $item['image'] = base_url('uploads/' . $item['image']);
                }
            }
            
            echo json_encode([
                "status" => true,
                "news" => $news
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
