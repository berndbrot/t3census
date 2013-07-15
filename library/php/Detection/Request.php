<?php
namespace T3census\Detection;


class Request {

	/**
	 * Keeps request URL
	 *
	 * @var  string
	 */
	protected $requestUrl;

	/**
	 * Keeps response URL
	 *
	 * @var  string
	 */
	protected $responseUrl;

	/**
	 * Keeps HTTP response code
	 *
	 * @var  integer
	 */
	protected $responseCode;

	/**
	 * Keeps response cookies
	 * @var  array
	 */
	protected $cookies;

	/**
	 * Keeps body
	 *
	 * @var  string
	 */
	protected $body;

	/**
	 * @param string $body
	 * @return  \T3census\Detection\Request
	 */
	public function setBody($body) {
		$this->body = $body;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param mixed $cookies
	 * @return  \T3census\Detection\Request
	 */
	public function setCookies($cookies) {
		$this->cookies = $cookies;

		return $this;
	}

	/**
	 * @return  mixed
	 */
	public function getCookies() {
		return $this->cookies;
	}

	/**
	 * @param  mixed  $responseCode
	 * @return  \T3census\Detection\Request
	 */
	public function setResponseCode($responseCode) {
		$this->responseCode = $responseCode;

		return $this;
	}

	/**
	 * @return  mixed
	 */
	public function getResponseCode() {
		return $this->responseCode;
	}

	/**
	 * @param  mixed  $url
	 * @return  \T3census\Detection\Request
	 */
	public function setRequestUrl($url) {
		$this->requestUrl = $url;

		return $this;
	}

	/**
	 * @return  mixed
	 */
	public function getRequestUrl() {
		return $this->requestUrl;
	}

	/**
	 * @param  mixed  $url
	 * @return  \T3census\Detection\Request
	 */
	public function setResponseUrl($url) {
		$this->responseUrl = $url;

		return $this;
	}

	/**
	 * @return  mixed
	 */
	public function getResponseUrl() {
		return $this->responseUrl;
	}
}
?>