<?php
class exalt
{
    var $code, $title, $description, $enabled;
	function exalt()
	{
		global $order;
		
		$this->signature = 'exalt|3.0';		
		$this->code = 'exalt';
		$this->title = MODULE_PAYMENT_EXALT_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_EXALT_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_PAYMENT_EXALT_SORT_ORDER;
		$this->enabled = (defined('MODULE_PAYMENT_EXALT_STATUS') && MODULE_PAYMENT_EXALT_STATUS == 'True') ? true : false;
		
		if ($this->enabled = true)
		{
			//If undefined or zero string
			if ((!defined('MODULE_PAYMENT_EXALT_MERCHANT') || !strlen(MODULE_PAYMENT_EXALT_MERCHANT)) ||
				(!defined('MODULE_PAYMENT_EXALT_KEY') || !strlen(MODULE_PAYMENT_EXALT_KEY)))
				{
					$this->enabled = false;
				}
			
			//If this is still enabled
			$this->sort_order = MODULE_PAYMENT_EXALT_SORT_ORDER;			
		}
	}
	/**
	 * Toggles the $enabled status depending on the availability of
	 * the module to checkout with in the current zone.
	 */
	function update_status() {
		/* Check whether the zones/geo_zones is valid */
		global $order;
		if ($this->enabled === true && ((int)MODULE_PAYMENT_EXALT_VALID_ZONE > 0))
		{
			$check_flag = false;
			$sql = sprintf("SELECT zone_id FROM %s WHERE geo_zone_id = %d AND zone_country_id = %d ORDER BY zone_id", TABLE_ZONES_TO_GEO_ZONES, MODULE_PAYMENT_EXALT_VALID_ZONE, $order->delivery['country']['id']);
			$result = tep_db_query($sql);
			while ($row = tep_db_fetch_array($result))
			{
				if ($row['zone_id'] < 1)
				{
					$check_flag = true;
					break;
				}
				elseif ($row['zone_id'] == $order->billing['zone_id'])
				{
					$check_flag = true;
					break;
				}
			}
			if ($check_flag === false)
			{
				$this->enabled = false;
			}
		}
	}
	/**
	 * Returns the Javascript validation string for receiving CC details
	 * from the form (help make validation more efficient/effective)
	 *
	 * Originally from OSCommerce Javascript Validation form cc.php
	 *
	 * @return String
	 */
	function javascript_validation()
	{

return false;
	}
	/**
	 * Creates an array of fields that defines the required params
	 * when using this module as a potential payment method.
	 *
	 * @return array
	 */
	function selection()
	{
	global $order;
		// Setup the possibly expiry months
		for ($i = 1; $i < 13; $i++)
		{
			$expiryMonths[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)));
		}
		// Setup the possible expiry years
		$currentYear = date("Y");
		for ($i = $currentYear; $i < ($currentYear+10); $i++)
		{
			$expiryYears[] = array('id' => strftime('%y', mktime(0, 0, 0, 1, 1, $i)), 'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)));
		}
		// Build the selection array
		$selection = array(
						'id' => $this->code,
						'module' => $this->title,
						'fields' => array(
										array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_OWNER,
											  'field' => tep_draw_input_field('exalt_cc_owner', $order->billing['firstname'].' '.$order->billing['lastname'])),
										array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_NUMBER,
											  'field' => tep_draw_input_field('exalt_cc_number')),
										array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_EXPIRES,
											  'field' => tep_draw_pull_down_menu('exalt_cc_expires_month', $expiryMonths).'&nbsp;'.tep_draw_pull_down_menu('exalt_cc_expires_year', $expiryYears)),
										 array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_CVV,
                                                    'field' => tep_draw_input_field('exalt_cc_cvc', '','size="5" maxlength="4"'))));
      return $selection;
    }
	/**
	 * Performs required validation to check and ensure that the
	 * submitted form information appears correct before performming the
	 * transaction through the confirmation screen.
	 *
	 * Uses the cc_validation.php class to check the CC Number, expiry date
	 * and redirects an $error string containing the Friendly Error Message
	 * to the Checkout Payment screen
	 */
	function pre_confirmation_check()
	{
		global $HTTP_POST_VARS;
		require_once(DIR_WS_CLASSES.'cc_validation.php');
		$error = '';
		// Perform validation through the cc_validation class
		$ccValidation = new cc_validation();
		$result = $ccValidation->validate($HTTP_POST_VARS['exalt_cc_number'], $HTTP_POST_VARS['exalt_cc_expires_month'], $HTTP_POST_VARS['exalt_cc_expires_year']);
		// Validate the result
		switch ($result)
		{
			case -1 :
				$error = sprintf(TEXT_CCVAL_ERROR_UNKNOWN_CARD, substr($ccValidation->cc_number, 0, 4));
				break;
			case -2 :
			case -3 :
			case -4 :
				$error = TEXT_CCVAL_ERROR_INVALID_DATE;
				break;
			case false :
				$error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
				break;
		}
		// Redirect the user if the card data was deemed invalid
		if (($result === false) || ($result < 1))
		{
			// Build the return URL
			$paymentErrorReturn =  'payment_error='.$this->code;
			$paymentErrorReturn .= '&error_message='.urlencode($error);
			$paymentErrorReturn .= '&exalt_cc_owner='.urlencode($HTTP_POST_VARS['exalt_cc_owner']);
			$paymentErrorReturn .= '&exalt_cc_expires_month='.$HTTP_POST_VARS['exalt_cc_expires_month'];
			$paymentErrorReturn .= '&exalt_cc_expires_year='.$HTTP_POST_VARS['exalt_cc_expires_year'];
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $paymentErrorReturn, 'SSL', true, false));
		}
		// Treat the CC details to ensure they are ready to be sent to a payment gateway
		$this->cc_card_owner = $HTTP_POST_VARS['exalt_cc_owner'];
		$this->cc_card_type = $ccValidation->cc_type;
		$this->cc_card_number = $ccValidation->cc_number;
		$this->cc_expiry_month = $ccValidation->cc_expiry_month;
		$this->cc_expiry_year = $ccValidation->cc_expiry_year;
		$this->cc_cvv = $HTTP_POST_VARS['exalt_cc_cvv'];
	}
	/**
	 * Setup & return data used on the confirmation page
	 *
	 * @return array
	 */
	function confirmation()
	{
		global $HTTP_POST_VARS;
		$confirmation = array(
			'title' => $this->title,
			'fields' => array(
				array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_OWNER,
						'field' => $HTTP_POST_VARS['exalt_cc_owner']),
				array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_NUMBER,
						'field' => substr($HTTP_POST_VARS['exalt_cc_number'], 0, 4) . str_repeat('X', (strlen($HTTP_POST_VARS['exalt_cc_number']) - 8)) . substr($HTTP_POST_VARS['exalt_cc_number'], -4)),
				array('title' => MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_EXPIRES,
						'field' => strftime('%B, %Y', mktime(0,0,0,$HTTP_POST_VARS['exalt_cc_expires_month'], 1, '20' . $HTTP_POST_VARS['exalt_cc_expires_year'])))
							)
							);
							
		return $confirmation;
	}
    /**
	 * Create the required Hidden Fields that contain the payment
	 * information to be used by the Payment Gateway
	 *
	 * @return String
	 */
   	function process_button()
	{
		$processButtonString  = tep_draw_hidden_field('description', 'processCard');
		$processButtonString .= tep_draw_hidden_field('NetAmount', number_format($order->info['total'], 2));
		//$processButtonString .= tep_draw_hidden_field('transactionCurrency', 'AUD');
		//$processButtonString .= tep_draw_hidden_field('transactionProduct', uniqid($customer_id));
		$processButtonString .= tep_draw_hidden_field('created_by', trim($order->customer['firstname'].' '.$order->customer['lastname']));
		//$processButtonString .= tep_draw_hidden_field('customerCountry', $order->billing['country']['iso_code_2']);
		//$processButtonString .= tep_draw_hidden_field('customerState', $order->billing['state']);
		//$processButtonString .= tep_draw_hidden_field('customerCity', $order->billing['city']);
		//$processButtonString .= tep_draw_hidden_field('customerAddress', $order->billing['street_address'] . ((isset($order->billing['suburb']) && strlen($order->billing['suburb'])) ? ', ' . $order->billing['suburb'] : ''));
		//$processButtonString .= tep_draw_hidden_field('customerPostCode', $order->billing['postcode']);
		//$processButtonString .= tep_draw_hidden_field('customerPhone', $order->customer['telephone']);
       // $processButtonString .= tep_draw_hidden_field('customerEmail', $order->customer['email_address']);
		$processButtonString .= tep_draw_hidden_field('IP_Address', $this->getRemoteIP());
		$processButtonString .= tep_draw_hidden_field('card_number', $this->cc_card_number);
		$processButtonString .= tep_draw_hidden_field('card_holder_name', $this->cc_card_owner);
		$processButtonString .= tep_draw_hidden_field('expmonth', $this->cc_expiry_month);
		$processButtonString .= tep_draw_hidden_field('expyear', substr($this->cc_expiry_year, -2));
		$processButtonString .= tep_draw_hidden_field('cvv', $HTTP_POST_VARS['exalt_cc_cvv']);

		return $processButtonString;
	}
	/**
	 * Two step function which sends the payment, then parses
	 * the response returned from the gateway.
	 *
	 * @return boolean
	 */
	function before_process()
	{
		global $order, $HTTP_POST_VARS;
		
		// Instantiate the exalt http client
		$exaltClient = new ExaltProxy();
	
		// Use the sandbox endpoint if undefined
		$useSandbox = !(defined('MODULE_PAYMENT_EXALT_MODE') && MODULE_PAYMENT_EXALT_MODE == 'Live');		
		
		$requestData["NetAmount"] = $HTTP_POST_VARS['NetAmount'];
		//$requestData["reference"] = $HTTP_POST_VARS['transactionProduct'];
		$requestData["card_holder_name"] = $HTTP_POST_VARS['card_holder_name'];
		$requestData["card_number"] = $HTTP_POST_VARS['card_number'];
		$requestData["expmonth"] = $HTTP_POST_VARS['expmonth'];
		$requestData["expyear"] = $HTTP_POST_VARS['expiryYear'];
		$requestData["cvv"] = $HTTP_POST_VARS['cvv'];
		$requestData["description"] = $HTTP_POST_VARS['description'];
		$requestData["IP_Address"] = $HTTP_POST_VARS['IP_Address'];
		$requestData["created_by"] = $HTTP_POST_VARS['created_by'];
		$requestData["APIKey"] = MODULE_PAYMENT_EXALT_KEY;
			
		try
		{			
			$credentials = MODULE_PAYMENT_EXALT_MERCHANT . ":" . MODULE_PAYMENT_EXALT_KEY;
			$response = $exaltClient->sendChargeRequest($credentials, $useSandbox, $requestData);
			
			// Make sure the API returned something
			if (!isset($response))
			{
				$errorMessage = "Transaction Error: Payment processor did not return a valid response.";
			}
			
			// Set an error message if the transaction failed
			if ($response->charge->status_code != '0')
			{
				$errorMessage = "Transaction Error. Payment processor declined transaction: {$response->charge->error_code} {$response->charge->error}";
			}
		}
		catch (ExaltException $e)
		{
			$errorMessage = $e->getMessage();
		}
		// Set an error and redirect if something went wrong
		if (isset($errorMessage) && strlen($errorMessage))
		{
			$paymentErrorReturn =  'payment_error='.$this->code;
			$paymentErrorReturn .= '&error_message='.urlencode($errorMessage);
			$paymentErrorReturn .= '&exalt_cc_owner='.urlencode($HTTP_POST_VARS['exalt_cc_owner']);
			$paymentErrorReturn .= '&exalt_cc_expires_month='.$HTTP_POST_VARS['exalt_cc_expires_month'];
			$paymentErrorReturn .= '&exalt_cc_expires_year='.$HTTP_POST_VARS['exalt_cc_expires_year'];
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $paymentErrorReturn, 'SSL', true, false));
		}
		$order->info['cc_owner'] = $HTTP_POST_VARS['exalt_cc_owner'];
	}
	/**
	 * Perform additional required tasks post process.
	 *
	 * @return boolean Function response
	 */
    function after_process()
	{
		return false;
    }
    /**
	 * Create an array that represents the possible module errors
	 * @return Array Errors that have arrived from HTTP
	 */
	function get_error()
	{
		$error = array('title' => MODULE_PAYMENT_EXALT_TEXT_ERROR,
						'error' => (isset($_GET['error_message']) ? stripslashes(urldecode($_GET['error_message'])) : MODULE_PAYMENT_EXALT_TEXT_ERROR_DESCRIPTION));
	}
	/**
	 * Checks whether the module has been installed
	 *
	 * @return int
	 */
	function check()
	{
		if (!isset ($this->_check))
		{
			if (!isset($this->_check)) {
				$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EXALT_STATUS'");
				$this->_check = tep_db_num_rows($check_query);
			}
		}
	
		return $this->_check;
	}
	/**
	 * Performed when installing this module.
	 *
	 * SQL Inserts all the required configuration variables to be
	 * assigned by the store owner in the administration screen
	 */
	function install()
	{
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)" .
						" values ('Enable Exalt Payment Module', 'MODULE_PAYMENT_EXALT_STATUS', 'False', 'Do you want to accept payments through Exalt?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)" .
						" values ('Transaction Mode', 'MODULE_PAYMENT_EXALT_MODE', 'Test', 'Transaction Mode.', '6', '0', 'tep_cfg_select_option(array(\'Test\', \'Live\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)" .
						" values ('Merchant ID', 'MODULE_PAYMENT_EXALT_MERCHANT', '', 'The unique merchant assigned to you by Exalt.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)" .
						" values ('Merchant Password', 'MODULE_PAYMENT_EXALT_KEY', '', 'The key provided to you by Exalt.', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added)" .
						" values ('Payment Zone', 'MODULE_PAYMENT_EXALT_VALID_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)" .
						" values ('Sort order of display', 'MODULE_PAYMENT_EXALT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
	}
	/**
	 * Performs a SQL delete statement to remove all configuration
	 * variables for this payment module
	 */
    function remove()
	{
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
	/**
	 * Returns an array of defines used by this module
	 *
	 * @return Array
	 */
	function keys()
	{
		return array('MODULE_PAYMENT_EXALT_STATUS',
					 'MODULE_PAYMENT_EXALT_MODE',
					 'MODULE_PAYMENT_EXALT_MERCHANT',
					 'MODULE_PAYMENT_EXALT_KEY',
					 'MODULE_PAYMENT_EXALT_VALID_ZONE',
					 'MODULE_PAYMENT_EXALT_SORT_ORDER');
	}
	/**
	 * Returns the (best guess) customer's IP
	 *
	 * @return string
	 */
	function getRemoteIP()
	{
		$remoteIP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
		if (strstr($remoteIP, ','))
		{
			$chunks = explode(',', $remoteIP);
			$remoteIP = trim($chunks[0]);
		}
		return $remoteIP;
	}
}
  
