<?php

require_once("common.php");

$source = "accesspoint";

$internal_interface="wlan1";
$external_interface="eth0";

$allowed_protocols = array("udp", "tcp");

$authorized = true;

$postdata = null;

if ($_POST) {
	$postdata = $_POST;
}


if ($postdata) {
	
	if (!check_login()) {
		$authorized = false;
		$formErrors["unauthorized"] = true;
		// give a response
		$response = array(
			"errors" => $formErrors
		);
		error_response($response);
		return;
	}
	
	$service = $postdata['service'];
	
	$server = $postdata['server'];
	$port = intval($postdata['port']);
	$protocol = $postdata['protocol'];
	$username = $postdata['username'];
	$password = $postdata['password'];
	$ca_cert = $postdata['ca_cert'];
	
	
	$continue = true;
	$formErrors = array();
	
	
	

	if ($service == "tor") {
		`sudo ../../scripts/tor/enable.sh --interface=$internal_interface`;
		$response = "";
		success_response($response);
	} else if ($service == "private") {
		$scrubbed_server = escapeshellarg($server);
		$scrubbed_port = escapeshellarg($port);
		$scrubbed_protocol = escapeshellarg($protocol);
		$scrubbed_username = escapeshellarg($username);
		$scrubbed_password = escapeshellarg($password);
		$scrubbed_ca_cert = escapeshellarg($ca_cert);
		
		
		if (!$server) {
			$formErrors["vpn_server"];
			$continue = false;
		}
		
		if (!is_int($port) or ($port > 65535) or ($port < 1)) {
			$formErrors["vpn_port"] = true;
			$continue = false;
		}
		
		if (!in_array($protocol, $allowed_protocols)) {
			$formErrors["vpn_protocol"] = true;
			$continue = false;
		}
		
		if ($username and !$password) {
			$formErrors['vpn_password'] = true;
			$continue = false;
		}
		if (!$username and $password) {
			$formErrors['vpn_username'] = true;
			$continue = false;
		}

		if (!$username and !$password and !$ca_cert) {
			$formErrors['vpn_ca_cert'] = true;
			$continue = false;
		}
		
		if ($continue) {
			`sudo ../../scripts/disable_vpn_tor.sh`;
			
			`sudo ../../scripts/vpn/set_auth_setting.sh --username=$scrubbed_username --password=$scrubbed_password`;
			

			`sudo ../../scripts/vpn/set_settings.sh --server=$scrubbed_server --port=$scrubbed_port --proto=$scrubbed_protocol`;
			
			`sudo ../../scripts/vpn/set_auth_settings.sh --username=$scrubbed_username --password=$scrubbed_password`;
			
			`sudo ../../scripts/vpn/set_ca_cert.sh --ca_cert=$scrubbed_ca_cert`;
			
			`sudo ../../scripts/vpn/enable.sh --interface=$internal_interface`;

			$response = "";
			success_response($response);
		} else {
			// give a response
			$response = array(
				"errors" => $formErrors
			);
			error_response($response);
			
		} 
		
	} else {
		// determine our working network interface
		$wlan0_exists = intval(`sudo ../scripts/interface_exists --interface=wlan0`);
		$external_interface="eth0";
		if ($wlan0_exists) {
			$external_interface="wlan0";
		}
		`sudo ../../scripts/disable_vpn_tor.sh --internal_interface=$internal_interface --external_interface=$external_interface`;
		
		$response = "";
		success_response($response);	
	}

		
}

?>