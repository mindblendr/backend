<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Output_profiler_model extends MY_Model {

	public function run()
	{
		$reports['benchmark'] = $this->compile_benchmark();
		$reports['get'] = $this->compile_get();
		$reports['post'] = $this->compile_post();
		$reports['headers'] = $this->input->request_headers();
		$reports['memory_usage'] = $this->compile_memory_usage();
		$reports['uri_string'] = $this->compile_uri_string();
		$reports['controller_info'] = $this->compile_controller_info();
		$reports['queries'] = $this->compile_queries();
		$reports['server_ip'] = $_SERVER['SERVER_ADDR'];
		$reports['client_ip'] = client_ip_address();
		return $reports;
	}

	protected function compile_benchmark()
	{
		$profile = array();
		$this->benchmark->mark('code_end');
		foreach ($this->benchmark->marker as $key => $val)
		{
			if (preg_match('/(.+?)_end$/i', $key, $match)
				&& isset($this->benchmark->marker[$match[1].'_end'], $this->benchmark->marker[$match[1].'_start']))
			{
				$profile['base_class'] = (float)$this->benchmark->elapsed_time($match[1].'_start', $key);
			}
		}
		$profile['controller_exec'] = (float)number_format($this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'), 4) - $profile['base_class'];
		$profile['total_exec'] = (float)number_format($this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'), 4);
		return $profile;
	}

	protected function compile_get()
	{
		$profile = array();
		foreach ($_GET as $key => $val)
		{
			$val = (is_array($val) OR is_object($val))
				? '<pre>'.htmlspecialchars(print_r($val, TRUE), ENT_QUOTES, config_item('charset')).'</pre>'
				: htmlspecialchars($val, ENT_QUOTES, config_item('charset'));

			$profile[$key] = $val;
		}
		return $profile;
	}

	protected function compile_memory_usage()
	{
		return number_format(memory_get_usage()) . ' bytes';
	}

	protected function compile_post()
	{
		$profile = array();
		foreach ($_POST as $key => $val)
		{
			$val = (is_array($val) OR is_object($val))
				? '<pre>'.htmlspecialchars(print_r($val, TRUE), ENT_QUOTES, config_item('charset')).'</pre>'
				: htmlspecialchars($val, ENT_QUOTES, config_item('charset'));

			$profile[$key] = $val;
		}
		return $profile;
	}

	protected function compile_uri_string()
	{
		return $this->uri->uri_string;
	}

	protected function compile_controller_info()
	{
		return $this->router->class . '/' . $this->router->method;
	}

	protected function compile_queries()
	{
		$profile = array();
		for ($i=0; $i < count($this->db->queries); $i++) {
			$query = [
				'query' => trim(preg_replace('/\s+/', ' ', $this->db->queries[$i])),
				'exec_time' => (float)number_format($this->db->query_times[$i] , 4)
			];
			$profile[$i] = $query;
		}
		$profile['total_exec'] = (float)number_format(array_sum($this->db->query_times), 4);
		$profile['total_count'] = count($this->db->queries);
		return $profile;
	}
}