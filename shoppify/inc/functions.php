<?php
/*require_once(dirname(__DIR__) . '/vendor/autoload.php');*/

define('SHOPIFY_APP_SECRET', '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');

/**
 * shopify_call
 *
 * @param  mixed $token
 * @param  mixed $shop
 * @param  mixed $api_endpoint
 * @param  mixed $query
 * @param  mixed $method
 * @param  mixed $request_headers
 *
 * @return void
 */
function shopify_call($token, $shop, $api_endpoint, $query = array(), $method = 'GET', $request_headers = array())
{

	// Build URL
	$url = "https://" . $shop . ".myshopify.com" . $api_endpoint;
	if (!is_null($query) && in_array($method, array('GET', 	'DELETE'))) $url = $url . "?" . http_build_query($query);

	// Configure cURL
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, TRUE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 3);
	// curl_setopt($curl, CURLOPT_SSLVERSION, 3);
	curl_setopt($curl, CURLOPT_USERAGENT, 'My New Shopify App v.1');
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

	// Setup headers
	$request_headers[] = "";
	if (!is_null($token)) $request_headers[] = "X-Shopify-Access-Token: " . $token;
	curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);

	if ($method != 'GET' && in_array($method, array('POST', 'PUT'))) {
		if (is_array($query)) $query = http_build_query($query);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
	}

	// Send request to Shopify and capture any errors
	$response = curl_exec($curl);
	$error_number = curl_errno($curl);
	$error_message = curl_error($curl);

	// Close cURL to be nice
	curl_close($curl);

	// Return an error is cURL has a problem
	if ($error_number) {
		return $error_message;
	} else {

		// No error, return Shopify's response by parsing out the body and the headers
		$response = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);

		// Convert headers into an array
		$headers = array();
		$header_data = explode("\n", $response[0]);
		$headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
		array_shift($header_data); // Remove status, we've already set it above
		foreach ($header_data as $part) {
			$h = explode(":", $part);
			$headers[trim($h[0])] = trim($h[1]);
		}

		// Return headers and Shopify's response
		return array('headers' => $headers, 'response' => $response[1]);
	}
}

// shippo functions

/**
 * retrieveShippoOrderObject
 *
 * @param  mixed $api_token
 * @param  mixed $start_date
 * @param  mixed $order_number
 *
 * @return void
 */
function retrieveShippoOrderObject($api_token, $start_date, $order_number)
{
	$order_object = '';
	$api_url = 'https://api.goshippo.com/orders/?start_date=' . $start_date;
	$headers = array(
		'Authorization: ShippoToken ' . $api_token
	);
	$opts = [
		CURLOPT_URL            => $api_url,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_RETURNTRANSFER => true,
	];

	$curl_token = curl_init();
	curl_setopt_array($curl_token, $opts);
	$response = json_decode(curl_exec($curl_token), true);
	curl_close($curl_token);

	foreach ($response['results'] as $order) {
		if ($order['order_number'] == $order_number) {
			$order_object = $order['object_id'];
		}
	}

	return $order_object;
}

/**
 * retrieveCareers
 *
 * @param  mixed $api_token
 * @param  mixed $carrier
 *
 * @return void
 */
function retrieveCareers($api_token, $carrier = '')
{
	$carrier_object = '';
	$api_url = 'https://api.goshippo.com/carrier_accounts';
	if ($carrier) {
		$api_url = $api_url . '/?carrier=' . $carrier;
	}
	$headers = array(
		'Authorization: ShippoToken ' . $api_token
	);

	$curl_token = curl_init();
	$opts = [
		CURLOPT_URL            => $api_url,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_RETURNTRANSFER => true,
	];
	curl_setopt_array($curl_token, $opts);
	$response = json_decode(curl_exec($curl_token), true);
	curl_close($curl_token);

	foreach ($response['results'] as $career) {
		if ($career['active'] == 1) {
			$carrier_object = $career['object_id'];
			return $carrier_object;
		}
	}

	return $carrier_object;
}


/**
 * generateShippingLabel
 *
 * @param  mixed $api_token
 * @param  mixed $address_from
 * @param  mixed $address_to
 * @param  mixed $parcel
 * @param  mixed $carrier_account
 * @param  mixed $order_object_id
 *
 * @return void
 */
function generateShippingLabel($api_token, $address_from, $address_to, $parcel, $carrier_account, $order_object_id)
{
	$label_url = '';
	$data = array(
		'shipment' => array(
			'address_from' => $address_from,
			'address_to'   => $address_to,
			'parcels'      => array($parcel),
		),
		'carrier_account' => $carrier_account,
		'servicelevel_token' => 'usps_parcel_select',
		'order' => $order_object_id,
	);

	$transaction = Shippo_Transaction::create($data, $api_token);

	if ($transaction['status'] == 'SUCCESS') {
		$label_url = $transaction['label_url'];
	}
	return $label_url;
}

function verify_webhook($data, $hmac_header)
{
	$calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
	return hash_equals($hmac_header, $calculated_hmac);
}
