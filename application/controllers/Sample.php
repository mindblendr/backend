<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sample extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

	public function index()
	{
        $this->json->_display([
            'data' => $this->db->get('user');,
            'status' => 1
        ]);
	}

	public function insert()
	{
        $this->json->_display([
            'status' => $this->db->insert('user', ['name' => md5(rand(1,2000))]) ? 1 : 0
        ]);
	}

}
