<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sample extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

	public function index()
	{
        debug($_ENV);
	}

}
