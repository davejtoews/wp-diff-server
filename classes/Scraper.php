<?php 


class Scraper {

	/**
	 * credentials
	 * @var string|array
	 */
	private $httpAuth;

	/**
	 * Root URL of website
	 * @var string
	 */
	public $rootUrl;

	/**
	 * Location of sitemap
	 * @var string
	 */
	public $sitemapUrl;

	/**
	 * List of endpoints
	 * @var object
	 */
	public $endpoints;

	/**
	 * Path to markup
	 * @var string
	 */
	public $muPath;

	/**
	 * App logger
	 * @var boolean|object
	 */
	private $logger;

	/**
	 * @param string
	 * @param string
	 * @param boolean|string
	 * @param boolean|string
	 */
	public function __construct($rootUrl, $sitemapUrl, $username = false, $password = false, $logger = false) {
		
		$this->rootUrl = $rootUrl;
		$this->sitemapUrl = $sitemapUrl;

		if ($username && $password) {
			$httpAuth = $username . ":" . $password;
		} else {
			$httpAuth = false;
		}

		$urlData = parse_url($this->rootUrl);
		$this->muPath = '../markup/' . $urlData['host'] . '/';

		$this->logger = $logger;

		$sitemap = $this->getMarkup($this->sitemapUrl);
		$this->endpoints = json_decode($sitemap);
	}

	public function scrape() {
		$endpointPathArrayList = array_map(array($this,"getEndpointPathArray"), $this->endpoints);

		foreach ($endpointPathArrayList as $endpointPathArray) {
			$this->writeEndpointToPath($endpointPathArray);
		}
	}

	/**
	 * @param  string
	 * @return string
	 */
	private function getMarkup($url) {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if($this->httpAuth) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->httpAuth);  
		}
		
		$output = curl_exec($ch);
		curl_close($ch);

		return $output;
	}

	/**
	 * @param  string
	 * @return string
	 */
	private function getFilePath($endpoint) {
		if(substr($endpoint, -1) == '/') {
		    $endpoint = substr($endpoint, 0, -1);
		}

		if ($endpoint == $this->rootUrl) {
			return "/ROOT";
		} else if (substr($endpoint, 0, strlen($this->rootUrl)) == $this->rootUrl) {
	    	return substr($endpoint, strlen($this->rootUrl));
		} else {
			return $endpoint;
		}
	}

	private function getEndpointPathArray($endpoint) {
		return array(
			'url'		=>	$endpoint,
			'filePath' 	=>	$this->getFilePath($endpoint)
		);
	}

	private function writeEndpointToPath($endpointPathArray) {
		$urlData = parse_url($this->rootUrl);

		$filePath = $this->muPath . $endpointPathArray['filePath'] . '.mu';
		$dirname = dirname($filePath);
		if (!is_dir($dirname)) {
		    mkdir($dirname, 0755, true);
		}

		$file = fopen($filePath, 'w+');
		$markup = $this->getMarkup($endpointPathArray['url']);

		if ($this->logger) {
			$this->logger->info($filePath);
		}

		fwrite($file, $markup);
		fclose($file);
	}

}