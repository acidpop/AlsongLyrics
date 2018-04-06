<?php


class LogWriter
{
	public function Log($text)
	{
		date_default_timezone_set('Asia/Seoul');
		$log_file = fopen("/tmp/alsong.log", "a");
		$timeText = (string)date("[Y-m-d H:i:s]");
		fwrite($log_file, $timeText." ");
		fwrite($log_file, $text."\r\n");  
		fclose($log_file);
	}
}

$args = getopt('', array(
	'action:',
	'text:'
		));

$inst = new LogWriter();

if ('Log' === $args['action']) {
	$inst->Log($args['text']);
}


?>
