<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . 'config.php';

function printPre($object) {
	echo "<pre>";
	print_r($object);
	echo "</pre>";
}

function getMarkup($url, $username = false, $password = false) {
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if($username && $password) {
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);  
	}
	
	$output = curl_exec($ch);
	curl_close($ch);

	return $output;
}

function getFilePath($endpoint) {
	if(substr($endpoint, -1) == '/') {
	    $endpoint = substr($endpoint, 0, -1);
	}

	if ($endpoint == ROOT_URL) {
		return "/ROOT";
	} else if (substr($endpoint, 0, strlen(ROOT_URL)) == ROOT_URL) {
    	return substr($endpoint, strlen(ROOT_URL));
	} else {
		return $endpoint;
	}
}

function getEndpointPathArray($endpoint) {
	return array(
		$endpoint,
		getFilePath($endpoint)
	);
}

function writeEndpointToPath($endpointPathArray) {
	$filePath = __DIR__ . '/markup' . $endpointPathArray[1];
	$dirname = dirname($filePath);
	if (!is_dir($dirname)) {
	    mkdir($dirname, 0755, true);
	}

	$file = fopen($filePath, 'w+');
	$markup = getMarkup($endpointPathArray[0], USERNAME, PASSWORD);

	fwrite($file, $markup);
	fclose($file);
}

$sitemap = getMarkup(SITEMAP_URL, USERNAME, PASSWORD);
$endpoints = json_decode($sitemap);

$endpointPathArrayList = array_map('getEndpointPathArray', $endpoints);

//foreach ($endpointPathArrayList as $endpointPathArray) {
//	writeEndpointToPath($endpointPathArray);
//}

$repo = false;

try {
	$repo = Git::open(__DIR__ .'/markup');
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}

if (!$repo) {
	$repo = Git::create(__DIR__ .'/markup');
	echo "Repository Created \n";
}
printPre($repo->status());

$repo->add('.');
printPre($repo->status());

try {
	$repo->commit(time());
} catch (Exception $e) {
	echo "Failed to commit \n";
}

printPre($repo->status());

