<?php

	/**
	 * -- FILEDESCRIPTION:
	 *
	 * This file is an import script for the DZSonline application, which is responsible for
	 * importing data from a CSV file into the application's database. The class 'dzs_import'
	 * is based on the old Aino-implementation of the import functionality, which could be found
	 * in the file system/packages/pkg_dzsonline#v1.0/cms/dzs_import/class.inc.php.
	 *
	 * The dzs_import isn't a class extending the basic_module_functions anymore, but is a
	 * standalone class that is instantiated in this file and used to perform the import. This
	 * classes uses the mysqliConnection class from mysqli.class.php to interact with the
	 * database, which contains various methods like ExecuteQuery, SafeExecuteQuery,
	 * SafeExecuteQueryAndReturn and GetInsertId that mimic the same behavior as the old Aino-
	 * implementation of the $this-Framwork class.
	 */

	// Enable strict types (must be the very first statement in the script) and error reporting
	//
	declare(strict_types=1);
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	// When ending up here, we've got a connection with the database, so we can start importing
	// the data.
	//

	class dzs_import
	{
		private $_mysqliConnection;

		private $Zalen;
		private $Klassen;
		private $Teams;
		private $ZaaldienstTeams;
		private $Leidingen;

		/**
		 * Array containing the CREATE TABLE-statements (based on a structure dump from the
		 * old database in Aino), which will be used to check if the tables exists and, if
		 * not, create them.
		 *
		 * @var array
		 */
		private $_tableCreateStatements = [

			// --
			// -- Tabelstructuur voor tabel `dzs_Import`
			// --
			'dzs_Import' => "CREATE TABLE IF NOT EXISTS `dzs_Import` (
				`Id` int(11) NOT NULL AUTO_INCREMENT,
				`File` longblob NOT NULL,
				`FileName` varchar(255) NOT NULL,
				`Omschrijving` varchar(100) NOT NULL DEFAULT '',
				`Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00', 
				`GeimporteerdOp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`GeuploadOp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`Online` tinyint(1) NOT NULL DEFAULT '0',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`Id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Klassen`
			// --
			'dzs_Klassen' => "CREATE TABLE IF NOT EXISTS `dzs_Klassen` (
				`KlasseId` int(11) NOT NULL AUTO_INCREMENT,
				`Klasse` varchar(50) NOT NULL DEFAULT '',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`KlasseId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Leiding`
			// --
			'dzs_Leiding' => "CREATE TABLE IF NOT EXISTS `dzs_Leiding` (
				`LeidingId` int(11) NOT NULL AUTO_INCREMENT,
				`Leiding` varchar(100) NOT NULL DEFAULT '',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`LeidingId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Sponsors`
			// --
			'dzs_Sponsors' => "CREATE TABLE IF NOT EXISTS `dzs_Sponsors` (
				`Id` int(11) NOT NULL AUTO_INCREMENT,
				`Sponsor` varchar(100) NOT NULL DEFAULT '',
				`Website` varchar(100) NOT NULL DEFAULT '',
				`Online` tinyint(1) NOT NULL DEFAULT '0',
				`Publish` tinyint(1) NOT NULL,
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`Id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Standen`
			// --
			'dzs_Standen' => "CREATE TABLE IF NOT EXISTS `dzs_Standen` (
				`TeamId` int(11) NOT NULL DEFAULT '0',
				`WedstrijdId` int(11) NOT NULL DEFAULT '0',
				`Punten` int(1) NOT NULL DEFAULT '0',
				`Voor` int(2) NOT NULL DEFAULT '0',
				`Tegen` int(2) NOT NULL DEFAULT '0',
				`Gewonnen` tinyint(1) NOT NULL DEFAULT '0',
				`Gelijk` tinyint(1) NOT NULL DEFAULT '0',
				`Verloren` tinyint(1) NOT NULL DEFAULT '0',
				`Online` tinyint(1) NOT NULL DEFAULT '0',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`TeamId`,`WedstrijdId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_standen`
			// --
			'dzs_standen' => "CREATE TABLE IF NOT EXISTS `dzs_standen` (
				`Id` int(11) NOT NULL AUTO_INCREMENT,
				`Team` varchar(50) NOT NULL DEFAULT '',
				`Klasse` varchar(50) NOT NULL DEFAULT '',
				`Punten` int(6) NOT NULL DEFAULT '0',
				`Voor` int(6) NOT NULL DEFAULT '0',
				`Tegen` int(6) NOT NULL DEFAULT '0',
				`Online` tinyint(1) NOT NULL DEFAULT '0',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`Id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Teams`
			// --
			'dzs_Teams' => "CREATE TABLE IF NOT EXISTS `dzs_Teams` (
				`TeamId` int(11) NOT NULL AUTO_INCREMENT,
				`Team` varchar(100) NOT NULL DEFAULT '',
				`Tekst` text NULL DEFAULT NULL,
				`Hash` varchar(255) NOT NULL DEFAULT '',
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`KlasseId` int(11) NOT NULL DEFAULT '0',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`TeamId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_uitslagen`
			// --
			'dzs_uitslagen' => "CREATE TABLE IF NOT EXISTS `dzs_uitslagen` (
				`idUitslag` int(11) NOT NULL AUTO_INCREMENT,
				`Datum` varchar(10) NOT NULL DEFAULT '',
				`Klasse` varchar(50) NOT NULL DEFAULT '',
				`TeamA` varchar(50) NOT NULL DEFAULT '',
				`TeamB` varchar(50) NOT NULL DEFAULT '',
				`PuntenTeamA` varchar(10) NOT NULL DEFAULT '-1',
				`PuntenTeamB` varchar(10) NOT NULL DEFAULT '-1',
				`Online` tinyint(1) NOT NULL DEFAULT '0',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`idUitslag`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Wedstrijden`
			// --
			'dzs_Wedstrijden' => "CREATE TABLE IF NOT EXISTS `dzs_Wedstrijden` (
				`WedstrijdId` int(11) NOT NULL AUTO_INCREMENT,
				`ZaalId` int(11) NOT NULL DEFAULT '0',
				`Datum` date NOT NULL DEFAULT '0000-00-00',
				`Tijd` time NOT NULL DEFAULT '00:00:00',
				`KlasseId` int(11) NOT NULL DEFAULT '0',
				`TeamIdThuis` int(11) NOT NULL DEFAULT '0',
				`TeamIdUit` int(11) NOT NULL DEFAULT '0',
				`TeamsZaalDienst` varchar(255) NOT NULL,
				`TeamIdZaaldienst_01` int(11) NOT NULL,
				`TeamIdZaaldienst_02` int(11) NOT NULL,
				`TeamIdZaaldienst_03` int(11) NOT NULL,
				`TeamIdZaaldienst_04` int(11) NOT NULL,
				`TeamIdZaaldienst_05` int(11) NOT NULL,
				`TeamIdZaaldienst` int(11) NOT NULL DEFAULT '0',
				`DoelpuntenTeamThuis` int(5) NOT NULL DEFAULT '-1',
				`DoelpuntenTeamUit` int(5) NOT NULL DEFAULT '-1',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`WedstrijdId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_WedstrijdLeidingen`
			// --
			'dzs_WedstrijdLeidingen' => "CREATE TABLE IF NOT EXISTS `dzs_WedstrijdLeidingen` (
				`WedstrijdId` int(11) NOT NULL DEFAULT '0',
				`LeidingId` int(11) NOT NULL DEFAULT '0',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`WedstrijdId`,`LeidingId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

			// -- --------------------------------------------------------

			// --
			// -- Tabelstructuur voor tabel `dzs_Zalen`
			// --
			'dzs_Zalen' => "CREATE TABLE IF NOT EXISTS `dzs_Zalen` (
				`ZaalId` int(11) NOT NULL AUTO_INCREMENT,
				`Zaal` varchar(100) NOT NULL DEFAULT '',
				`Online` tinyint(1) NOT NULL DEFAULT '1',
				`Publish` tinyint(1) NOT NULL,
				`Archive` tinyint(1) NOT NULL DEFAULT '0',
				`Locked` tinyint(1) NOT NULL DEFAULT '0',
				`Removed` tinyint(1) NOT NULL,
				`Scheduled` tinyint(1) NOT NULL,
				`ScheduleActive` tinyint(1) NOT NULL,
				`Rating` float(10,2) NOT NULL,
				`ReadOnly` tinyint(1) NOT NULL,
				`Private` tinyint(1) NOT NULL,
				`Marker` int(5) NOT NULL,
				`Hidden` tinyint(1) NOT NULL,
				`System` tinyint(1) NOT NULL,
				`Hash` varchar(50) NOT NULL,
				`Languages` tinyint(1) NOT NULL,
				`RecordOwner` int(11) NOT NULL,
				`RecordGroup` int(11) NOT NULL,
				`MicroTime` bigint(20) NOT NULL DEFAULT '0',
				`ValidFrom` datetime NOT NULL,
				`ValidTill` datetime NOT NULL,
				`Parent` int(11) NOT NULL,
				`Ranking` float(10,2) NOT NULL,
				`ID_Language` int(11) NOT NULL,
				`ID_Portal` int(11) NOT NULL,
				`ApplicationTag` varchar(20) NOT NULL,
				PRIMARY KEY (`ZaalId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
		];

		public function __construct(mysqliConnection $mysqliConnection)
		{
			$this->_mysqliConnection = $mysqliConnection;

			// When constructing the dzs_import-class, call method _checkTables in order
			// to check all tables from the $this->_tableCreateStatements array and (if
			// not exists), create the table into the database.
			// 
			$this->_checkTables();
			
			$this->Zalen = array();
			$this->Klassen = array();
			$this->Teams = array();
			$this->ZaaldienstTeams = array();
			$this->Leidingen = array();
		}

		/**
		 * Extracted from the old Aino-method Framework::checkEmptyValue() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * @param mixed $value
		 * @return
		 */
		public function _getEmptyValue($value, string $regexp="[^A-Za-z0-9]")
		{	
			$value = trim($value);
			$value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES))); // http://www.php.net/manual/en/function.trim.php#98812
			$value = strip_tags($value, "<img><frame><iframe><meta><object><video>");
			$value = stripslashes($value);
			$value = preg_replace($regexp,"",$value);
			return $value;
		}

		/**
		 * Extracted from the old Aino-method Framework::isEmptyValue() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * @param mixed $value
		 * @return
		 */
		public function _isEmptyValue($value, string $regexp="[^A-Za-z0-9]")
		{
			$value = $this->_getEmptyValue($value,$regexp);
			return empty($value);
		}

		/**
		 * Loops through the keys in array $this->_tableCreateStatements and checks if the
		 * table exists or not. If not, method _createTable will be called in order to
		 * create the table, based on the dumped table-structure from the old Aino-database.
		 * This way, when implementing this script somewhere and starting the import, when
		 * no tables are found in the database, they will be automatically created so it's
		 * always possible to import, even if the database if empty.
		 *
		 * @return void
		 */
		private function _checkTables()
		{
			foreach(array_keys($this->_tableCreateStatements) as $tableName)
			{
				if( !$this->_mysqliConnection->tableExists($tableName) )
				{
					if(!$this->_createTable($tableName))
					{
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Mutatets the given CREATE TABLE-statement in order to be compatible with newer
		 * versions of MySQL. For example, fields/columns of type INT or FLOAT without a
		 * default value where valid in the old database (MySQL 5.5?), but will fail on
		 * newer versions when inserting rows without a value.
		 *
		 * So instead of line:
		 *   " `Publish` tinyint(1) NOT NULL "
		 * or line:
		 *   " `ValidFrom` datetime NOT NULL, "
		 *
		 * We need lines:
		 *   " `Publish` tinyint(1) NOT NULL DEFAULT 0 "
		 * or line:
		 *   " `ValidFrom` datetime NOT NULL DEFAULT '1970-01-01 00:00:00, "
		 *
		 * in our CREATE TABLE-statement to be compatible with the newer versions of MySQL.
		 *
		 * @param string $createStatement  The original CREATE TABLE-statement from the
		 *                                 dump of the database.
		 * @return string                  The modified version of the CREATE TABLE-
		 *                                 statement.
		 */
		private function _fixCreateTableStatement(string $createStatement) : string
		{
			$createStatement = str_replace("DEFAULT '0000-00-00 00:00:00'", "DEFAULT '1970-01-01 00:00:00'", $createStatement);
			$createStatement = str_replace("DEFAULT '0000-00-00'", "DEFAULT '1970-01-01'", $createStatement);
			$createStatement = str_replace("DEFAULT '00:00:00'", "DEFAULT '00:00:01'", $createStatement);
			
			$createStatement = preg_replace("/ varchar\(([0-9]+)\) NOT NULL,/i", "VARCHAR($1) NOT NULL DEFAULT '',", $createStatement);
			$createStatement = preg_replace("/ text NOT NULL,/i", " TEXT NOT NULL DEFAULT '',", $createStatement);
			$createStatement = preg_replace("/ datetime NOT NULL,/i", "DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',", $createStatement);
			$createStatement = preg_replace("/ int\(([0-9]+)\) NOT NULL,/i", "INT($1) NOT NULL DEFAULT 0,", $createStatement);
			$createStatement = preg_replace("/ tinyint\(([0-9]+)\) NOT NULL,/i", "TINYINT($1) NOT NULL DEFAULT 0,", $createStatement);
			$createStatement = preg_replace("/ float\(([0-9]+),([0-9]+)\) NOT NULL,/i", "FLOAT($1,$2) NOT NULL DEFAULT 0.00,", $createStatement);

			//
			return $createStatement;
		}

		/**
		 * Creates the table based on the specified name and the CREATE TABLE-statement from
		 * the $this->_tableCreateStatements array. Because the old database, on MySQL 5.5?
		 * has some invalid configurations for newer databases, method
		 * _fixCreateTableStatement will be called in order to mutate the statement and
		 * make is compatible with newer versions of MySQL.
		 *
		 * @param string $tableName
		 * @return boolean
		 */
		private function _createTable(string $tableName) : bool
		{
			$createStatement = $this->_tableCreateStatements[$tableName] ?? '';
			if ($createStatement == '')
			{
				return false;
			}

			$createStatement = $this->_fixCreateTableStatement($createStatement);
			if($this->_mysqliConnection->executeQuery($createStatement, __METHOD__."@".__LINE__) === false)
			{
				throw new Exception("Failed to create table $tableName: " . $this->_mysqliConnection->error);
				return false;
			}
			
			return true;
		}
		
		function getZaalId($Zaal)
		{
			if($this->_isEmptyValue($Zaal)) return 0;
			$key = sha1(strtolower($Zaal));
			
			if(!array_key_exists($key, $this->Zalen))
			{
				$sqlQuery = "SELECT ZaalId FROM dzs_Zalen WHERE Zaal = '%0' LIMIT 1";
				$sqlArgs = array($Zaal);
				$zaalId = $this->_mysqliConnection->SafeExecuteQueryAndReturn("ZaalId", $sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
				if($zaalId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Zalen (Zaal) VALUES ('%0')";
					$sqlArgs = array($Zaal);
					$this->_mysqliConnection->SafeExecuteQuery($sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
					$zaalId = $this->_mysqliConnection->GetInsertId();
				}
				$this->Zalen[$key] = $zaalId;
			}
			return $this->Zalen[$key];
		}
		
		
		function getKlasseId($Klasse)
		{
			if($this->_isEmptyValue($Klasse)) return 0;
			$key = sha1(strtolower($Klasse));
			
			if(!array_key_exists($key, $this->Klassen))
			{
				$sqlQuery = "SELECT KlasseId FROM dzs_Klassen WHERE Klasse = '%0' LIMIT 1";
				$sqlArgs = array($Klasse);
				$klasseId = $this->_mysqliConnection->SafeExecuteQueryAndReturn("KlasseId", $sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
				if($klasseId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Klassen (Klasse) VALUES ('%0')";
					$sqlArgs = array($Klasse);
					$this->_mysqliConnection->SafeExecuteQuery($sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
					$klasseId = $this->_mysqliConnection->GetInsertId();
				}
				$this->Klassen[$key] = $klasseId;
			}
			return $this->Klassen[$key];
		}
		
		function correctTeamName($teamName)
		{	
			$stripCharsBefore = array(
				" ",
				"`"
			);
			
			$stripCharsAfter = array(
				" ",
				"`"
			);
								
			while(in_array(substr($teamName, 0, 1), $stripCharsBefore))	$teamName = substr($teamName, 1);	
			while(in_array(substr($teamName, -1, 1), $stripCharsAfter))	$teamName = substr($teamName, 0, -1);
			
			return trim($teamName);
		}
		
		
		function getTeamId($Team, $KlasseId=0)
		{
			if($this->_isEmptyValue($Team)) return 0;
			$Team = $this->correctTeamName($Team);
			$key = sha1($KlasseId.".".strtolower($Team));
			
			if(!array_key_exists($key, $this->Teams))
			{
				$sqlQuery = "SELECT TeamId FROM dzs_Teams WHERE Team = '%0'";
				$sqlArgs = array($Team);
				
				if($KlasseId > 0)
				{
					$sqlQuery .= " AND KlasseId = '%1' ";
					$sqlArgs[] = $KlasseId;
				}
				$sqlQuery .= " LIMIT 1";
				
				$teamId = $this->_mysqliConnection->SafeExecuteQueryAndReturn("TeamId", $sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
				if($teamId <= 0 && $KlasseId > 0)
				{
					$sqlQuery = "INSERT INTO dzs_Teams (Team, KlasseId) VALUES ('%0', '%1')";
					$sqlArgs = array($Team, $KlasseId);
					$this->_mysqliConnection->SafeExecuteQuery($sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
					$teamId = $this->_mysqliConnection->GetInsertId();
				}
				$this->Teams[$key] = $teamId;
			}
			
			return $this->Teams[$key];
		}
		
		
		function getTeamIdsZaaldienst($TeamZaalDienst)
		{
			$TeamIdsZaalDienst = array(0 => array());
			
			if(strpos($TeamZaalDienst, "/") !== false)
			{
				$TeamsArray = explode("/", $TeamZaalDienst);
				$ZaalDienstTeams = $TeamsArray;
				
				$this->findTeamIdsZaalDienst($TeamsArray, $ZaalDienstTeams, $TeamIdsZaalDienst);
			}
			else
			{
				$TeamIdsZaalDienst[$this->getTeamId($TeamZaalDienst, 0)] = $TeamZaalDienst;
			}
			
			if(count($TeamIdsZaalDienst[0]) == 0)
			{
				unset($TeamIdsZaalDienst[0]);
			}
			
			return $TeamIdsZaalDienst;
		}
		
		function findTeamIdsZaalDienst($TeamsArray, $ZaalDienstTeams, &$TeamIdsZaalDienst)
		{
			for($i=0; $i<count($ZaalDienstTeams); $i++)
			{
				if(trim($ZaalDienstTeams[$i]) != "")
				{
					if(($teamId = $this->getTeamId(trim($ZaalDienstTeams[$i]))) > 0)
					{
						$TeamIdsZaalDienst[$teamId] = trim($ZaalDienstTeams[$i]);
					}
					else
					{
						if(isset($ZaalDienstTeams[$i+1]) && (trim($ZaalDienstTeams[$i+1]) != ""))
						{
							$ZaalDienstTeams[$i+1] = $ZaalDienstTeams[$i]."/".$ZaalDienstTeams[$i+1];
						}
					}
				}
				
				if($i==(count($ZaalDienstTeams)-1) && (count($TeamIdsZaalDienst) == 1))
				{
					if(count($TeamsArray) > 1)
					{
						$TeamIdsZaalDienst[0][] = array_shift($TeamsArray);
						$ZaalDienstTeams = $TeamsArray;
						$this->findTeamIdsZaalDienst($TeamsArray, $ZaalDienstTeams, $TeamIdsZaalDienst);
					}
				}
			}
		}
		
		function getLeidingId($Leiding)
		{
			if($this->_isEmptyValue($Leiding)) return 0;
			$key = sha1(strtolower($Leiding));
			
			if(!array_key_exists($key, $this->Leidingen))
			{
				$sqlQuery = "SELECT LeidingId FROM dzs_Leiding WHERE Leiding = '%0' LIMIT 1";
				$sqlArgs = array($Leiding);
				$leidingId = $this->_mysqliConnection->SafeExecuteQueryAndReturn("LeidingId", $sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
				if($leidingId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Leiding (Leiding) VALUES ('%0')";
					$sqlArgs = array($Leiding);
					$this->_mysqliConnection->SafeExecuteQuery($sqlQuery, $sqlArgs, __METHOD__."@".__LINE__);
					$leidingId = $this->_mysqliConnection->GetInsertId();
				}
				$this->Leidingen[$key] = $leidingId;
			}
			return $this->Leidingen[$key];
		}

		/**
		 * Rwead the contents from the file on the given file path and return the contents
		 * as a string, the same way it was read and inserted into the database in Aino. If
		 * the file doesn't exist or is empty, an exception will be thrown.
		 *
		 * @param string $filePath
		 * @return string
		 */
		function _readFile(string $filePath) : string
		{
			if (!file_exists($filePath))
			{
				throw new Exception("File '". $filePath . "' does not exist.");
				return '';
			}

			if(filesize($filePath) == 0)
			{
				throw new Exception("File '". $filePath . "' is empty.");
				return '';
			}
				
			$fileContents = file_get_contents($filePath);
			// Inherited from the old Aino module, no idea why we need to replace a '
			// with a `, but let's keep it for now to avoid breaking things.
			//
			$fileContents = nl2br(str_replace("'", "`", stripslashes($fileContents)));
			return $fileContents;
		}

		/**
		 * Imports the data from the given file path into the database, the same way it was
		 * done in Aino in method afterHandle() when $handle was set to 'setonline'.
		 *
		 * This method will read the contents of the file directly (instead of fetching the
		 * fileConents from the database), split it into rows and columns, and insert the
		 * data into the database, while also keeping track of the Zalen, Klassen, Teams
		 * and Leidingen that have been inserted to avoid inserting duplicates. If the file
		 * doesn't exist or is empty, an exception will be thrown.
		 *
		 * @param string $filePath
		 * @return boolean  Returns true when the import was successfull, false otherwise.
		 */
		function Import(string $filePath) : bool
		{
			$fileContents = $this->_readFile($filePath);
			if($fileContents === '')
			{
				return false;
			}
			
			// Inherited from the old Aino module, no idea why we explode the file
			// contents on <br />, but let's keep it for now to avoid breaking things.
			// 
			$content = explode("<br />", $fileContents);

			$row = 1;
			$cols = 12;
			$Error = "";

			$Zalen = array();
			$Klassen = array();
			$Teams = array();
			$Leiding = array();
			
			$sqlQuery = "TRUNCATE `dzs_Leiding`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

			$sqlQuery = "TRUNCATE `dzs_Wedstrijden`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

			$sqlQuery = "TRUNCATE `dzs_WedstrijdLeidingen`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			
			$sqlQuery = "TRUNCATE `dzs_Klassen`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

			$sqlQuery = "TRUNCATE `dzs_Standen`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

			$sqlQuery = "TRUNCATE `dzs_Teams`";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

			foreach ($content as $key => $row)
			{
				if(trim($row) != "")
				{
					$data = explode(";", $row);

					if(count($data) < $cols)
					{
						throw new Exception("Excelsheet komt niet overeen met de te-importeren data");
						return false;
					}
					else
					{
						if(preg_match("/#/", $data[0], $matches, PREG_OFFSET_CAPTURE))
						{
							if($Error == "")
							{
								$Error = "De volgende rijen zijn , vanwege een # in de eerste kolom, <u>niet</u> geimporteerd:<br>".$row;
							}
							else
							{
								$Error .= ", ".$row;
							}
						}
						else
						{
							$Zaal = $data[1];
							$Datum = $data[2];
							$Dag = $data[3];
							$Tijd = $data[4];
							$Klasse = $data[5];
							
							$TeamThuis = $this->correctTeamName($data[6]);
							$TeamUit = $this->correctTeamName($data[7]);
							$TeamsZaalDienst = $this->correctTeamName($data[8]);
							
							$DoelpuntenTeamThuis = ((trim($data[9])!='')?intval($data[9]):-1);
							$DoelpuntenTeamUit = ((trim($data[10])!='')?intval($data[10]):-1);
							
							$ZaalId = $this->getZaalId($Zaal);
							$KlasseId = $this->getKlasseId($Klasse);
							
							$TeamIdThuis = $this->getTeamId($TeamThuis, $KlasseId);
							$TeamIdUit = $this->getTeamId($TeamUit, $KlasseId);
							$TeamIdZaaldienst = 0;
							
							if($this->_isEmptyValue($Zaal)
								|| $this->_isEmptyValue($Datum)
								|| $this->_isEmptyValue($Tijd)
								|| $this->_isEmptyValue($Klasse)
								|| $this->_isEmptyValue($TeamThuis)
								|| $this->_isEmptyValue($TeamUit)
							) {
								
							}
							else
							{	
								//
								list($Dag, $Maand, $Yaar) = explode('-', $Datum);
								list($Uur, $Minuut) = explode('.', $Tijd);

								$MaandAfkortingEngels = array('jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec');
								$MaandAfkortingNederlands = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec');
								$MaandTekstEngels = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
								$MaandTekstNederlands = array('januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december');

								$MaandGetal = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

								$Maand = str_replace($MaandAfkortingEngels, $MaandGetal, strtolower($Maand));
								$Maand = str_replace($MaandAfkortingNederlands, $MaandGetal, strtolower($Maand));
								$Maand = str_replace($MaandTekstEngels, $MaandGetal, strtolower($Maand));
								$Maand = str_replace($MaandTekstNederlands, $MaandGetal, strtolower($Maand));

								$Date = (intval($Yaar)."-".sprintf("%02d",intval($Maand))."-".sprintf("%02d",intval($Dag)));
								$Time = (intval($Uur).":".sprintf("%02d",intval($Minuut)).":00");

								//
								if(empty($DoelpuntenTeamThuis) && $DoelpuntenTeamThuis < 0)
								{
									$DoelpuntenTeamThuis = -1;
								}

								if(empty($DoelpuntenTeamUit) && $DoelpuntenTeamUit < 0)
								{
									$DoelpuntenTeamUit = -1;
								}
								
								//	print $Date." | ".$Time." | ".$Klasse." (".$KlasseId.") :: ".$TeamThuis." (".$TeamIdThuis.") vs. ".$TeamUit." (".$TeamIdUit.") = ".$DoelpuntenTeamThuis." - ".$DoelpuntenTeamUit." / Zaaldienst: ".$TeamZaaldienst." (".$TeamIdZaaldienst.") \n";

								$TeamThuisGewonnen = 0;
								$TeamThuisGelijk = 0;
								$TeamThuisVerloren = 0;

								$TeamUitGewonnen = 0;
								$TeamUitGelijk = 0;
								$TeamUitVerloren = 0;

								if($DoelpuntenTeamThuis > -1 && $DoelpuntenTeamUit > -1)
								{
									if($DoelpuntenTeamThuis > $DoelpuntenTeamUit)
									{
										// Team thuis gewonnen
										$PuntenTeamThuis = 3;
										$PuntenTeamUit = 0;

										$TeamThuisGewonnen = 1;
										$TeamUitVerloren = 1;
									}
									else if($DoelpuntenTeamThuis == $DoelpuntenTeamUit)
									{
										// Gelijkspel
										$PuntenTeamThuis = 1;
										$PuntenTeamUit = 1;

										$TeamThuisGelijk = 1;
										$TeamUitGelijk = 1;								
									}
									else
									{
										// Team uit gewonnen gewonnen
										$PuntenTeamThuis = 0;
										$PuntenTeamUit = 3;
										$TeamThuisVerloren = 1;
										$TeamUitGewonnen = 1;
									}
								}
								else
								{
									// Onbekend
									$PuntenTeamThuis = -1;
									$PuntenTeamUit = -1;
								}

								// Onbekend
								if($DoelpuntenTeamThuis == "" || $DoelpuntenTeamUit == "")
								{
									$DoelpuntenTeamThuis = -1;
									$DoelpuntenTeamUit = -1;
								}

								$sqlQuery = "INSERT INTO dzs_Wedstrijden (ZaalId, Datum, Tijd, KlasseId, TeamIdThuis, TeamIdUit, DoelpuntenTeamThuis, DoelpuntenTeamUit, TeamsZaalDienst) VALUES ('".$ZaalId."', '".$Date."', '".$Time."', '".$KlasseId."', '".$TeamIdThuis."', '".$TeamIdUit."', '".$DoelpuntenTeamThuis."', '".$DoelpuntenTeamUit."', '".$TeamsZaalDienst."')";
								$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
								$WedstrijdId = $this->_mysqliConnection->GetInsertId();

								// Insert punten voor Team Thuis
								$sqlQuery = "INSERT INTO dzs_Standen (TeamId, WedstrijdId, Punten, Voor, Tegen, Gewonnen, Gelijk, Verloren) VALUES ('".$TeamIdThuis."', '".$WedstrijdId."', '".$PuntenTeamThuis."', '".$DoelpuntenTeamThuis."', '".$DoelpuntenTeamUit."', '".$TeamThuisGewonnen."', '".$TeamThuisGelijk."', '".$TeamThuisVerloren."')";
								$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

								// Insert punten voor Team Uit
								$sqlQuery = "INSERT INTO dzs_Standen (TeamId, WedstrijdId, Punten, Voor, Tegen, Gewonnen, Gelijk, Verloren) VALUES ('".$TeamIdUit."', '".$WedstrijdId."',  '".$PuntenTeamUit."', '".$DoelpuntenTeamUit."', '".$DoelpuntenTeamThuis."', '".$TeamUitGewonnen."', '".$TeamUitGelijk."', '".$TeamUitVerloren."')";						
								$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);

								/*
								for($i=12; $i<=(count($data)); $i++)
								{
									if(!empty($data[$i]))
									{
									*/
										$Leiding = $data[12];
										$LeidingId = $this->getLeidingId($Leiding);
										$sqlQuery = "INSERT INTO dzs_WedstrijdLeidingen (WedstrijdId, LeidingId) VALUES ('".$WedstrijdId."', '".$LeidingId."')";
										$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
								/*
									}
								}
									*/
							}
						}
					}
				}
			}
			
			$sqlQuery = "SELECT WedstrijdId, TeamsZaaldienst FROM dzs_Wedstrijden WHERE 1";
			$sqlResult = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			while($row = $sqlResult->fetch_object())
			{
				$TeamZaaldienst = $row->TeamsZaaldienst;
				if(trim($TeamZaaldienst) != "")
				{
					$sqlSet = array();
					$teamNo = 1;
					
					$TeamIdsZaaldienst = $this->getTeamIdsZaaldienst($TeamZaaldienst);
					if(count($TeamIdsZaaldienst) > 0)
					{
						foreach($TeamIdsZaaldienst as $teamId => $teamName)
						{
							if($teamId == 0 && is_array($teamId))
							{
								foreach($teamId as $noId => $teamName)
								{
									$sqlSet[] = "TeamIdZaaldienst_0".$teamNo." = '0'";
									$teamNo++;
								}
							}
							else
							{
								$sqlSet[] = "TeamIdZaaldienst_0".$teamNo." = ".intval($teamId);
								$teamNo++;
							}
						}
					}
					
					if(count($sqlSet) > 0)
					{
						// print $TeamZaaldienst." = ".var_export($TeamIdsZaaldienst, true)."\n ".var_export($sqlSet, true)."\n\n---------------------------------------------------------------------------------------------------------\n";
						$sqlQuery = "UPDATE dzs_Wedstrijden SET ".implode(", ", $sqlSet)." WHERE WedstrijdId = ".intval($row->WedstrijdId);
						$this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
					}
				}
			}

			return true;
		}

		/**
		 * Count the amount of klassen, leidingen, standen, teams, wedstrijden and zalen in
		 * the database. Will/can be used at the end of the import to check/verify/report
		 * the imported data.
		 *
		 * @return array
		 */
		function countData() : array
		{
			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Klassen";
			$totalKlassen = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Leiding";
			$totalLeidingen = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Standen";
			$totalStanden = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Teams";
			$totalTeams = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Wedstrijden";
			$totalWedstrijden = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			$sqlQuery = "SELECT COUNT(*) AS Total FROM dzs_Zalen";
			$totalZalen = $this->_mysqliConnection->safeExecuteQueryAndReturn("Total", $sqlQuery, [], __METHOD__."@".__LINE__);

			return [
				"Klassen" => $totalKlassen,
				"Leidingen" => $totalLeidingen,
				"Standen" => $totalStanden,
				"Teams" => $totalTeams,
				"Wedstrijden" => $totalWedstrijden,
				"Zalen" => $totalZalen,
			];
		}
	}

	// Include the MySQLi class, create an instance of the dzs_import class and call method
	// import with the path to the CSV file to import the data.
	//
	include('mysqli.class.php');
	$dzsImport = new dzs_import($mysqliConnection);
	if($dzsImport->Import('koos10.csv'))
	{
		// When the import is done, generate a small report.
		//
		print "<h1>Import gelukt!</h1><hr>";
		print "Overzicht van geïmporteerde gegevens:<br><ul>";
		$count = $dzsImport->countData();
		foreach($count as $key => $value)
		{
			print "<li>".$key.": ".$value."</li>";
		}
		print "</ul>";
	}