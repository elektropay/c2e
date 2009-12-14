<?php

class Gateway extends Controller {

	function gateway()
	{
		parent::Controller();	
	}
	
	function index()
	{
		// grab the request
		$request = $this->input->post('request');
		
		// Log the request
		$this->log_model->LogRequest($request);
		
		// find out if the request is valid XML
		$xml = simplexml_load_string($request);
		
		// if it is not valid XML...
		if(!$xml) {
			die($this->response->Error(1000));
		}
		
		// Make an array out of the XML
		$this->load->library('arraytoxml');
		$params = $this->arraytoxml->toArray($xml);
		
		// get the api ID and secret key
		$api_id = $params['authentication']['api_id'];
		$secret_key = $params['authentication']['secret_key'];
		
		// authenticate the api ID
		$this->load->model('authentication_model', 'auth');
		
		$client = $this->auth->Authenticate($api_id, $secret_key);
		$client_id = $client->client_id;
		
		if(!$client_id) {
			die($this->response->Error(1001));
		}	
		
		// Get the request type
		if(!isset($params['type'])) {
			die($this->response->Error(1002));
		}
		$request_type = $params['type'];
		
		// Make sure the first letter is capitalized
		$request_type = ucfirst($request_type);
		
		// validate the request type
		$this->load->model('request_type_model', 'request_type');
		$request_type_model = $this->request_type->ValidateRequestType($request_type);
		
		if(!$request_type_model) {
			die($this->response->Error(1002));
		}
		
		// Load the correct model and method
		// Is this method part of this API controller?
		if (method_exists($this,$request_type)) {
			$response = $this->$request_type();
		}
		else {
			$this->load->model($request_type_model);
			$response = $this->$request_type_model->$request_type($client_id, $params);
		}
		
		// handle errors that didn't just kill the code
		if ($response == FALSE) {
			echo $this->response->Error('1009');
			die();
		}
		
		// Make sure a proper format was passed
		if(isset($params['format'])) {
			$format = $params['format'];
			if(!in_array($format, array('xml', 'json', 'php'))) {
				echo $this->response->Error(1006);
				die();
			}
		} else {
			$format = 'xml';
		}
		
		// Echo the response
		echo $this->response->FormatResponse($response, $format);		
	}
	
