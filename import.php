<?
	//--------------------------------------------------------------
	// class.inc.php
	//--------------------------------------------------------------
	//version	: 0.2
	//date		: 04-04-2007
	//author	: sb
	//
	// Copyright (C) Concera Software
	//
	//Changelog:
	//
	//

	//main class
	class dzs_import extends basic_module_functions
	{
		private $Zalen;
		private $Klassen;
		private $Teams;
		private $ZaaldienstTeams;
		private $Leidingen;	
		
		//constructor function
		function dzs_import(&$Framework, $_BROWSER, $myId)
		{
			$this->Framework = $Framework;
			$this->BROWSER = $_BROWSER;
			$this->myId = $myId;
			
			$this->Zalen = array();
			$this->Klassen = array();
			$this->Teams = array();
			$this->ZaaldienstTeams = array();
			$this->Leidingen = array();
		}
		
		function getZaalId($Zaal)
		{
			if($this->Framework->isEmptyValue($Zaal)) return 0;
			$key = sha1(strtolower($Zaal));
			
			if(!array_key_exists($key, $this->Zalen))
			{
				$sqlQuery = "SELECT ZaalId FROM dzs_Zalen WHERE Zaal = '%0' LIMIT 1";
				$sqlArgs = array($Zaal);
				$zaalId = $this->Framework->mySqlSafeExecuteQueryAndReturn("ZaalId", $sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
				if($zaalId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Zalen (Zaal) VALUES ('%0')";
					$sqlArgs = array($Zaal);
					$this->Framework->mySqlSafeExecuteQuery($sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
					$zaalId = $this->Framework->mySqlInsertId();
				}
				$this->Zalen[$key] = $zaalId;
			}
			return $this->Zalen[$key];
		}
		
		
		function getKlasseId($Klasse)
		{
			if($this->Framework->isEmptyValue($Klasse)) return 0;
			$key = sha1(strtolower($Klasse));
			
			if(!array_key_exists($key, $this->Klassen))
			{
				$sqlQuery = "SELECT KlasseId FROM dzs_Klassen WHERE Klasse = '%0' LIMIT 1";
				$sqlArgs = array($Klasse);
				$klasseId = $this->Framework->mySqlSafeExecuteQueryAndReturn("KlasseId", $sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
				if($klasseId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Klassen (Klasse) VALUES ('%0')";
					$sqlArgs = array($Klasse);
					$this->Framework->mySqlSafeExecuteQuery($sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
					$klasseId = $this->Framework->mySqlInsertId();
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
			if($this->Framework->isEmptyValue($Team)) return 0;
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
				
				$teamId = $this->Framework->mySqlSafeExecuteQueryAndReturn("TeamId", $sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
				if($teamId <= 0 && $KlasseId > 0)
				{
					$sqlQuery = "INSERT INTO dzs_Teams (Team, KlasseId) VALUES ('%0', '%1')";
					$sqlArgs = array($Team, $KlasseId);
					$this->Framework->mySqlSafeExecuteQuery($sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
					$teamId = $this->Framework->mySqlInsertId();
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
			if($this->Framework->isEmptyValue($Leiding)) return 0;
			$key = sha1(strtolower($Leiding));
			
			if(!array_key_exists($key, $this->Leidingen))
			{
				$sqlQuery = "SELECT LeidingId FROM dzs_Leiding WHERE Leiding = '%0' LIMIT 1";
				$sqlArgs = array($Leiding);
				$leidingId = $this->Framework->mySqlSafeExecuteQueryAndReturn("LeidingId", $sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
				if($leidingId <= 0)
				{
					$sqlQuery = "INSERT INTO dzs_Leiding (Leiding) VALUES ('%0')";
					$sqlArgs = array($Leiding);
					$this->Framework->mySqlSafeExecuteQuery($sqlQuery, $sqlArgs, null, __METHOD__."@".__LINE__);
					$leidingId = $this->Framework->mySqlInsertId();
				}
				$this->Leidingen[$key] = $leidingId;
			}
			return $this->Leidingen[$key];
		}

		
		function afterSave($rid)
		{
			if(count($_FILES) > 0)
			{
				//get first files field.
				list($key, $value) = each($_FILES);
				$tmp_file = $value;

				//read file.
				if (!empty($tmp_file['name']))
				{
					if (file_exists($tmp_file['tmp_name']))
					{
						if ($f = file_get_contents($tmp_file['tmp_name']))
						{
							$content = nl2br(str_replace("'", "`", stripslashes($f)));
							$sqlQuery = "UPDATE dzs_Import SET FileName='".$tmp_file['name']."', File='".$content."', GeuploadOp='".date("Y-m-d H:i:s")."' WHERE Id='".$rid."'";
							$this->Framework->mySqlExecuteQuery($sqlQuery);	
							$this->reloadObject();
						}
					}
				}
			}
		}
		
		function afterHandle($rid, $handle)
		{
			if($handle == "setonline")
			{
				$sqlQuery = "SELECT * FROM dzs_Import WHERE Id='".$rid."' LIMIT 1";
				$result = $this->Framework->mySqlExecuteQuery($sqlQuery);
				if(mysql_num_rows($result) == 1)
				{
					$record = mysql_fetch_object($result);
					$content = explode("<br />", $record->File);

					$sqlQuery = "UPDATE dzs_Import SET Online='0' WHERE 1";
					$this->Framework->mySqlExecuteQuery($sqlQuery);

					$sqlQuery = "UPDATE dzs_Import SET GeimporteerdOp='".date("Y-m-d H:i:s")."', Online='1'  	WHERE Id='".$rid."'";
					$this->Framework->mySqlExecuteQuery($sqlQuery);
				}

				$row = 1;
				$cols = 12;
				$Error = "";

				$Zalen = array();
				$Klassen = array();
				$Teams = array();
				$Leiding = array();
				
				$sqlQuery = "TRUNCATE `dzs_Leiding`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

				$sqlQuery = "TRUNCATE `dzs_Wedstrijden`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

				$sqlQuery = "TRUNCATE `dzs_WedstrijdLeidingen`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);
				
				$sqlQuery = "TRUNCATE `dzs_Klassen`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

				$sqlQuery = "TRUNCATE `dzs_Standen`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

				$sqlQuery = "TRUNCATE `dzs_Teams`";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

				while (list($key, $row) = each($content))
				{
					if(trim($row) != "")
					{
						$data = explode(";", $row);

						if(count($data) < $cols)
						{
							$this->addError("Excelsheet komt niet overeen met de te-importeren data");
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
								
								$DoelpuntenTeamThuis = $data[9];
								$DoelpuntenTeamUit = $data[10];
								
								$ZaalId = $this->getZaalId($Zaal);
								$KlasseId = $this->getKlasseId($Klasse);
								
								$TeamIdThuis = $this->getTeamId($TeamThuis, $KlasseId);
								$TeamIdUit = $this->getTeamId($TeamUit, $KlasseId);
								$TeamIdZaaldienst = 0;
								
								if($this->Framework->isEmptyValue($Zaal)
								|| $this->Framework->isEmptyValue($Datum)
								|| $this->Framework->isEmptyValue($Tijd)
								|| $this->Framework->isEmptyValue($Klasse)
								|| $this->Framework->isEmptyValue($TeamThuis)
								|| $this->Framework->isEmptyValue($TeamUit))
								{
									
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
									$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);
									$WedstrijdId = $this->Framework->mySqlInsertId();

									// Insert punten voor Team Thuis
									$sqlQuery = "INSERT INTO dzs_Standen (TeamId, WedstrijdId, Punten, Voor, Tegen, Gewonnen, Gelijk, Verloren) VALUES ('".$TeamIdThuis."', '".$WedstrijdId."', '".$PuntenTeamThuis."', '".$DoelpuntenTeamThuis."', '".$DoelpuntenTeamUit."', '".$TeamThuisGewonnen."', '".$TeamThuisGelijk."', '".$TeamThuisVerloren."')";
									$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

									// Insert punten voor Team Uit
									$sqlQuery = "INSERT INTO dzs_Standen (TeamId, WedstrijdId, Punten, Voor, Tegen, Gewonnen, Gelijk, Verloren) VALUES ('".$TeamIdUit."', '".$WedstrijdId."',  '".$PuntenTeamUit."', '".$DoelpuntenTeamUit."', '".$DoelpuntenTeamThuis."', '".$TeamUitGewonnen."', '".$TeamUitGelijk."', '".$TeamUitVerloren."')";						
									$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);

									/*
									for($i=12; $i<=(count($data)); $i++)
									{
										if(!empty($data[$i]))
										{
									 */
											$Leiding = $data[12];
											$LeidingId = $this->getLeidingId($Leiding);
											$sqlQuery = "INSERT INTO dzs_WedstrijdLeidingen (WedstrijdId, LeidingId) VALUES ('".$WedstrijdId."', '".$LeidingId."')";
											$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);
									/*
										}
									}
									 */
								}		
							}
							$row++;
						}
					}
				}
				
				$sqlQuery = "SELECT WedstrijdId, TeamsZaaldienst FROM dzs_Wedstrijden WHERE 1";
				$sqlResult = $this->Framework->mySqlExecuteQuery($sqlQuery);
				while($row = $this->Framework->mySqlFetchObject($sqlResult))
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
									$sqlSet[] = "TeamIdZaaldienst_0".$teamNo." = '".$teamId."'";
									$teamNo++;
								}
							}
						}
						
						if(count($sqlSet) > 0)
						{
							// print $TeamZaaldienst." = ".var_export($TeamIdsZaaldienst, true)."\n ".var_export($sqlSet, true)."\n\n---------------------------------------------------------------------------------------------------------\n";
							$sqlQuery = "UPDATE dzs_Wedstrijden SET ".implode(", ", $sqlSet)." WHERE WedstrijdId = '".$row->WedstrijdId."'";
							$this->Framework->mySqlExecuteQuery($sqlQuery);
						}
					}
				}
				
				$this->reloadList();
				
			}
		}
		
		function checkImport($rid)
		{
			$uploadDate = $this->Framework->mySqlSelectFromTable("GeuploadOp", "dzs_Import", "Id", $rid);
			$importDate = $this->Framework->mySqlSelectFromTable("GeimporteerdOp", "dzs_Import", "Id", $rid);
			
			if(($importDate > 0) && ($uploadDate > 0) && ($uploadDate > $importDate))
			{	
				$this->addWarning("Let op! De laatste keer dat dit bestand is geupload (".$uploadDate.") is later dan de laatste keer waarop deze is geimporteerd (".$importDate."). Mogelijk dat niet alle gegevens correct zijn verwerkt.");
			}
		}

		//configure this class
		function Configure()
		{
		
			if (!isset($this->BROWSER['rid'])) $this->BROWSER['rid'] = "";
			
			if($this->getRecordId() > 0)
			{
				$this->checkImport($this->getRecordId());
			}
		
			$this->ControlLib->SetTable("dzs_Import", "Id");
		    $this->ControlLib->AddFormField("Omschrijving", "Omschrijving",true, false, "main");
			$this->ControlLib->AddFormFieldFile(".csv Bestand", "",false, false, "main");
		    $this->ControlLib->AddFormFieldDisabled("Bestand", "FileName",false, false, "main");
		    $this->ControlLib->AddFormFieldDisabled("Geupload Op", "GeuploadOp",false, false, "main");
		    $this->ControlLib->AddFormFieldDisabled("Geimporteerd Op", "GeimporteerdOp",false, false, "main");
			
			$this->ControlLib->SetFormButtons(true, true, true, true, false, true, false, false);
			
			$this->ControlLib->AddListColumn("Id", "Id", "Id", "right");
			$this->ControlLib->AddListColumn("Omschrijving", "Omschrijving", "Omschrijving");
			$this->ControlLib->AddListColumn("Geupload Op", "GeuploadOp", "GeuploadOp");
			$this->ControlLib->AddListColumn("Geimporteerd Op", "GeimporteerdOp", "GeimporteerdOp");
			
			$this->ControlLib->config_list_record_text_dbfield = "Omschrijving";
			$this->ControlLib->config_list_order_fields = "Omschrijving";
			$this->ControlLib->config_list_tabsearch_field = "Omschrijving";
			$this->ControlLib->SetListButtions(true, true, true, false, false, true);
			
			$this->ControlLib->config_list_multicommand_online = false;
			$this->ControlLib->config_list_multicommand_offline = false;
			$this->ControlLib->config_list_multicommand_remove = true;	
			$this->ControlLib->config_list_multicommand_archive = true;	
			$this->ControlLib->config_list_multicommand_dearchive = true;	
			$this->ControlLib->config_list_multicommand_locked = false;			
			$this->ControlLib->config_list_multicommand_unlocked = false;
			
			//configure search screen.
			$this->ControlLib->AddSearchField("Omschrijving", "Omschrijving");
		}

	}// end of class

?>