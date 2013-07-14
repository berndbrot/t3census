<?php
namespace T3census\Bing\Scraper;

use T3census\Url\UrlFetcher;

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../../../library/php');
$vendorDir = realpath($dir . '/../../../../vendor');

require_once $libraryDir . '/Url/UrlFetcher.php';
require_once $vendorDir . '/autoload.php';


class ReverseIpLookup {

	protected $endpoint = NULL;

	protected $query = NULL;

	protected $isProcessed = FALSE;

	protected $offset = 1;

	protected $maxResults = NULL;

	protected $results = array();


	public function __construct($endpoint = NULL) {

		if (!is_null($endpoint)) {
			$this->setEndpoint($endpoint);
		}

		\Purl\Autoloader::register();
	}

	/**
	 * @param null $endpoint
	 */
	public function setEndpoint($endpoint) {
		if (is_string($endpoint)) {
			$this->endpoint = $endpoint;
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getEndpoint() {
		return $this->endpoint;
	}

	/**
	 * @return null
	 */
	public function getMaxResults() {
		return $this->maxResults;
	}

	/**
	 * @param null $maxResults
	 */
	public function setMaxResults($maxResults) {
		if (is_null($maxResults) || is_int($maxResults)) {
			$this->maxResults = $maxResults;
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset) {
		if (is_int($offset)) {
			$this->offset = $offset;
		}

		return $this;
	}

	/**
	 * @param null $query
	 */
	public function setQuery($query) {
		if (is_string($query)) {
			$this->query = $query;
			unset($this->results); $this->results = array();
			$this->isProcessed = FALSE;
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getQuery() {
		return $this->query;
	}

	public function getResults() {
		if (!$this->isProcessed) {
			libxml_use_internal_errors(TRUE);
			$objHttpClient = new UrlFetcher();

			$urls = array();
			for ($i=0; $i< 100; $i++) {
				$url = sprintf('%s?q=%s&first=%u',
					$this->endpoint,
					urlencode($this->query),
					$this->offset
				);
				$objHttpClient->setUrl($url)->fetchUrl(UrlFetcher::HTTP_GET, FALSE, FALSE);
				$body = $objHttpClient->getBody();


				$dom = new \DOMDocument();
				$dom->loadHTML($body);
				$xpath = new \DOMXPath($dom);

				$urls = array_merge($urls, $this->extractUrlsByXpath($xpath));


				$objHttpClient->reset();

				$this->offset += 10;

				if (!is_null($this->maxResults) && is_int($this->maxResults) && $this->offset > $this->maxResults) {
					unset($xpath, $dom, $objHttpClient);
					break;
				} elseif (!$this->hasNext($xpath)) {
					unset($xpath, $dom, $objHttpClient);
					break;
				} else {
					unset($xpath, $dom);
					$objHttpClient->reset();
					continue;
				}
			}

			libxml_clear_errors();

			natsort($urls);
			$urls = array_reverse($urls);

			$lastInsertedUrl = NULL;
			$mergedUrls = array();
			foreach ($urls as $url) {
				if (is_string($lastInsertedUrl)) {
					/* @var $currentUrl \Purl\Url */
					$currentUrl = $this->getSimplifiedUrl(\Purl\Url::parse($url));

					if (0 !== strcmp($lastInsertedUrl, $currentUrl)) {
						$mergedUrls[] = $currentUrl;
						$lastInsertedUrl = $currentUrl;
					}
				} else {
					/* @var $currentUrl \Purl\Url */
					$currentUrl = $this->getSimplifiedUrl(\Purl\Url::parse($url));
					$mergedUrls[] = $currentUrl;
					$lastInsertedUrl = $currentUrl;
				}
			}

			$this->results = $mergedUrls;
		}

		return $this->results;
	}

	public function reset() {
		$this->endpoint = $this->query = NULL;
		unset($this->results); $this->results = array();
		$this->isProcessed = FALSE;
		$this->offset = 1;

		return $this;
	}

	protected function hasNext($xpath) {
		// Look for the content attribute of description meta tags
		$generators = $xpath->query('//a[@class="sb_pagN"]');

		return ($generators->length > 0);
	}

	protected function extractUrlsByXpath($xpath) {
		$urls = array();

		// Look for the content attribute of description meta tags
		$generators = $xpath->query('//h3/a/@href');

		// If nothing matches the query
		if ($generators->length == 0) {
			// Found one or more descriptions, loop over them
		} else {
			foreach ($generators as $generator) {
				$urls[] = $generator->value;
			}
		}

		return $urls;
	}

	protected function getSimplifiedUrl(\Purl\Url $url) {
		$simplifiedUrl= '';

		$simplifiedUrl .= $url->get('scheme') . '://';
		$simplifiedUrl .= $url->get('host');
		$simplifiedUrl .= (!is_null($url->get('port')) ? ':' . $url->get('port') .'/' : '/');

		return $simplifiedUrl;
	}
}