	function DeletePlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($this->plan_model->DeletePlan($client_id, $params['plan_id'])) {
			return $this->response->TransactionResponse(502, array());
		} else {
			return FALSE;
		}
	}
	
	function GetPlans($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$data = array();
		if ($plans = $this->plan_model->GetPlans($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($plans);
			$data['total_results'] = count($this->plan_model->GetPlans($client_id, $params));
			
			while (list(,$plan) = each($plans)) {
				$data['plans']['plan'][] = $plan;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetPlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($plan = $this->plan_model->GetPlan($client_id, $params['plan_id'])) {
			$data = array();
			$data['plan'] = $plan;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdatePlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($this->plan_model->UpdatePlan($client_id, $params)) {
			return $this->response->TransactionResponse(501, array());		
		}
		else {
			return FALSE;
		}
	}
	
	function NewPlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($insert_id = $this->plan_model->NewPlan($client_id, $params)) {
			$response_array = array();
			$response_array['plan_id'] = $insert_id; 
			$response = $this->response->TransactionResponse(500, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function NewGateway($client_id, $params)
	{
		$this->load->model('gateway_model');
		
		if ($insert_id = $this->gateway_model->NewGateway($client_id, $params)) {
			$response_array = array();
			$response_array['gateway_id'] = $insert_id; 
			$response = $this->response->TransactionResponse(400, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function MakeDefaultGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('MakeDefaultGateway', $params);
		
		$this->load->model('gateway_model');
		
		if ($this->gateway_model->MakeDefaultGateway($client_id, $params['gateway_id'])) {
			$response = $this->response->TransactionResponse(403, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdateGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('MakeDefaultGateway', $params);
		
		$this->load->model('gateway_model');
		if ($this->gateway_model->UpdateGateway($client_id, $params)) {
			$response = $this->response->TransactionResponse(401,array());
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function DeleteGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('MakeDefaultGateway', $params);
		
		$this->load->model('gateway_model');
		if ($this->gateway_model->DeleteGateway($client_id, $params['gateway_id'])) {
			$response = $this->response->TransactionResponse(402,array());
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function GetRecurring($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('GetRecurring', $params);
	
		$this->load->model('subscription_model');
		if (!$recurring = $this->subscription_model->GetRecurring($client_id, $params['recurring_id'])) {
			return $this->response->Error(6002);
		} else {
			$data = array();
			$data['recurring'] = $recurring;
			return $data;
		}
	}
	
	function GetRecurrings($client_id, $params)
	{
		$this->load->model('subscription_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$data = array();
		if ($recurrings = $this->subscription_model->GetRecurrings($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($recurrings);
			$data['total_results'] = count($this->subscription_model->GetRecurrings($client_id, $params));
			
			while (list(,$recurring) = each($recurrings)) {
				$data['recurrings']['recurring'][] = $recurring;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function UpdateRecurring($client_id, $params)
	{
		if (isset($params['plan_id'])) {
			return $this->response->Error();
		}
		
		if(!isset($params['recurring_id'])) {
			return $this->response->Error(6002);
		}
	
		$this->load->model('subscription_model');
		if ($this->gateway_model->UpdateRecurring($client_id, $params)) {
			$response = $this->response->TransactionResponse(102,array());
			
			return $response;
		}
		else {
			return $this->response->Error(6005);
		}
	}
	
	function CancelRecurring($client_id, $params)
	{
		if (!isset($params['recurring_id'])) {
			return $this->response->Error(6002);
		}
		
		$this->load->model('subscription_model');
		
		if ($this->gateway_model->CancelRecurring($client_id, $params['recurring_id'])) {
			return $this->response->TransactionResponse(101,array());
		}
		else {
			return $this->response->Error(5014);
		}
	}
	
	function NewCustomer($client_id, $params)
	{
		$this->load->model('customer_model');
		
		if ($customer_id = $this->customer_model->NewCustomer($client_id, $params)) {
			$response = array('customer_id' => $customer_id);
		
			$response = $this->response->TransactionResponse(200, $response);
		}
		else {
			return FALSE;
		}	
	}
	
	function UpdateCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			return $this->response->Error(6001);
		}
		
		$this->load->model('customer_model');
		
		if ($this->customer_model->UpdateCustomer($client_id, $params)) {
			return $this->response->TransactionResponse(201);
		}
		else {
			return FALSE;
		}
	}
	
	function DeleteCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		$this->load->model('customer_model');
		
		if ($this->customer_model->DeleteCustomer($client_id, $params['customer_id'])) {
			return $this->response->TransactionResponse(202);
		}
		else {
			return FALSE;
		}	
	}
	
	function GetCustomers($client_id, $params)
	{
		$this->load->model('customer_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$data = array();
		if ($customers = $this->customer_model->GetCustomers($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($customers);
			$data['total_results'] = count($this->customer_model->GetCustomers($client_id, $params));
			
			while (list(,$customer) = each($customers)) {
				// sort through plans, first
				$customer_plans = $customer['plans'];
				unset($customer['plans']);
				while (list(, $plan) = each($customer_plans)) {
					$customer['plans']['plan'][] = $plan;
				}
				
				$data['customers']['customer'][] = $customer;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetCustomer($client_id, $params)
	{
		// Get the customer id
		if(!isset($params['customer_id'])) {
			return $this->response->Error(4000);
		}
		
		$this->load->model('customer_model');
		
		$data = array();
		if ($customer = $this->customer_model->GetCustomer($client_id, $params['customer_id'])) {	
			// sort through plans, first
			$customer_plans = $customer['plans'];
			unset($customer['plans']);
			while (list(, $plan) = each($customer_plans)) {
				$customer['plans']['plan'][] = $plan;
			}
			
			$data['customer'] = $customer;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function GetCharges($client_id, $params)
	{
		$this->load->model('order_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$data = array();
		if ($charges = $this->order_model->GetCharges($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($charges);
			$data['total_results'] = count($this->order_model->GetCharges($client_id, $params));
			
			while (list(,$charge) = each($charges)) {
				$data['charges']['charge'][] = $charge;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetCharge($client_id, $params)
	{
		// Get the charge ID
		if(!isset($params['charge_id'])) {
			return $this->response->Error(6000);
		}
		
		$this->load->model('order_model');
		
		$data = array();
		if ($charge = $this->order_model->GetCharge($client_id, $params['charge_id'])) {	
			$data['charge'] = $charge;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function GetLatestCharge($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		$this->load->model('order_model');
		
		$data = array();
		if ($charge = $this->order_model->GetLatestCharge($client_id, $params['customer_id'])) {	
			$data['charge'] = $charge;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function NewClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($client = $this->client_model->NewClient($client_id, $params)) {
			$response = $this->response->TransactionResponse(300,$client);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdateAccount($client_id, $params)
	{
		$this->load->model('client_model');
		
		$params['client_id'] = $client_id;
		return $this->UpdateClient($client_id, $params);
	}
	
	function UpdateClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->UpdateClient($client_id, $params)) {
			return $this->response->TransactionResponse(301,array());
		}
		else {
			return FALSE;
		}
	}
	
	function SuspendClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->SuspendClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(302,array());
		}
		else {
			return $this->response->Error(2004);
		}
	}
	
	function UnsuspendClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->UnsuspendClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(303,array());
		}
		else {
			return $this->response->Error(2004);
		}
	}
	
	function DeleteClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->DeleteClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(304,array());
		}
		else {
			return $this->response->Error(2004);
		}
	}
}



/* End of file gateway.php */
/* Location: ./system/application/controllers/gateway.php */