<?php
namespace T3census\Detection;


abstract class AbstractProcessor {

	/**
	 * @var  \T3census\Detection\ProcessorInterface|null
	 */
	protected $successor = NULL;


	/**
	 * @param \T3census\Detection\ProcessorInterface $successor
	 */
	public function setSuccessor(\T3census\Detection\ProcessorInterface $successor) {
		$this->successor = $successor;
	}

	/**
	 * Processes context.
	 *
	 * @param  \T3census\Detection\Context  $context
	 * @return  void
	 */
	public function process(\T3census\Detection\Context $context) {
		if (!is_null($this->successor)) {
			$this->successor->process($context);
		}
	}
}