<?php

namespace Magrathea2;

use Magrathea2\DB\Database;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    TimezoneCheck created: 2026-10 by Paulo Martins
####
#######################################################################################

/**
* Timezone settings: whether the MySQL session timezone follows PHP's (`db_sync_timezone`),
* and a troubleshooter for the admin Server page and the Bootstrap wizard.
* The troubleshooter (`Run()`) is never called at runtime.
*/
class TimezoneCheck {

	const SYNC_ON = "on";
	const SYNC_OFF = "off";
	/** off because the config has no `timezone` */
	const SYNC_NO_TIMEZONE = "no-timezone";

	const OK = "ok";
	const INFO = "info";
	const WARNING = "warning";
	const ERROR = "error";

	/**
	* Is the MySQL session timezone synced with PHP's?
	* On by default when the active environment has a `timezone`;
	* `db_sync_timezone = false` (or `0`, `off`, `no`, empty; quoted or not) turns it off.
	* A value that isn't a boolean keeps the default (on).
	* @return 	string 		SYNC_ON, SYNC_OFF or SYNC_NO_TIMEZONE
	*/
	public static function SyncState(): string {
		$config = Config::Instance();
		if(empty($config->Get("timezone"))) return self::SYNC_NO_TIMEZONE;
		$sync = $config->Get("db_sync_timezone");
		if($sync === null) return self::SYNC_ON;
		// parse_ini_file turns an unquoted false into "", but a quoted "false" stays a string
		$off = filter_var($sync, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
		return $off ? self::SYNC_OFF : self::SYNC_ON;
	}

	/**
	* Runs the timezone troubleshooter. Never throws: a database that can't be reached is a finding.
	* @return 	array 		list of findings: ["level" => OK|INFO|WARNING|ERROR, "message" => string]
	*/
	public static function Run(): array {
		// in this order: ClockFindings() opens the connection, which is when the sync runs
		return array_merge(
			self::ConfigFindings(),
			self::ClockFindings(),
			self::SyncFindings(self::SyncedTimezone()),
		);
	}

	/**
	* Findings about the config's `timezone` key
	* @return 	array 		findings
	*/
	private static function ConfigFindings(): array {
		$timezone = Config::Instance()->Get("timezone");
		if(empty($timezone)) {
			if(self::HasOldKey()) {
				return [self::Finding(self::WARNING, "The config has `time_zone`, which is ignored: rename it to `timezone`, in the environment section. That sets PHP's timezone and turns on the MySQL timezone sync (`db_sync_timezone`).")];
			}
			return [self::Finding(self::INFO, "No `timezone` in the config: PHP uses php.ini's default timezone, and the MySQL timezone sync is off.")];
		}
		if(!in_array($timezone, timezone_identifiers_list(\DateTimeZone::ALL_WITH_BC), true)) {
			return [self::Finding(self::ERROR, "`timezone = \"".$timezone."\"` is not a valid timezone: PHP ignores it and stays on its default (".date_default_timezone_get().").")];
		}
		return [];
	}

	/**
	* Findings comparing PHP's clock with MySQL's
	* @return 	array 		findings
	*/
	private static function ClockFindings(): array {
		$findings = [self::Finding(self::INFO, "PHP: ".date_default_timezone_get()." (".date("P")."), now() = ".now())];
		try {
			$mysql = self::MysqlClock();
		} catch(\Throwable $ex) {
			$findings[] = self::Finding(self::WARNING, "Could not query MySQL: ".$ex->getMessage());
			return $findings;
		}
		if(empty($mysql)) {
			$findings[] = self::Finding(self::WARNING, "MySQL returned no timezone data.");
			return $findings;
		}
		$findings[] = self::Finding(self::INFO, "MySQL: @@global.time_zone = ".$mysql["global_tz"].", @@session.time_zone = ".$mysql["session_tz"].", NOW() = ".$mysql["mysql_now"]);
		$findings[] = self::CompareOffsets((int)date("Z"), (int)$mysql["mysql_offset"]);
		return $findings;
	}

	/**
	* Reads MySQL's timezones and clock
	* @return 	array 		global_tz, session_tz, mysql_now, mysql_offset (seconds from UTC); empty if nothing came back
	* @throws 	\Throwable 	if the database can't be queried
	*/
	private static function MysqlClock(): array {
		return (array)Database::Instance()->QueryRow("SELECT @@global.time_zone AS global_tz, @@session.time_zone AS session_tz, NOW() AS mysql_now, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS mysql_offset");
	}

	/**
	* Compares PHP's and MySQL's offsets from UTC
	* @param 	int 	$phpSeconds 	PHP's offset, in seconds
	* @param 	int 	$mysqlSeconds 	MySQL's offset, in seconds
	* @return 	array 	finding
	*/
	private static function CompareOffsets(int $phpSeconds, int $mysqlSeconds): array {
		// compared in minutes: NOW() and UTC_TIMESTAMP() may straddle a second
		$php = (int)round($phpSeconds / 60);
		$mysql = (int)round($mysqlSeconds / 60);
		if($php === $mysql) {
			return self::Finding(self::OK, "PHP and MySQL are in sync (UTC".self::FormatOffset($php).").");
		}
		return self::Finding(self::ERROR, "PHP and MySQL are out of sync: PHP is UTC".self::FormatOffset($php).", MySQL is UTC".self::FormatOffset($mysql).". Timestamps written by PHP (`now()`) and by MySQL (`NOW()`, column defaults) disagree.");
	}

	/**
	* What the database connection synced its timezone to
	* @return 	array|null 	Database::GetSyncedTimezone(), null if unknown (no connection, or a mock)
	*/
	private static function SyncedTimezone(): ?array {
		$db = Database::Instance();
		return method_exists($db, "GetSyncedTimezone") ? $db->GetSyncedTimezone() : null;
	}

	/**
	* Findings about `db_sync_timezone`
	* @param 	array|null 	$synced 	Database::GetSyncedTimezone(), null if unknown
	* @return 	array 		findings
	*/
	private static function SyncFindings(?array $synced): array {
		switch(self::SyncState()) {
			case self::SYNC_NO_TIMEZONE:
				return [self::Finding(self::INFO, "MySQL timezone sync: off (no `timezone` in the config).")];
			case self::SYNC_OFF:
				return [self::Finding(self::INFO, "MySQL timezone sync: off (`db_sync_timezone = false`).")];
		}
		if(!$synced) {
			return [self::Finding(self::INFO, "MySQL timezone sync: on.")];
		}
		if($synced["fallback"]) {
			return [self::Finding(self::WARNING, "MySQL timezone sync: on, with the offset ".$synced["zone"]." instead of ".date_default_timezone_get().": MySQL's timezone tables aren't loaded (`mysql_tzinfo_to_sql`). An offset doesn't follow DST.")];
		}
		return [self::Finding(self::OK, "MySQL timezone sync: on (".$synced["zone"].").")];
	}

	/**
	* Does the config have the old `time_zone` key, in the active environment or in [general]?
	* @return 	bool
	*/
	private static function HasOldKey(): bool {
		$config = Config::Instance();
		if(!empty($config->Get("time_zone"))) return true;
		$all = $config->GetConfig();
		return !empty($all["general"]["time_zone"]);
	}

	/**
	* Formats an offset in minutes as +HH:MM
	* @param 	int 	$minutes 	offset
	* @return 	string
	*/
	private static function FormatOffset(int $minutes): string {
		return ($minutes < 0 ? "-" : "+").sprintf("%02d:%02d", intdiv(abs($minutes), 60), abs($minutes) % 60);
	}

	/**
	* @param 	string 	$level 		OK, INFO, WARNING or ERROR
	* @param 	string 	$message 	message
	* @return 	array
	*/
	private static function Finding(string $level, string $message): array {
		return ["level" => $level, "message" => $message];
	}

}
