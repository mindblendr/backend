<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function client_ip_address()
{
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
              $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
              $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
}

function contains($needle, $haystack)
{
    return strpos($haystack, $needle) !== false;
}

function client_browser()
{
    $CI = get_instance();
    
    if ($CI->user_agent->is_browser()) 
    { 
        $agent = $CI->user_agent->browser() . ' ' . $CI->user_agent->version();
    } 
    else if ($CI->user_agent->is_robot()) 
    {
        $agent = $CI->user_agent->robot();
    } 
    else if ($CI->user_agent->is_mobile())
    {
        $agent = $CI->user_agent->mobile();
    } 
    else 
    {
        $agent = 'Unidentified User Agent';
    }

    return $agent;
}

function client_platform()
{
    $CI = get_instance();

    return $CI->user_agent->platform();
}

function db_timestamp($timestamp_type, &$data, $id = NULL, $type = NULL, $has_date_int = TRUE)
{
    if (in_array($timestamp_type, [
        'created',
        'updated',
        'completed',
        'cancelled',
        'last_login',
        'deleted',
        'last_connected',
    ])) {
        $data[$timestamp_type . '_at']         = date('Y-m-d H:i:s');
        $data[$timestamp_type . '_ip']         = client_ip_address();
        $data[$timestamp_type . '_browser']    = client_browser();
        $data[$timestamp_type . '_platform']   = client_platform();
        if ($has_date_int) $data[$timestamp_type . '_date_int']   = str_replace(['-', ':', ' '], '', $data[$timestamp_type . '_at']);
        if ($id) 
        {
            $data[$timestamp_type . '_by'] = $id;
            if ($type) {
                $data[$timestamp_type . '_type'] = $type;
            }
        }
    }
    
    return $data;
}

function valid_date($date, $null = TRUE)
{
    if ($null == TRUE)
    {
        if (is_null($date) OR empty($date))
        {
            return TRUE;
        }
    }
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function pre_var_dump($data)
{
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

function asset_version()
{
    $CI = get_instance();
    return $CI->config->item('asset_version');
}

function data_exists($table, $condition)
{
    $CI = get_instance();    
    return $CI->db->select('id')->from($table)->where($condition)->get()->num_rows() > 0;    
}

function get_user_from_session($session_id, $user_table, $fields = NULL)
{
    $CI = get_instance();
    if ($session_id != "") {
        return $CI->db->select($fields ? $fields : '*')->from($user_table)->where([$user_table . '.session_id' => $session_id])->get()->row();
    }
    return NULL;
}

function object_filter($object_array, $callback)
{
	$new_array;
	foreach ($object_array as $object) {
		if (call_user_func($callback, $object)) {
			$new_array[] = $object;
		}
	}
	return $new_array;
}

function is_logged()
{
    $CI = get_instance();
	return isset($CI->session_user) !== FALSE && $CI->session_user !== null;
}

function is_agent_logged()
{
    $CI = get_instance();
	return isset($CI->session_user) !== FALSE && $CI->session_user !== null;
}

function convert_keys($request_keys, &$transfer)
{
    foreach ($transfer as $key => $value) {
        if (array_key_exists($key, $request_keys)) {
            $transfer[$request_keys[$key]] = $value;
            unset($transfer[$key]);
        }
    }
}

function set_session_user($session_id)
{
    $CI = get_instance();
    $exploded_session_id = explode('_', $CI->input->post('session_id'));

    if (count($exploded_session_id) == 2) {
        if (! array_key_exists($exploded_session_id[1], $CI->config->item('user_table'))) {
            $CI->json->_exit('Unauthorized!');
        }
        $table = $CI->config->item('user_table')[$exploded_session_id[1]];
        $CI->session_user = $CI->$table->get_by_session_id();
        if (is_logged()) {
            switch ($table) {
                case 'admin':
                    $CI->session_user->type = 1;
                    break;
                case 'agent':
                    $CI->session_user->type = 2;
                    break;
                case 'player':
                default:
                    $CI->session_user->type = 3;
                    break;
            }
        } else {
            $CI->json->_exit('Unauthorized!');
        }
    }
}

function sort_files($files)
{
	$new_files = [];
	for ($i=0; $i < count($files['name']); $i++) { 
		$new_files[] = [
			'name' => $files['name'][$i],
			'type' => $files['type'][$i],
			'tmp_name' => $files['tmp_name'][$i],
			'error' => $files['error'][$i],
			'size' => $files['size'][$i]
		];
	}
	return $new_files;
}

function limit_to($user_types = array(), $is_api = FALSE)
{
    $CI = get_instance();
    $limited = FALSE;
    if (count($user_types) > 0) {
        foreach ($user_types as $key => $user_type) {
            if (! is_logged() || $CI->session_user->type == $user_type) {
                $limited = TRUE;
            }
        }
    }
    
    if ($limited) {
        if ($is_api) {
            $CI->json->_exit('Unauthorized!');
        } else {
            show_404();
        }
    }
}

function curlRequest($url, $data = [], $header='', $method = "GET", $decoded = FALSE)
{
	try {
		$ch = curl_init($url);
		if ($header != '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);

			foreach ($header as $_header) {
				if (strpos($_header, 'Content-Type') !== FALSE && strpos($_header, 'application/json') !== FALSE) {
					$data = json_encode($data);
					break;
				}
			}
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} else if ($method == 'GET') {
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
		$response = curl_exec($ch);
		curl_close($ch);

        return $decoded ? $response : json_decode($response);
	} catch (\Exception $e) {
        return [ 'error' => $e];
	}
}

function _error($code)
{
	/**
	 * 0 - UNKNOWN ERROR
	 * 1 - OK
	 * 501 - FUND ERROR
	 * 502 - AUTHENTICATION FAILED
	 * 503 - INVALID TRANSACTION
	 */
	$CI = get_instance();
	$CI->json->_display(NULL, $code);
}

function _send_error($code, $status = 1)
{
	/**
	 * 0 - UNKNOWN ERROR
	 * 1 - OK
	 * 501 - FUND ERROR
	 * 502 - AUTHENTICATION FAILED
	 * 503 - INVALID TRANSACTION
	 */
	$CI = get_instance();
	$CI->json->_display([
        'context' => [
            'status' => $code
        ]
    ], $status);
}

function cors()
{
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
    if ( "OPTIONS" === $_SERVER['REQUEST_METHOD'] ) {
        die();
    }
}

// Function to get all the dates in given range 
function getDatesFromRange($start, $end, $format = 'Y-m-d') { 
      
    // Declare an empty array 
    $array = array(); 
      
    // Variable that store the date interval 
    // of period 1 day 
    $interval = new DateInterval('P1D'); 
  
    $realEnd = new DateTime($end); 
    // $realEnd->add($interval); 
  
    $period = new DatePeriod(new DateTime($start), $interval, $realEnd); 
  
    // Use loop to store date into array 
    foreach($period as $date) {                  
        $array[] = $date->format($format);  
    } 
  
    // Return the array elements 
    return $array; 
} 