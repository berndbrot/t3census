<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 25.05.13
 * Time: 21:27
 * To change this template use File | Settings | File Templates.
 */
require_once 'Typo3HostDetectorWorker.php';

declare(ticks = 1); //this is needed to catch signals

//get a new fetcher by default connecting to localhost:4730
$fetcher = new Typo3HostDetectorWorker();

$pid = pcntl_fork();

if ($pid == -1)
{
	die("could not fork\n");
} else if ($pid) {
	// After the bird exit the parent process this closes the console
	// and let us operate as a daemon
	exit(0);
} else {
	// we are the child now operating the fetcher
	// catch some signals
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGHUP,  "sig_handler");
	pcntl_signal(SIGINT,  "sig_handler");

	$fetcher->run();
}

// the signal handler is very useless here normally it does some
// cleanup of the daemon before it exits the child
function sig_handler($signo) {
	echo "Signal {$signo} received\n";
	flush();
	switch($signo) {
		case SIGTERM:
			exit(0);
			break;
		case SIGHUP:
			exit(0);
			break;
		case SIGINT:
			exit(0);
			break;
	}
}