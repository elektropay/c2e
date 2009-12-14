<?php
/**
* Plan Model 
*
* Contains all the methods used to create and manage client plans.
*
* @version 1.0
* @author David Ryan
* @author Brock Ferguson
* @package OpenGateway

*/

class Plan_model extends Model
{
	function Plan_Model()
	{
		parent::Model();
	}
	
	/*
	* Creates a new plan under the specified client
	*
	* @param int $client_id The Client ID
	* @param string $params['plan_type'] The name of the plan type (e.g. "paid"). Optional.
	* @param string $params['amount'] Charge amount. Optional.
	* @param int $params['interval'] Interval (days). Optional.
	* @param string $params['notification_url'] Notification URL. Optional.
	* @param string $params['name'] Name.  Optional.
	* @param int $params['free_trial'] Number of days of a free trial. Optional.
	*
	* @return int|bool Returns Plan ID on success or FALSE on failure
	*/ 
	function NewPlan($client_id, $params)
	{
		// Get the plan params
		$plan = $params['plan'];
		
		$this->load->library('field_validation');
		
		if(isset($plan['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($plan['plan_type']);
			$insert_data['plan_type_id'] = $plan_type_id;
		} else {
			die($this->response->Error(1004));
		}
		
		if($plan['plan_type'] == 'free') {
			$insert_data['amount'] = 0;
		} else {
			if(isset($plan['amount'])) {
				if(!$this->field_validation->ValidateAmount($plan['amount'])) {
					die($this->response->Error(5009));	
				}
				$insert_data['amount'] = $plan['amount'];
			} else {
				die($this->response->Error(1004));
			}
		}		
		
		if(isset($plan['interval'])) {
			if(!is_numeric($plan['interval']) || $plan['interval'] < 1) {
				die($this->response->Error(5011));
			}	
			$insert_data['interval'] = $plan['interval'];
		} else {
			die($this->response->Error(1004));
		}
		
		if(isset($plan['notification_url'])) {	
			$insert_data['notification_url'] = $plan['notification_url'];
		}
		
		if(isset($plan['name'])) {	
			$insert_data['name'] = $plan['name'];
		} else {
			die($this->response->Error(1004));
		}
		
		if(isset($plan['free_trial'])) {
			if(!is_numeric($plan['free_trial']) || $plan['free_trial'] < 0) {
				die($this->response->Error(7002));
			}	
			$insert_data['free_trial'] = $plan['free_trial'];
		} else {
			die($this->response->Error(1004));
		}
		
		$insert_data['client_id'] = $client_id;
		$insert_data['deleted'] = 0;
							
		if ($this->db->insert('plans', $insert_data)) {
			return $this->db->insert_id();
		}
		else {
			return FALSE;
		}
	}
	
	/*
	* Updates a plan
	*
	* @param int $client_id The Client ID
	* @param int $params['plan_id'] The ID of the plan being updated
	* @param string $params['plan_type'] The name of the plan type (e.g. "paid"). Optional.
	* @param string $params['amount'] Charge amount. Optional.
	* @param int $params['interval'] Interval (days). Optional.
	* @param string $params['notification_url'] Notification URL. Optional.
	* @param string $params['name'] Name.  Optional.
	* @param int $params['free_trial'] Number of days of a free trial. Optional.
	*
	* @return bool Returns TRUE on success or FALSE on failure
	*/ 
	function UpdatePlan($client_id, $params)
	{
		// Get the plan params
		$plan = $params['plan'];
		
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $params['plan_id']);
		
		$this->load->library('field_validation');
		
		if(isset($plan['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($plan['plan_type']);
			$update_data['plan_type_id'] = $plan_type_id;
		}
		
		if($plan['plan_type'] == 'free') {
			$update_data['amount'] = 0;
		} else {
			if(isset($plan['amount'])) {
				if(!$this->field_validation->ValidateAmount($plan['amount'])) {
					die($this->response->Error(5009));	
				}
				$update_data['amount'] = $plan['amount'];
			} else {
				die($this->response->Error(1004));
			}
		}
		
		if(isset($plan['interval'])) {
			if(!is_numeric($plan['interval']) || $plan['interval'] < 1) {
				die($this->response->Error(5011));
			}	
			$update_data['interval'] = $plan['interval'];
		}
		
		if(isset($plan['notification_url'])) {	
			$update_data['notification_url'] = $plan['notification_url'];
		}
		
		if(isset($plan['name'])) {	
			$update_data['name'] = $plan['name'];
		}
		
		if(isset($plan['free_trial'])) {
			if(!is_numeric($plan['free_trial']) || $plan['free_trial'] < 0) {
				die($this->response->Error(7002));
			}	
			$update_data['free_trial'] = $plan['free_trial'];
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		$this->db->where('plan_id', $plan_details->plan_id);
		if ($this->db->update('plans', $update_data)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	/*
	* Get plan information by plan ID
	*
	* @param int $client_id The Client ID
	* @param int $plan_id The Plan ID
	*
	* @return array Plan information
	*
	*/
	function GetPlan($client_id, $plan_id)
	{
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $plan_id);
		
		$plan_type = $this->GetPlanType($plan_details->plan_type_id);
		
		unset($plan_details->client_id);
		unset($plan_details->plan_type_id);
		
		$plan_details->type = $plan_type;
		
		foreach($plan_details as $key => $value)
		{
			$data[$key] = $value;
		}
		
		return $data;
	}
	
	/*
	* Gets a list of all active plans with optional filters
	*
	* @param int $client_id The Client ID
	* @param string $params['plan_type'] The name of the plan type (e.g. "paid"). Optional.
	* @param string $params['amount'] Amount filter. Optional.
	* @param int $params['interval'] Interval (days) filter. Optional.
	* @param string $params['notification_url'] Notification URL filter. Optional.
	* @param string $params['name'] Name filter.  Optional.
	* @param int $params['free_trial'] Number of days of a free trial. Optional.
	* @param int $params['offset'] Database query offset.  Optional.  Defaults to 0.
	* @param int $params['limit'] Database query limit.  Optional.  Defaults to config value.
	*
	* @return array|bool Returns an array of all plans matching query or FALSE if none.
	*/	
	function GetPlans($client_id, $params)
	{		
		if(isset($params['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($params['plan_type']);
			$this->db->where('plans.plan_type_id', $plan_type_id);
		}
		
		if(isset($params['amount'])) {
			$this->db->where('amount', $params['amount']);
		}
		
		if(isset($params['interval'])) {
			$this->db->where('interval', $params['interval']);
		}
		
		if(isset($params['notification_url'])) {
			$this->db->where('notification_url', $params['notification_url']);
		}
		
		if(isset($params['name'])) {
			$this->db->where('name', $params['name']);
		}
		
		if(isset($params['free_trial'])) {
			$this->db->where('free_trial', $params['free_trial']);
		}
		
		if (isset($params['offset'])) {
			$offset = $params['offset'];
		}
		else {
			$offset = 0;
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit'], $offset);
		}
		
		$this->db->join('plan_types', 'plans.plan_type_id = plan_types.plan_type_id', 'inner');
		$this->db->where('client_id', $client_id);
		$this->db->where('deleted', 0);
		$query = $this->db->get('plans');
		$data = array();
		if($query->num_rows() > 0) {
			foreach($query->result() as $row)
			{
				$data[] = array(
								'id' => $row->plan_id,
								'type' => $row->type,
								'name' => $row->name,
								'amount' => $row->amount,
								'interval' => $row->interval,
								'notification_url' => $row->notification_url,
								'free_trial' => $row->free_trial
								);
			}
			
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	/*
	* Marks a plan as deleted
	*
	* @param int $client_id The Client ID
	* @param int $plan_id The ID of the plan
	*
	* @return bool TRUE upon success
	*
	*/	
	function DeletePlan($client_id, $plan_id)
	{
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $plan_id);
		
		$update_data['deleted'] = 1;
		$this->db->where('plan_id', $plan_details->plan_id);
		$this->db->update('plans', $update_data);
		
		return TRUE;
	}
	
	/**
	* Verifies that the plan exists, is available to the client, and is active
	*
	* @param int $client_id The Client ID
	* @param int $plan_id The Plan ID
	*
	* @return array Plan information
	*/	
	function GetPlanDetails($client_id, $plan_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->where('plan_id', $plan_id);
		$this->db->where('deleted', 0);
		$query = $this->db->get('plans');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(7001));
		}	
	}
	
	/**
	* Gets the ID number for a plan type by plan type namme
	*
	* @param string $type The name of the plan type
	*
	* @return int The ID of the plan type
	*/	
	function GetPlanTypeId($type)
	{
		$this->db->where('type', $type);
		$query = $this->db->get('plan_types');
		if($query->num_rows() > 0) {
			$plan_type_id = $query->row()->plan_type_id;
		} else {
			die($this->response->Error(7000));
		}
		
		return $plan_type_id;
	}
	
	/**
	* Gets the plan type name by the plan type ID
	*
	* @param int $plan_type_id The ID of the plan type
	*
	* @return string The name of the plantype
	*/	
	function GetPlanType($plan_type_id)
	{
		$this->db->where('plan_type_id', $plan_type_id);
		$query = $this->db->get('plan_types');
		if($query->num_rows() > 0) {
			$plan_type = $query->row()->type;
		} else {
			die($this->response->Error(7000));
		}
		
		return $plan_type;
	}
}