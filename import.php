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

		public function __construct(mysqliConnection $mysqliConnection)
		{
			$this->_mysqliConnection = $mysqliConnection;

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
							
							$DoelpuntenTeamThuis = intval($data[9]);
							$DoelpuntenTeamUit = intval($data[10]);
							
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
	}

	// Include the MySQLi class, create an instance of the dzs_import class and call method
	// import with the path to the CSV file to import the data.
	//
	include('mysqli.class.php');
	$dzsImport = new dzs_import($mysqliConnection);
	if($dzsImport->Import('koos10.csv'))
	{
		print 'Done!';
	}