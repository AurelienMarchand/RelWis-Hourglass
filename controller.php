<?php

error_reporting(-1);
ini_set('display_errors','stdout');

$file = 'hourglass-export.json';
$sqlite = 'hourglass.db';

try {
	ob_start();

	switch($_GET['mode'])
	{
	case 'readfile':
		// send to output buffer the content of the file
		readfile($file);
		break;
	case 'importfile':
		echo json_encode(importfile($file,$sqlite));
		break;
	case 'report':
		echo json_encode(createreport($sqlite));
	default:
		break;
	}
} catch (Exception $e)
{
	echo json_encode(array('error' => $e->getMessage()));
}

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

	// import data
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


	// import data
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
		"magazines" INTEGER,
		"pioneer" TEXT,
		"placements" INTEGER,
		"videoshowings" INTEGER,
		"brochures" INTEGER,
		"tracts" INTEGER
	)')) throw new Exception('Cannot create table "reports"');

	$db->close();
	return array("filename" => $file, "size" => strlen($raw));
}

function createreport($dbname)
{
	function arrange_group($g,$exceptions)
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
		if(in_array($res['lastname'],array_keys($exceptions))) $res['firstnames'] = array_merge($res['firstnames'],$exceptions[$res['lastname']]);

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

	// we have exceptions to the list, in particular missing kids since they are not yet publisher hence not tracked in Hourglass
	$exceptions = [
		"Baky" => ["Anaïs"],
		"Charlot" => ["Brandgy","Darren"],
		"Dort" => ["Mia","Ian"],
		"Kamwanga" => ["Roxanne"],
		"Lavoile" => ["Kervins"],
		"Marchand" => ["Rémi","Lucy"],
		"Ogunjobi" => ["Teniola"],
		"Sainvilus" => ["Joevanny"]
	];

	$res = [];
	$group = [];
	while($s_a = $s_s->fetchArray(SQLITE3_ASSOC))
	{
		if($s_a['t_address_id'] != $cur_add)
		{
			if(count($group)) $res[] = arrange_group($group,$exceptions);
			$group = [];
		}
		$group[] = $s_a;
		$cur_add = $s_a['t_address_id'];
	}
	$res[] = arrange_group($group,$exceptions);

	return array("status" => "success", "result" => $res);
}

header('Content-Type: application/json');
exit();
