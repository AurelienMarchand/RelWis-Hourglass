<?php

$hourglass_input = 'hourglass-export.json';
$sqlite_db = 'hourglass.db';
$additions_source = 'additions.json';
$report_output = 'latest-report.csv';
$contact_output = 'latest-contact.csv';

function importfile($file,$dbname)
{
	$db = new SQLite3($dbname);
	if(!$db) throw new Exception('Could not create db file ' . $dbname);

	// get the data, parse it and populate the tables
	$raw = file_get_contents($file);
	$everything = json_decode($raw);
	if(!$everything) throw new Exception('Error parsing the JSON import file');

	// cleaning existing records

	// addresses
	if(!$db->query('DROP TABLE IF EXISTS addresses')) throw new Exception('Cannot drop table "addresses"');
	if(!$db->query('CREATE TABLE "addresses" (
		"t_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		"city" TEXT,
		"country" TEXT,
		"line1" TEXT,
		"line2" TEXT,
		"postalcode" TEXT,
		"state" TEXT
	)')) throw new Exception('Cannot create table "addresses"');

	// import address data
	$stmt_addresses = $db->prepare('INSERT INTO addresses (t_id,city,country,line1,line2,postalcode,state) VALUES (:t_id,:city,:country,:line1,:line2,:postalcode,:state)');
	foreach($everything->hourglassExport->addresses as $a)
	{
		if(!isset($a->line2)) $a->line2 = NULL;
		if(!isset($a->postalcode)) $a->postalcode = NULL;
		$stmt_addresses->bindValue(':t_id',$a->t_id,SQLITE3_INTEGER);
		$stmt_addresses->bindValue(':city',$a->city,SQLITE3_TEXT);
		$stmt_addresses->bindValue(':country',$a->country,SQLITE3_TEXT);
		$stmt_addresses->bindValue(':line1',$a->line1,SQLITE3_TEXT);
		$stmt_addresses->bindValue(':line2',$a->line2,SQLITE3_TEXT);
		$stmt_addresses->bindValue(':postalcode',$a->postalcode,SQLITE3_TEXT);
		$stmt_addresses->bindValue(':state',$a->state,SQLITE3_TEXT);
		$stmt_addresses->execute();
	}

	// users
	if(!$db->query('DROP TABLE IF EXISTS users')) throw new Exception('Cannot drop table "users"');
	if(!$db->query('CREATE TABLE "users" (
		"t_id" INTEGER PRIMARY KEY AUTOINCREMENT,
		"t_group_id" INTEGER,
		"t_address_id" INTEGER,
		"status" TEXT,
		"sgo" TEXT,
		"sex" TEXT,
		"lastname" TEXT,
		"firstname" TEXT,
		"email" TEXT,
		"cellphone" TEXT,
		"firstmonth" TEXT,
		"homephone" TEXT,
		"birth" TEXT,
		"baptism" TEXT,
		"appt" TEXT
	)')) throw new Exception('Cannot create table "users"');


	// import user data
	$stmt_users = $db->prepare('
		INSERT INTO users (t_id,t_group_id,t_address_id,status,sgo,sex,lastname,firstname,email,cellphone,firstmonth,homephone,birth,baptism,appt)
		VALUES (:t_id,:t_group_id,:t_address_id,:status,:sgo,:sex,:lastname,:firstname,:email,:cellphone,:firstmonth,:homephone,:birth,:baptism,:appt)');
	foreach($everything->hourglassExport->users as $u)
	{
		if(!isset($u->email)) $u->email = NULL;
		if(!isset($u->baptism)) $u->baptism = NULL;
		if(!isset($u->appt)) $u->appt = NULL;
		if(!isset($u->homephone)) $u->homephone = NULL;
		if(!isset($u->birth)) $u->birth = NULL;
		if(!isset($u->cellphone)) $u->cellphone = NULL;
		if(!isset($u->firstmonth)) $u->firstmonth = NULL;
		if(!isset($u->t_address_id)) $u->t_address_id = NULL;
		if(!isset($u->t_group_id)) $u->t_group_id = NULL;

		$stmt_users->bindValue(':t_id',			$u->t_id,			SQLITE3_INTEGER);
		$stmt_users->bindValue(':t_group_id',	$u->t_group_id,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':t_address_id',	$u->t_address_id,	SQLITE3_INTEGER);
		$stmt_users->bindValue(':status',		$u->status,			SQLITE3_TEXT);
		$stmt_users->bindValue(':sgo',			$u->sgo,			SQLITE3_TEXT);
		$stmt_users->bindValue(':sex',			$u->sex,			SQLITE3_TEXT);
		$stmt_users->bindValue(':lastname',		$u->lastname,		SQLITE3_TEXT);
		$stmt_users->bindValue(':firstname',	$u->firstname,		SQLITE3_TEXT);
		$stmt_users->bindValue(':email',		$u->email,			SQLITE3_TEXT);
		$stmt_users->bindValue(':cellphone',	$u->cellphone,		SQLITE3_TEXT);
		$stmt_users->bindValue(':firstmonth',	$u->firstmonth,		SQLITE3_TEXT);
		$stmt_users->bindValue(':homephone',	$u->homephone,		SQLITE3_TEXT);
		$stmt_users->bindValue(':birth',		$u->birth,			SQLITE3_TEXT);
		$stmt_users->bindValue(':baptism',		$u->baptism,		SQLITE3_TEXT);
		$stmt_users->bindValue(':appt',			$u->appt,			SQLITE3_TEXT);
		$stmt_users->execute();
	}

	// reports
	if(!$db->query('DROP TABLE IF EXISTS reports')) throw new Exception('Cannot drop table "reports"');
	if(!$db->query('CREATE TABLE "reports" (
		"t_id" INTEGER PRIMARY KEY AUTOINCREMENT,
		"t_reported_by" INTEGER,
		"t_users_id" INTEGER,
		"year" INTEGER,
		"submitted_month" TEXT,
		"studies" INTEGER,
		"returnvisits" INTEGER,
		"reported_at" TEXT,
		"month" INTEGER,
		"minutes" INTEGER,
		"credithours" INTEGER,
		"magazines" INTEGER,
		"pioneer" TEXT,
		"placements" INTEGER,
		"videoshowings" INTEGER,
		"brochures" INTEGER,
		"tracts" INTEGER
	)')) throw new Exception('Cannot create table "reports"');

	// import report data
	$stmt_users = $db->prepare('
		INSERT INTO reports (t_id,t_reported_by,t_users_id,year,submitted_month,studies,returnvisits,reported_at,month,minutes,credithours,magazines,pioneer,placements,videoshowings,brochures,tracts)
		VALUES (:t_id,:t_reported_by,:t_users_id,:year,:submitted_month,:studies,:returnvisits,:reported_at,:month,:minutes,:credithours,:magazines,:pioneer,:placements,:videoshowings,:brochures,:tracts)');
	foreach($everything->hourglassExport->reports as $k => $r)
	{
		$bar = floor($k/count($everything->hourglassExport->reports)*50);
		echo "\r[" . str_repeat("=",$bar) . ($bar<50?">" . str_repeat(" ",50-$bar):"=") . "] " . number_format(100*$k/count($everything->hourglassExport->reports),0) . "%	{$k}/" . count($everything->hourglassExport->reports);
//		echo 'Processing ' . $r->month . ' for user : ' . $r->t_users_id . "\n";
		// skip old records
		if(isset($r->submitted_month) && $r->submitted_month < '2016-01') continue;

		if(!isset($r->studies)) $r->studies = null;
		if(!isset($r->returnvisits)) $r->returnvisits = null;
		if(!isset($r->credithours)) $r->credithours = null;
		if(!isset($r->magazines)) $r->magazines = null;
		if(!isset($r->pioneer)) $r->pioneer = null;
		if(!isset($r->placements)) $r->placements = null;
		if(!isset($r->videoshowings)) $r->videoshowings = null;
		if(!isset($r->brochures)) $r->brochures = null;
		if(!isset($r->tracts)) $r->tracts = null;

		$stmt_users->bindValue(':t_id',			$r->t_id,			SQLITE3_INTEGER);
		$stmt_users->bindValue(':t_reported_by',$r->t_reported_by,	SQLITE3_INTEGER);
		$stmt_users->bindValue(':t_users_id',	$r->t_users_id,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':year',			$r->year,			SQLITE3_INTEGER);
		$stmt_users->bindValue(':submitted_month',$r->submitted_month,	SQLITE3_TEXT);
		$stmt_users->bindValue(':studies',		$r->studies,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':returnvisits',	$r->returnvisits,	SQLITE3_INTEGER);
		$stmt_users->bindValue(':reported_at',	$r->reported_at,	SQLITE3_TEXT);
		$stmt_users->bindValue(':month',		$r->month,			SQLITE3_INTEGER);
		$stmt_users->bindValue(':minutes',		$r->minutes,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':credithours',	$r->credithours,	SQLITE3_INTEGER);
		$stmt_users->bindValue(':magazines',	$r->magazines,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':pioneer',		$r->pioneer,		SQLITE3_TEXT);
		$stmt_users->bindValue(':placements',	$r->placements,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':videoshowings',$r->videoshowings,	SQLITE3_INTEGER);
		$stmt_users->bindValue(':brochures',	$r->brochures,		SQLITE3_INTEGER);
		$stmt_users->bindValue(':tracts',		$r->tracts,			SQLITE3_INTEGER);
		$ret = $stmt_users->execute()->finalize();
		if(!$ret) file_put_contents('trace.log',print_r($r,true),FILE_APPEND);
	}


	$db->close();
	return array("filename" => $file, "size" => strlen($raw));
}

function getstats($dbname)
{
	$db = new SQLite3($dbname,SQLITE3_OPEN_READONLY);
	if(!$db) throw new Exception('Could not open db file ' . $dbname);

	// we want data for monthly report to BoE
	$s_q = "
	SELECT
		max(submitted_month) as last_submit
	FROM
		reports
	";
	$s_s = $db->query($s_q);
	if(!$s_s) throw new Exception('Error in select query');
	while($s_a = $s_s->fetchArray(SQLITE3_ASSOC))
	{
		$ret[] = $s_a;
	}
	return $ret;
}

function gettotals($dbname)
{
	$db = new SQLite3($dbname,SQLITE3_OPEN_READONLY);
	if(!$db) throw new Exception('Could not open db file ' . $dbname);

	// we want data for monthly report to BoE
	$s_q = "
	SELECT
		lastname, firstname, serviceyear, runningsum
	FROM
		`runninngtotal_pioneers`
	";
	$s_s = $db->query($s_q);
	if(!$s_s) throw new Exception('Error in select query');
	while($s_a = $s_s->fetchArray(SQLITE3_ASSOC))
	{
		$ret[] = $s_a;
	}
	return $ret;
}

function createreport($dbname)
{
	$db = new SQLite3($dbname,SQLITE3_OPEN_READONLY);
	if(!$db) throw new Exception('Could not open db file ' . $dbname);

	// we want data for monthly report to BoE
	$s_q = "
	SELECT
		lastname, firstname, grp, pioneer, submitted_month, placements, videoshowings, hours, credithours, returnvisits, studies,
		sixmonthnocredit, sixmonthwithcredit
	FROM
		`6months_report_latest`
	";
	$s_s = $db->query($s_q);
	if(!$s_s) throw new Exception('Error in select query');
	while($s_a = $s_s->fetchArray(SQLITE3_ASSOC))
	{
		$ret[] = $s_a;
	}
	return $ret;
}

function createcontact($dbname,$additions_source)
{
	function arrange_group($g,$additions)
	{
		// in this array are multiple records found at the same address
		// we also know they share the same lastname
		if(!count($g)) return;
		// Lastname, Firstnames[], email[], cellphones[], Address + Homephone
		foreach($g as $r)
		{
			$res['lastname'] = $r['lastname'];
			$res['firstnames'][] = $r['firstname'];
			$res['emails'][] = $r['email'];
			$res['cellphones'][] = $r['cellphone'];
			$res['address'] = $r['line1'] . ($r['line2']?", {$r['line2']}":"") . "\\n" . $r['city'] . " " . $r['state'] . " " . $r['postalcode'] . ($r['homephone']?"\\n\\nTel: {$r['homephone']}":"");
		}
		// add the exceptions if applicable
		if(in_array($res['lastname'],array_keys($additions))) $res['firstnames'] = array_merge($res['firstnames'],$additions[$res['lastname']]);

		return $res;
	}

	$db = new SQLite3($dbname,SQLITE3_OPEN_READONLY);
	if(!$db) throw new Exception('Could not open db file ' . $dbname);

	// we want data ready for create a phone list
	$s_q = "
	SELECT
		t_address_id, sex, lastname, firstname, email, cellphone, homephone, birth, appt, city, country, line1, line2, postalcode, state
	FROM
		users LEFT JOIN
		addresses ON users.t_address_id = addresses.t_id
	WHERE
		1
	ORDER BY
		lastname || ifnull(t_address_id,99999999), strftime('%Y',ifnull(birth,date('now'))) / 20, sex DESC, ifnull(birth,date('now')) ASC
	";
	$s_s = $db->query($s_q);
	if(!$s_s) throw new Exception('Error in select query');
	$cur_add = '';

	// we have additions to the list, in particular missing kids since they are not yet publisher hence not tracked in Hourglass
	$tmp = @file_get_contents($additions_source);
	if($tmp === FALSE) throw new Exception('Failed to read additions from ' . $additions_source);
	if(!$tmp) $additions = []; else $additions = json_decode($tmp,true);
	if(!$additions) throw new Exception('Failed to decode the additions, probably a formatting error');

	$res = [];
	$group = [];
	while($s_a = $s_s->fetchArray(SQLITE3_ASSOC))
	{
		if($s_a['t_address_id'] != $cur_add)
		{
			if(count($group)) $res[] = arrange_group($group,$additions);
			$group = [];
		}
		$group[] = $s_a;
		$cur_add = $s_a['t_address_id'];
	}
	$res[] = arrange_group($group,$additions);

	return array("status" => "success", "result" => $res);
}