class ExaltProxy
{  
  	public function sendChargeRequest($credentials, $useSandbox, $requestData)
	{	
        $headers = array();
		if (strlen($useSandbox) < 1)
        {
            throw new ExaltException("Exalt sandbox/live environment not set");
        }
		$environment = $useSandbox == true ? "72.16.9.248:82": "172.16.9.248:82";
		$endPoint = "https://{$environment}/api/ApiEnvoicePayment/CreatePaymentToMerchent";
		
		// Initialise CURL and set base options
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'));
        // Setup CURL request method
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $this->encodeData($requestData));		
		// Setup CURL params for this request
		curl_setopt($curl, CURLOPT_URL, $endPoint);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $credentials);	
		// Run CURL
		$response = curl_exec($curl);
   		$error = curl_error($curl);
		$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);	
        $responseObject = json_decode($response);
        if (is_object($responseObject) && $responseObject->object_type == "error")
        {
            $errorParam = strlen($responseObject->error->param) > 0 ? ". Parameter: " . $responseObject->error->param : "";
            throw new ExaltException("Exalt returned an error. Error: " . $responseObject->error->message . $errorParam);
        }
		// Check for CURL errors
		if (isset($error) && strlen($error))
		{
			throw new ExaltException("Could not successfully communicate with payment processor. Error: {$error}.");
		}
		else if (isset($responseCode) && strlen($responseCode) && $responseCode == '500')
		{
			throw new ExaltException("Could not successfully communicate with payment processor. HTTP response code {$responseCode}.");
		}
        return $responseObject;
	}
    private function encodeData($requestData)
    {
        if (!is_array($requestData))
        {
            throw new ExaltException("Request data is not in an array");
        }
        $formValues = "";
        foreach($requestData as $key=>$value) 
        { 
            $formValues .= $key.'='.urlencode($value).'&'; 
        }
        rtrim($formValues, '&');
        return $formValues;        
    }
}
 class ExaltException extends Exception {}
?>