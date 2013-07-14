<?php
namespace T3census\Gearman;


class Serverstatus {

	protected $host = NULL;

	protected $port = NULL;

	protected $status = array();


	public function __construct($host = '127.0.0.1', $port = 4730) {
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * @param string $host
	 */
	public function setHost($host) {
		$this->host = $host;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port) {
		$this->port = $port;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPort() {
		return $this->port;
	}

	public function hasFunction($functionName) {
		return (is_array($this->status) && array_key_exists('operations', $this->status) && array_key_exists($functionName, $this->status['operations']));
	}

	public function getNumberOfWorkersByFunction($functionName) {
		$numberWorkers = 0;

		if ($this->hasFunction($functionName)) {
			$numberWorkers = intval($this->status['operations'][$functionName]['connectedWorkers']);
		}

		return $numberWorkers;
	}


	public function poll() {
		$errorNumber = 0;
		$errorString = '';

		$handle = @fsockopen($this->host, $this->port, $errorNumber, $errorString, 10);
		if ($handle != NULL){
			fwrite($handle,"status\n");
			while (!feof($handle)) {
				$line = fgets($handle, 4096);
				if( $line==".\n"){
					break;
				}
				if( preg_match("~^(.*)[ \t](\d+)[ \t](\d+)[ \t](\d+)~",$line,$matches) ){
					$function = $matches[1];
					$this->status['operations'][$function] = array(
						'function' => $function,
						'total' => $matches[2],
						'running' => $matches[3],
						'connectedWorkers' => $matches[4],
					);
				}
			}
			fwrite($handle,"workers\n");
			while (!feof($handle)) {
				$line = fgets($handle, 4096);
				if( $line==".\n"){
					break;
				}
				// FD IP-ADDRESS CLIENT-ID : FUNCTION
				if( preg_match("~^(\d+)[ \t](.*?)[ \t](.*?) : ?(.*)~",$line,$matches) ){
					$fd = $matches[1];
					$this->status['connections'][$fd] = array(
						'fd' => $fd,
						'ip' => $matches[2],
						'id' => $matches[3],
						'function' => $matches[4],
					);
				}
			}
			fclose($handle);
		} else {
			throw new \GearmanException($errorString, $errorNumber);
		}

		#print_r($this->status);
		return $this;
	}
}