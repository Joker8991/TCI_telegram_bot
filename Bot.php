<?php

define('OVERPASS_API_URL', 'http://overpass-api.de/api/interpreter');
define('GITHUB_API_URL','https://api.github.com/repos/');
define('ISSUE_PATH','emergenzeHack/terremotocentro_segnalazioni/issues');

class Bot
{


	function getUpdates()
	{
		$content = file_get_contents("php://input");
		$update = json_decode($content, true);

		if (!$update)
			exit;
		
		return $update;
	}

	function exec_curl_request($handle, &$headerSize=false) {
	  $response = curl_exec($handle);
	  
	  if ($response === false) {
	    $errno = curl_errno($handle);
	    $error = curl_error($handle);
	    error_log("Curl returned error $errno: $error\n");
	    curl_close($handle);
	    return false;
	  }

	  if($headerSize)
	  	$headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);

	  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
	  curl_close($handle);

	  if ($http_code >= 500) {
	    // do not wat to DDOS server if something goes wrong
	    sleep(10);
	    return false;
	  } else if ($http_code != 200) {
	  	$response = json_decode($response, true);
	    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
	    if ($http_code == 401) {
	      throw new Exception('Invalid access token provided');
	    }
	    return false;
	  }
	  else {
    	$decoded =json_decode($response, true);
    	if (isset($decoded['description'])) {
      		error_log("Request was successfull: {$decoded['description']}\n");
    	} 
	  
	  return $response;
	}
  }


	function apiRequest($method, $parameters) {
		if (!is_string($method))
		{
	    	error_log("Il metodo deve essere in formato stringa\n");
	    	return false;
	  	}

	  	if (!$parameters)
	  	{
	    	$parameters = array();
	  	}
	  	else if (!is_array($parameters))
	  	{
	    	error_log("I parameters devono essere in un array\n");
	    	return false;
	  	}

	  	foreach ($parameters as $key => &$val)
	  	{
	    	// encoding to JSON array parameters, for example reply_markup
	    	if (!is_numeric($val) && !is_string($val))
	    	{
	    		$val = json_encode($val);
	    	}
	  	}
	  	$botToken = getenv('BOT_TOKEN');
	  	$parameters = http_build_query($parameters);
	  	$url = "https://api.telegram.org/bot%s/%s?%s";
	  	$url = sprintf($url, $botToken, $method, $parameters);
	  	
	  	$handle = curl_init($url);
	  	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	  	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
	  	curl_setopt($handle, CURLOPT_TIMEOUT, 10);

	  	$ret = $this -> exec_curl_request($handle);
	  	if($ret != false)
	  	{
	  		$ret = json_decode($ret,true);
	  		return $ret['result'];
	  	}
	  	
	  	return $ret;
	}


	function overpassMapRequest($lat,$lon,$tag)
	{
		//cerco tutti gli elementi nel raggio di 10 km.
		$parameters = array('data'=>"[out:json];node(around:10000.0,$lat,$lon)$tag;out body;");
		$url = OVERPASS_API_URL.'?'.http_build_query($parameters);
		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	  	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
	  	curl_setopt($handle, CURLOPT_TIMEOUT, 10);
	  	$ret = $this -> exec_curl_request($handle);
		if($ret != false)
		{
	  		$ret = json_decode($ret,true);
	  		return $ret['elements']; 
		}
		return $ret;
	}

	function githubApiRequest($parameters)
	{
		//url di test
		//$url = "https://api.github.com/search/code?q=addClass+user:mozilla";
		$url = GITHUB_API_URL.ISSUE_PATH.'?'.http_build_query($parameters);
		$handle = curl_init($url);
		$curlVersion = curl_version();
		//github richiede che lo user-agent sia settato
		curl_setopt($handle, CURLOPT_USERAGENT, 'curl/' . $curlVersion['version'] );
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_HTTPHEADER, array( 'Accept: application/json'));

		$header_size=true;
		$ret = $this -> exec_curl_request($handle, $header_size);
		
		if($ret != false)
		{
			$nextPage = null;
			$matches=array();
			//$header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
			/*Github pagina i risultati -> l'header contiene i link alla pagina successiva e all'ultima pagina
			 *se ce n'è più di una
			 */ 
			$header = substr($ret, 0, $header_size);
			$content = json_decode(substr($ret, $header_size), true);
			if(preg_match('/page=[1-9]{1}[0-9]*>; rel="next"/', $header, $matches))
			{
				$nextPage = (substr($matches[0], 5,-13));
			}
			return array('content' => $content, 'nextPage' => $nextPage);
		}
	}
}

?>
