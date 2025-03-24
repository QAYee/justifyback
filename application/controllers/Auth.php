<?php

public function register() 
{
    $array = array(
        'name' => $this->input->get('name'),
        'age' => $this->input->get('age'),
        'email' => $this->input->get('email'),
        'password' => password_hash($this->input->get('password'), PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    );

    $this->User_model->insert_user($array);
    $array2 = $this->User_model->get_users();

    echo json_encode($array2);
}
