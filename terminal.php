<?php

/* tool to process requests for hourglass, running from terminal instead of web server */

require_once('hourglass.lib.php');
$additions_source = 'additions.json';

$prompt = "\nHOURGLASS> ";

do {
	$running = true;
	echo $prompt;
	$cmd_raw = strtolower(trim(fgets(STDIN)));
	switch($cmd_raw)
	{
	case 'quit':
		$running = false;
		break;
	case 'stats':
		print_r(getstats($sqlite_db));
		break;
	case 'import':
		print_r(importfile($hourglass_input,$sqlite_db));
		break;
	case 'totals':
		print_r(gettotals($sqlite_db));
		break;
	case 'report':
		ob_start();
		csv_report(createreport($sqlite_db));
		$csv = ob_get_clean();
		file_put_contents($report_output,$csv);
		echo "\nData dumped to {$report_output}\n";
		break;
	case 'contact':
		$res = createcontact($sqlite_db,$additions_source);
//		print_r($res); break;
		$recs = $res['result'];
		file_put_contents($contact_output,csv_contact($recs));
		echo "\nContact list created in {$contact_output}\n";
		break;
	case 'help':
	default:
		help();
		break;
	}
}while($running);

echo "\nExiting\n";

function help()
{
	echo <<<END
Supported Commands:
	QUIT	exit the program
	HELP	this help
	STATS	show stats
	IMPORT	populate records
	REPORT	create report
	TOTALS	show running totals for pioneers
END;
}

function csv_report($a)
{
	// the data is already sorted by pioneer field
	$pioneer = null;
	foreach($a as $r)
	{
		if($pioneer !== $r['pioneer']) echo "\n\nLast name,First name,Group,Pioneer,Month,Placements,Videos,Hours,Credits,RVs,Studies,6 Month Avg (no Credit),6 Month Avg (With Credit)\n";
		echo implode(',',$r) . "\n";
		$pioneer = $r['pioneer'];
	}
//	print_r($a);
}

function csv_contact($a)
{
	ob_start();
print_r($a);
	return ob_get_clean();
}