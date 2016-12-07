<?php
class callfire_api_base
{
	/**
	* Trigger events
	* UNDEFINED_EVENT
	* INBOUND_CALL_FINISHED
	* INBOUND_TEXT_FINISHED
	* OUTBOUND_CALL_FINISHED
	* OUTBOUND_TEXT_FINISHED
	* CAMPAIGN_STARTED
	* CAMPAIGN_STOPPED
	* CAMPAIGN_FINISHED
	*/
	public $username			= '';
	public $password			= '';
	public $curl_data			= array();
	public $api_url				= '';
	public $api_method			= '';
	public $response			= null;
	public $json_response		= null;
	public $sxml				= null;

	public function __construct($arg_username, $arg_password)
	{
		$this->setUsername($arg_username);
		$this->setPassword($arg_password);
		$this->_init();
	}

	public function _init()
	{
		//overide in subclass
	}

	public function setUsername($arg_username)
	{
		$this->username	= $arg_username;
	}

	public function setPassword($arg_password)
	{
		$this->password	= $arg_password;
	}

	public function setCurlData($arg_data)
	{
		$this->curl_data	= $arg_data;
	}

	public function setApiUrl($arg_api_url)
	{
		error_log(get_class($this) . ': api url set to: ' . $arg_api_url);
		$this->api_url	= $arg_api_url;
	}

	public function setApiMethod($arg_method)
	{
		if( $arg_method != 'GET' && $arg_method != 'POST' && $arg_method != 'DELETE' )
		{
			die('callfire_api_base::setApiMethod must be GET or POST');
		}

		$this->api_method = $arg_method;
	}

	public function apiPostCall()
	{
		$post_query			= ltrim(http_build_query($this->curl_data), '&');

		$curl	= curl_init($this->api_url);
		//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
		curl_setopt($curl, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// If you experience SSL issues, perhaps due to an outdated SSL cert
  		// on your own server, try uncommenting the line below
 		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$this->response = curl_exec($curl);
		print_r(curl_getinfo($curl));
		curl_close($curl);

		//json
		$this->json_response 	= json_decode($this->response);
		
		//xml
		$this->sxml_response	= simplexml_load_string($this->response);
		
		//associative array
		$this->array_response	= json_decode(json_encode($this->sxml_response), true); 
		
		/*
		if( $this->json_response->Status == 'Failure' )
		{
			if( !empty($this->json_reponse->Errors) )
			{
				$this->errors	= $this->json_response->Errors;
			}
			return 0;
		}
		*/
		return 1;
	}

	public function apiGetCall()
	{
		$url	= $this->api_url;
		$url 	.= is_array($this->curl_data) &&  sizeof($this->curl_data) ?  '&' . http_build_query($this->curl_data) : null;
		error_log(get_class($this) . ': apiGetCall full url and query: ' . $url);
		$curl	= curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
		curl_setopt($curl, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		curl_setopt($curl, CURLOPT_POST, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// If you experience SSL issues, perhaps due to an outdated SSL cert
  		// on your own server, try uncommenting the line below
 		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$this->response = curl_exec($curl);
		curl_close($curl);

		//json
		$this->json_response = json_decode($this->response);

		//xml
		$this->sxml_response	= simplexml_load_string($this->response);
		
		//associative array
		$this->array_response	= json_decode(json_encode($this->sxml_response), true); 

		/*
		if( $this->json_response->Status == 'Failure' )
		{
			if( !empty($this->json_reponse->Errors) )
			{
				$this->errors	= $this->json_response->Errors;
			}
			return 0;
		}
		*/

		return 1;
	}

	public function apiDeleteCall()
	{
		$url	= $this->api_url;

		$curl	= curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: DELETE'));
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($curl, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$this->response = curl_exec($curl);

		curl_close($curl);

		//json
		$this->json_response = json_decode($this->response);

		//xml
		$this->sxml_response	= simplexml_load_string($this->response);
		
		//associative array
		$this->array_response	= json_decode(json_encode($this->sxml_response), true); 
	}

	public function apiCall()
	{
		switch($this->api_method)
		{
			case 'GET':
				return $this->apiGetCall();
				break;
			case 'POST':
				return $this->apiPostCall();
				break;
			case 'DELETE':
				return $this->apiDeleteCall();
				break;
			default:
				error_log('callfire_api::no call method defined - exiting');
				die();
				break;
		}
	}
}

class callfire_subscriptions extends callfire_api_base
{
	function _init()
	{
	}

	function getSubscriptions($arg_max_results, $arg_start_subscription_id)
	{
		$this->setApiMethod('GET');
		$this->setApiUrl('https://www.callfire.com/api/1.1/rest/subscription?');

		$data	= array();
		$data['MaxResults']		= $arg_max_results;
		$data['FirstResult']	= $arg_start_subscription_id;
		$this->setCurlData($data);
		return $this->apiCall();
	}

	function getSubscription($arg_subscription_id)
	{
		$this->setApiMethod('GET');
		$this->setApiUrl('https://www.callfire.com/api/1.1/rest/subscription/' . $arg_subscription_id);
		return $this->apiCall();
	}

	function deleteSubscription($arg_subscription_id)
	{
		$this->setApiMethod('DELETE');
		$this->setApiUrl('https://www.callfire.com/api/1.1/rest/subscription/' . $arg_subscription_id);
		return $this->apiCall();
	}

	/**
	* addSubscription 
	* Example:
	* 
	*	$data				= array();
	*	$endpoint_url		= 'http://my.domain.com/endpoint/to/receive/notifications';
	*
	*	$trigger_event		= 'INBOUND_TEXT_FINISHED';

	*	$data['RequestId']			= 'http://unique/url/just/to/stop/duplicate/requests'; I use a time stamp here
	*	$data['NotificationFormat']	= 'JSON';
	*	$data['Enabled']			= 'true';
	*	$data['NonStrictSsl']		= 'true';
	*	$data['Endpoint']			= $endpoint_url;
	*	$data['TriggerEvent']		= $trigger_event;
	*	$data['BroadcastId']		= $some_callfire_campaign_id

	*	$callfire_subscriptions	= new callfire_subscriptions(CALLFIRE_USER, CALLFIRE_PASSWORD);
	*	$callfire_subscriptions->addSubscription($data);
	*/

	function addSubscription($args	= array())
	{
		$required_fields	= array('RequestId', 'Enabled', 'Endpoint', 'TriggerEvent');
		foreach($required_fields as $field_name)
		{	
			echo($field_name);
			if( empty($args[$field_name]) ) { error_log('callfire_api - addSubscription missing required field: ' . $field_name); die(); }
		}

		$this->setApiUrl('https://www.callfire.com/api/1.1/rest/subscription');
		$this->setApiMethod('POST');
		$this->setCurlData($args);
		return $this->apiCall();
	}
}
?>
