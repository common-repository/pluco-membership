<?php

namespace PLCODashboard\front\classes;

class PLCO_Crontab {

	/*
	(c) Gustav Genberg 2017
	This script uses PHP shell_exec! Make sure it is enabled before using!
	Also make sure that the user running this script (usually www-data) have access to the crontab command!
	Note that this script should be working fine as it is (Apache2)
	Crontab usage:  crontab [-u user] file
					crontab [ -u user ] [ -i ] { -e | -l | -r }
							(default operation is replace, per 1003.2)
					-e      (edit user's crontab)
					-l      (list user's crontab)
					-r      (delete user's crontab)
					-i      (prompt before deleting user's crontab)
	PHP-Crontab usage:  Crontab::[ Read ] [ Set ] [ Add ] [ Remove ] [ Check ]
						Read      (Returns array of ALL [usually www-data] 's cronjobs)
						Set       (Takes array with cronjobs and REPLACES the existing cronjobs)
						Add       (Takes string formatted as a cronjob and adds it)
						Remove    (Takes string and will remove all cronjobs that matches the provided string [case-sensitive])
						Check     (Returns boolean based on cronjob existance)
	Examples:
	  Crontab::Add('0 0 * * 6 sh /backup/run.sh');
	  Crontab::Remove('0 0 * * 6 sh /backup/run.sh');
	  // OR
	  Crontab::Remove('/backup/run.sh');
	  Crontab::Check('/backup/run.sh'); // false
	*/

	public static function Read () {

		$Jobs = shell_exec('crontab -l');

		$Jobs = explode("\n", $Jobs);

		array_pop($Jobs);

		return $Jobs;

	}

	public static function Set ($Jobs) {

		$file = fopen('/tmp/php-crontab.tmp', 'w');

		for($i = 0; $i < count($Jobs); $i++) {

			fwrite($file, $Jobs[$i] . "\n");

		}

		fclose($file);

		shell_exec('crontab /tmp/php-crontab.tmp');

		unlink('/tmp/php-crontab.tmp');

	}

	public static function Add ($Job) {

		$Jobs = PLCO_Crontab::Read();

		if(PLCO_Crontab::Check($Job)) return;

		array_push($Jobs, $Job);

		PLCO_Crontab::Set($Jobs);

	}

	public static function Remove ($Job) {

		$Jobs = PLCO_Crontab::Read();

		$UpdatedJobs = [];

		for($i = 0; $i < count($Jobs); $i++) {

			if(strpos($Jobs[$i], $Job) !== false) continue;

			array_push($UpdatedJobs, $Jobs[$i]);

		}

		PLCO_Crontab::Set($UpdatedJobs);

	}

	public static function Check ($Job) {

		$Jobs = PLCO_Crontab::Read();

		for($i = 0; $i < count($Jobs); $i++) {

			if(strpos($Jobs[$i], $Job) !== false) return true;

		}

		return false;

	}

}