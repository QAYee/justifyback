<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API_Registration extends CI_Controller {

    // sample access api end point
    // http://justify.test/index.php/Welcome/delete_user?ID=2
 
	 public function __construct()
	 {
		 parent::__construct();

		$this->load->model('User_model');
	 }

	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function getData()
	{
		$id = $this->input->get('id');
		$array = array(
			'key' => 'value',
			'key2' => 'value2'
		);

		$array = $this->User_model->get_user($id);
			echo json_encode($array);
	}

	public function insert_user()
	{
		$array = array(
			'Name' => $this->input->get('Name'),
		);

		$this->User_model->insert_user($array);
		$array2 = $this->User_model->get_user();

			echo json_encode($array2);
	}

	public function update_user()
	{
		$array = array(
			'Name' => $this->input->get('Name'),
			'id' => $this->input->get('ID'),
		);

		$this->User_model->update_user($array);
		$array2 = $this->User_model->get_user();

			echo json_encode($array2);
	}
	public function delete_user()
	{
		$array = array(
			'id' => $this->input->get('ID'),
		);

		$this->User_model->delete_user($array);
		$array2 = $this->User_model->get_user();

			echo json_encode($array2);
	}
	
}
