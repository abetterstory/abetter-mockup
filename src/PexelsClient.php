<?php

namespace ABetter\Mockup;

class PexelsClient {

	private $token;
	private $client;

	public function __construct($token=NULL) {
		$this->token = $token ?: env('MOCKUP_PEXELS_KEY');
	}

	private function getClient() {
		if ($this->client === NULL) {
			$this->client = new \GuzzleHttp\Client([
				'base_uri' => 'https://api.pexels.com/v1/',
				'headers' => [
					'Authorization' => $this->token
				]
			]);
		}
		return $this->client;
	}

	public function search($query, $size = 15, $page = 1) {
		return $this->getClient()->get('search?'.http_build_query([
			'query' => $query,
			'per_page' => $size,
			'page' => $page
		]));
	}

	public function curated($size = 15, $page = 1) {
		return $this->getClient()->get('curated?'.http_build_query([
			'per_page' => $size,
			'page' => $page
		]));
	}

}
