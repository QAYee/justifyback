<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */

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
