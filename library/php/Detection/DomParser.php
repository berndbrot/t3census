<?php
namespace T3census\Detection;


class DomParser {

	/**
	 * DOM to parse
	 *
	 * @var  string
	 */
	protected $content;

	/**
	 * Keeps content of meta tag generator.
	 *
	 * @var  string
	 */
	protected $metaGenerator;


	public function __construct($content = NULL) {
		if (!is_null($content)) {
			$this->setContent($content);
		}
	}

	/**
	 * @param string  $content
	 * @return  \T3census\Detection\DomParser
	 */
	public function setContent($content) {
		if (!is_string($content)) {
			throw new InvalidArgumentException(
				sprintf('Invalid argument for method %s:%s()',
					get_class($this),
					'setContent'
				),
				1373929900
			);
		}

		if (strpos($content, 'DOCTYPE')) {
			$content = preg_replace('/<!DOCTYPE.*>/sU','', $content);
		}

		$this->content = $content;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param string $metaGenerator
	 * @return  \T3census\Detection\DomParser
	 */
	public function setMetaGenerator($metaGenerator) {
		$this->metaGenerator = $metaGenerator;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMetaGenerator() {
		return $this->metaGenerator;
	}

	public function parse() {
		if (is_null($this->content) || !is_string($this->content)) {
			throw new \RuntimeException(
				sprintf('No data to parse in %s:$s()',
					get_class($this),
					'parse'
				),
				1373928990
			);
		}
		libxml_use_internal_errors(TRUE);

		$metaGenerator = '';
		$dom = new \DOMDocument();
		$dom->loadHTML($this->content);
		$xpath = new \DOMXPath($dom);

		// Look for the content attribute of description meta tags
		$generators = $xpath->query('/html/head/meta[@name="generator"]/@content');

		// If nothing matches the query
		if ($generators->length == 0) {
			// Found one or more descriptions, loop over them
		} else {
			foreach ($generators as $generator) {
				$metaGenerator .= $generator->value;
			}
		}

		if (is_string($metaGenerator) && strlen($metaGenerator) > 0) {
			$this->metaGenerator = $metaGenerator;
		}

		libxml_clear_errors();
	}
}