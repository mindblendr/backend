<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public $_data = NULL;
    public function __construct()
    {
        parent::__construct();
		$this->_prep_data();

		$exploded_uri = explode('/', substr($_SERVER['REQUEST_URI'], 1));
		if (count($exploded_uri) > 0) {
		
			if ($this->input->post('session_id')) {
				$exploded_session_id = explode('_', $this->input->post('session_id'));
				if (count($exploded_session_id) === 2) {
					switch($this->config->item('user_table')[$exploded_session_id[1]]) {
						case 'admin':
							$this->session_user = $this->admin
															->select('admin.*')
															->where([
																'admin.session_id' => $this->input->post('session_id'),
																'admin.status' => 2
															])->get();
							if (is_logged()) $this->session_user->type = 1;
							break;
						case 'agent':
							$this->session_user = $this->agent
															->select('agent.*')
															->where([
																'agent.session_id' => $this->input->post('session_id'),
																'agent.status' => 2
															])->get();
							if (is_logged()) $this->session_user->type = 2;
							break;
					}
				}
			}

			switch($exploded_uri[0]) {
				case 'admin':
					if (is_logged()) {
						if ($this->session_user->type != 1) {
							$this->json->_exit('Unauthorized!', 401);
						}
					} else {
						$this->json->_exit('Unauthorized!', 401);
					}
					break;
				case 'agent':
					if (is_logged()) {
						if ($this->session_user->type != 2) {
							$this->json->_exit('Unauthorized!', 401);
						}
					} else {
						$this->json->_exit('Unauthorized!', 401);
					}
					break;
				default:
					break;
			}
		}

		cors();
    }

    private function _prep_data()
    {
        // Sanitize all POST variable
        if ($this->input->post()) {
            // $_POST = array_map('trim', $_POST);
            foreach ($_POST as $key => $value) {
                if ($_POST[$key] == null or strlen($_POST[$key]) == 0) {
                    unset($_POST[$key]);
                }
            }
        }

        // Sanitize all GET variable
        if ($this->input->get()) {
            // $_GET = array_map('trim', $_GET);
            foreach ($_GET as $key => $value) {
                if ($_GET[$key] == null or strlen($_GET[$key]) == 0) {
                    unset($_GET[$key]);
                }

            }
		}
		
		if ($this->input->raw_input_stream) {
			$raw_input = json_decode($this->security->xss_clean($this->input->raw_input_stream), TRUE, 512, JSON_OBJECT_AS_ARRAY);
			if ($raw_input) {
				$_POST = array_merge($_POST, $raw_input);
			}
		}
    }

    public function data_validation($config, $data = null, $error_convert_keys = null)
    {
        if (!empty($data) or !is_null($data)) {
            $this->form_validation->set_data($data);
        }

        if (is_array($config)) {
            foreach ($config as $key) {
                if ($this->form_validation->run($key) == false) {
                    $errors = $this->form_validation->error_array();

                    $errors = array_values($errors);
                    for ($i = 0; $i < count($errors); $i++) {$errors[$i] = (int) $errors[$i];}

                    $this->json->_display([
                        'context' => [
                            'status' => array_values($errors),
                        ],
                        'status' => 1,
                    ]);
                }
                $this->form_validation->reset_validation();
            }
        } else {
            if ($this->form_validation->run($config) == false) {
                $errors = $this->form_validation->error_array();

                $errors = array_values($errors);
                for ($i = 0; $i < count($errors); $i++) {$errors[$i] = (int) $errors[$i];}

                $this->json->_display([
                    'context' => [
                        'status' => array_values($errors),
                    ],
                    'status' => 1,
                ]);
            }
        }
        $this->form_validation->reset_validation();
    }

    public function relations($query, $table, $primary_key, $foreign_key, $select = ['*'])
    {
        $primary_keys = [];
        foreach ($query as $row) {
            array_push($primary_keys, (int) $row->{$primary_key});
        }

        array_push($select, $foreign_key);
        $this->db->select($select)
            ->from($table)
            ->where_in($foreign_key, $primary_keys);
        $data = $this->db->get()->result();

        foreach ($query as $row) {
            foreach ($data as $sub) {
                if ($row->{$primary_key} == $sub->{$foreign_key}) {
                    $row->{$table}[] = $sub;
                }
            }
        }
    }
}
