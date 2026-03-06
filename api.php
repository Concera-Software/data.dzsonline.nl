<?php

	/**
	 * -- FILEDESCRIPTION:
	 *
	 * This file is a simple version of an API, used to fetch data like 'standen', 'teams',
	 * 'uitslagen' and 'wedstrijden' from the tables...
	 *
	 *  - http://192.168.205.110/dzsonline/programma.json - Geplande wedstrijden
	 *  - http://192.168.205.110/dzsonline/standen.json - Alle klasses + teams + stand
	 *  - http://192.168.205.110/dzsonline/teams.json - Alle klasses + teams
	 *  - http://192.168.205.110/dzsonline/uitslagen.json - Gespeelde wedstrijden
	 *  - http://192.168.205.110/dzsonline/wedstrijden.json - Alle wedstrijden
	 */

	// Enable strict types (must be the very first statement in the script) and error reporting
	//
	declare(strict_types=1);
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	// Check if request got here by .htaccess redirect, by using $_SERVER['REDIRECT_URL']. Also
	// check if request-parameter '_fetch' is found in the URL and contains a value. If not any
	// of these checks are ok, return a 204 No Content response and terminate the script. This
	// means when someone is using the path /api.php directly instead of something like
	// standen.json or wedstrijden.json (forwarded from the .htacess), this won't be allowed.
	//
	if(
		!isset($_SERVER['REDIRECT_URL'])
		|| (trim($_SERVER['REDIRECT_URL']) == '')
		|| !isset($_REQUEST)
		|| !isset($_REQUEST['_fetch'])
		|| (($_REQUEST['_fetch']??'') == '')
	) {
		http_response_code(204); // No content
		die();
	}

	$requestMethod = strtoupper($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']??'');
	$origin = strtolower($_SERVER['HTTP_ORIGIN']??'');
	$originHost = parse_url($origin, PHP_URL_HOST);

	// Check if the REQUEST METHOD is set to 'OPTIONS'. If so, go respond to preflight request
	// by replying CORS (Cross Origin Resource Sharing) OPTIONS request.
	//
	if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'))
	{
		// Specify which methods are allowed. In this case, we'll only allow GET requests.
		//
		$allowedMethods = [
			'GET',
		];
		
		// Specify which hosts are allowed to access the data from this file.
		//
		$allowedHosts = [
			'dzsonline.nl',
			'data.dzsonline.nl',
			'dzsonline.limecreations.nl',
			'192.168.205.110',
		];

		// When no request method was found or the request method in the preflight request
		// is not found in the list with allowed methods, return a 405 Method Not Allowed
		// response and terminate the script.
		//
		if ( ($requestMethod == '') || !in_array($requestMethod, $allowedMethods) )
		{
			http_response_code(405); // Method Not Allowed
			die();
		}
		
		// When no origin was found (this can happen with same-origin requests, for which
		// browsers don’t send Origin header, some non-browser clients or tools like curl,
		// older browsers or certain cross-origin requests without credentials) in the
		// preflight request or the (host of the) origin is not found in the list with
		// allowed hosts, return a 403 Forbidden response and terminate the script.
		//
		if ( ($originHost == '') || !in_array($originHost, $allowedHosts) )
		{
			http_response_code(403); // Forbidden
			die();
		}

		// Return only allow-method for current methods, so we don't have to reveal all of
		// them.
		//
		header('Access-Control-Allow-Methods: ' . $requestMethod);

		// Return only allow-origin for current origin (indicating whether the response can
		// be made from the given origin), so we don't have to reveal the *.
		//
		header('Access-Control-Allow-Origin: ' . $origin);

		// When preflight-request was alright, return a 204 No Content response and
		// terminate the script.
		// 
		http_response_code(204); // No Content
		die();
	}

	// Set defines for different 'wedstrijd statussen', which will be used to specify if
	// played/completed or schedules/planned games must be fetched from the database.
	//
	define('DZS_WEDSTRIJDSTATUS_GESPEELD',	0x01);
	define('DZS_WEDSTRIJDSTATUS_GEPLAND',	0x02);

	/**
	 * Class for extracting data from the dzs tables and returning an array in an API-like
	 * structure so it can be outputted as JSON.
	 */
	class dzs_api
	{
		private $_mysqliConnection;

		public function __construct(mysqliConnection $mysqliConnection)
		{
			$this->_mysqliConnection = $mysqliConnection;
		}

		/**
		 * This method can be called in order to fetch data from the dzs-tables. Based on
		 * the specified type, various methods will be called. For example, when URL ending
		 * on programma.json is called, the request will be redirected to this file with
		 * ?_fetch=programma in the URL. From there, this method will be called with value
		 * 'programma' for variable $type, which will return the contents from method
		 * _fetchProgramma.
		 *
		 * @param string $type  The type of data to fetch from the dzs tables.
		 * @return array        Returns an array with the reqested data.
		 */
		public function fetch(string $type) : array
		{
			switch(strtolower($type))
			{
				case 'data':
					return $this->_fetchAll();

				case 'programma':
					return $this->_fetchProgramma();

				case 'standen':
					return $this->_fetchStanden();

				case 'teams':
					return $this->_fetchTeams();

				case 'uitslagen':
					return $this->_fetchUitslagen();

				case 'wedstrijden':
					return $this->_fetchWedstrijden();
			}

			return [];
		}

		/**
		 * Method thats being called when file 'standen.json' is requested. Calls method
		 * _getKlassen with the first argument set to true in order to rank/sort the teams
		 * based on their position.
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains the same information as the file class.inc.php from the
		 * old Aino module in /system/packages/pkg_dzsonline#v1.0/web/dzs_standen.
		 */
		private function _fetchStanden() : array
		{
			return [
				'klassen' => $this->_getKlassen(true)
			];
		}

		/**
		 * Method thats being called when file 'teams.json' is requested. Calls method
		 * _getKlassen in order to get all 'klassen' and for each 'klasse', all teams.
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains the same information as the file class.inc.php from the
		 * old Aino module in /system/packages/pkg_dzsonline#v1.0/web/dzs_teams.
		 */
		private function _fetchTeams() : array
		{	
			return [
				'klassen' => $this->_getKlassen()
			];
		}

		/**
		 * Method thats being called when file 'programma.json' is requested. Calls method
		 * _fetchWedstrijden with DZS_WEDSTRIJDSTATUS_GEPLAND for argument $wedstrijdStatus,
		 * so only 'wedstrijden' that are not yet played (date greater than the current
		 * date) will be fetched.
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains the same information as the file class.inc.php from the
		 * old Aino module in /system/packages/pkg_dzsonline#v1.0/web/dzs_programma.
		 */
		private function _fetchProgramma() : array
		{
			return $this->_fetchWedstrijden(wedstrijdStatus: DZS_WEDSTRIJDSTATUS_GEPLAND);
		}

		/**
		 * Method thats being called when file 'programma.json' is requested. Calls method
		 * _fetchWedstrijden with DZS_WEDSTRIJDSTATUS_GESPEELD for argument $wedstrijdStatus
		 * so only 'wedstrijden' that are already layed (date smaller than the current
		 * date) will be fetched.
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains the same information as the file class.inc.php from the
		 * old Aino module in /system/packages/pkg_dzsonline#v1.0/web/dzs_wedstrijden.
		 */
		private function _fetchUitslagen() : array
		{
			return $this->_fetchWedstrijden(DZS_WEDSTRIJDSTATUS_GESPEELD);
		}

		/**
		 * Method thats being called when file 'wedstrijden.json' is requested (or via
		 * methods _fetchProgramma, _fetchWedstrijden or _fetchAll). Creates an array
		 * including all 'klassen' (and all teams in each 'klasse'), all individual
		 * 'wedstrijddagen', all 'wedstrijden' (planned/played or all, based on the value
		 * for parameter $wedstrijdStatus) an all 'zalen'.
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains the same information as the file class.inc.php from the
		 * old Aino modules in /system/packages/pkg_dzsonline#v1.0/web/dzs_uitslagen and
		 * /system/packages/pkg_dzsonline#v1.0/web/dzs_wedstrijden.
		 */
		private function _fetchWedstrijden(int $wedstrijdStatus=0, bool $withStanden=false) : array
		{
			return [
				'klassen' => $this->_getKlassen($withStanden),
				'wedstrijddagen' => $this->_getWedstrijdDagen($wedstrijdStatus),
				'wedstrijden' => $this->_getWedstrijden($wedstrijdStatus),
				'zalen' => $this->_getZalen(),
			];
		}

		/**
		 * Method thats being called when file 'data.json' is requested. Calls method
		 * _fetchWedstrijden with 0 for argument $wedstrijdStatus and true for argument
		 * $withStanden. 
		 *
		 * This method returns an array in an API-like structure (which can be parsed as
		 * JSON), which contains all 'klassen' (including teams ranked/sorted based on their
		 * position), 'wedstrijddagen', 'wedstrijden' and 'zalen' will be returned.
		 *
		 * The output of this method may be enough to provide all the information needed on
		 * the frontend to feed data to all the different components.
		 */
		private function _fetchAll() : array
		{
			return $this->_fetchWedstrijden(0, true);
		}

		/**
		 * Method used to create/prepare an array with a meta/data-structure, just like in
		 * the CoCoS API. By default, we'll set count to -1, because the data-array isn't
		 * filled/used yet.
		 */
		private function _createDataArray() : array
		{
			return [
				'meta' => [
					'count' => -1
				],
				'data' => []
			];
		}

		/**
		 * Method used to add an item to the given $data-array.
		 */
		private function _addToDataArray(array &$data, array $item)
		{
			$data['data'][] = $item;
		}

		/**
		 * Method used to finish/finalize the meta/data-structured array. Will update the
		 * meta-count element based on the amount of items in the data-element.
		 */
		private function _finishDataArray(array &$data) : array
		{
			$data['meta']['count'] = count($data['data']);
			return $data;
		}

		/**
		 * Fetches all 'klassen' from the dzs-tables and returns an array with each 'klasse'
		 * and for each one, a list of all teams in that 'klasse'. The value for parameter
		 * $withStanden will be passed through to method _getTeams(), used to instruct
		 * whether or not teams has to be ranked/sorted based on their position.
		 */
		private function _getKlassen(bool $withStanden=false) : array
		{
			$klassen = $this->_createDataArray();

			$sqlQuery = "SELECT "
				. " `dzs_Klassen`.`KlasseId` "
				. " , `dzs_Klassen`.`Klasse` "

			. " FROM "
				. " `dzs_Klassen` "

			. " ORDER BY "
				. " `dzs_Klassen`.`Klasse`";

			$result = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			while($record = $result->fetch_object())
			{
				$klasse = [
					'id' => intval($record->KlasseId),
					'name' => $record->Klasse,
					'teams' => $this->_getTeams(intval($record->KlasseId), $withStanden)
				];

				$this->_addToDataArray($klassen, $klasse);
			}

			return $this->_finishDataArray($klassen);
		}

		/**
		 * Fetches all 'teams' from the dzs-tables and returns an array with all teams for
		 * the requested 'klasse'. When parameter $withStanden is set to true, additional
		 * information about the team will be fetched from the database in order to
		 * rank/sort them based on their position.
		 *
		 * Please note, queries and functionalities could perhaps be improved, but have been
		 * adopted from how they were previously in Aino, so that this new implementation
		 * produces the same results.
		 */
		private function _getTeams(int $klasseid=0, bool $withStanden=false) : array
		{	
			$teams = $this->_createDataArray();
			
			if($withStanden)
			{
				$sqlQuery = "SELECT "
					. " `dzs_Teams`.`TeamId` "
					. " , `dzs_Teams`.`Team` "
					. " , SUM(`dzs_Standen`.`Punten`) AS 'Punten' "
					. " , SUM(`dzs_Standen`.`Voor`) AS 'DoelpuntenVoor' "
					. " , SUM(`dzs_Standen`.`Tegen`) AS 'DoelpuntenTegen' "
					. " , SUM(`dzs_Standen`.`Gewonnen`) AS 'Gewonnen' "
					. " , SUM(`dzs_Standen`.`Gelijk`) AS 'Gelijk' "
					. " , SUM(`dzs_Standen`.`Verloren`) AS 'Verloren' "
					. " , SUM(`dzs_Standen`.`Gewonnen`+`dzs_Standen`.`Gelijk`+`dzs_Standen`.`Verloren`) AS 'Gespeeld' "
				. "  FROM "
					. " `dzs_Teams`, `dzs_Standen` "
					
				. "  WHERE "
					. " `dzs_Teams`.`Online`='1' "
					. " AND `dzs_Teams`.`Team` != '' "
					. (($klasseid>0)?" AND `dzs_Teams`.`KlasseId` = '".$klasseid."'":"")
					. " AND `dzs_Teams`.`TeamId` = `dzs_Standen`.`TeamId` "
					. " AND `dzs_Standen`.`Voor` > '-1' "
					. " AND `dzs_Standen`.`Tegen` > '-1' "

				. " GROUP BY `Team` "

				. " ORDER BY "
					. " Punten DESC "
					. " , DoelpuntenVoor DESC "
					. " , DoelpuntenTegen";
			}
			else
			{
				$sqlQuery = "SELECT "
					. " `dzs_Teams`.`TeamId` "
					. " , `dzs_Teams`.`Team` "

				. " FROM "
					. " `dzs_Teams` "

				. " WHERE "
					. " `dzs_Teams`.`Online`='1' "
					. " AND `dzs_Teams`.`Team` != '' "
					. (($klasseid>0)?" AND `dzs_Teams`.`KlasseId` = '".$klasseid."'":"")

				. " ORDER BY "
					. " `dzs_Teams`.`Team`";
			}

			$result = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			
			$pos = 0;
			while($record = $result->fetch_object())
			{	
				$team = [
					'id' => intval($record->TeamId),
					'name' => $record->Team,
				];

				if($withStanden)
				{
					$team['positie'] = (++$pos);
					$team['gespeeld'] = intval($record->Gespeeld);
					$team['gewonnen'] = intval($record->Gewonnen);
					$team['gelijk'] = intval($record->Gelijk);
					$team['verloren'] = intval($record->Verloren);
					$team['punten'] = intval($record->Punten);
					$team['puntenVoor'] = intval($record->DoelpuntenVoor);
					$team['puntenTegen'] = intval($record->DoelpuntenTegen);
				}

				$this->_addToDataArray($teams, $team);
			}

			return $this->_finishDataArray($teams);
		}

		/**
		 * Fetches all 'wedstrijdDagen' from the dzs-tables and returns an array with a
		 * single entry for each unique data for a 'wedstrijd' (scheduled or played, based
		 * on argument $wedstrijdStatus)
		 *
		 * Please note, queries and functionalities could perhaps be improved, but have been
		 * adopted from how they were previously in Aino, so that this new implementation
		 * produces the same results.
		 */
		private function _getWedstrijdDagen(int $wedstrijdStatus=0) : array
		{	
			$wedstrijdDagen = $this->_createDataArray();
			
			$sqlQuery = "SELECT "
				. " DISTINCT `dzs_Wedstrijden`.`Datum` "

			. " FROM "
				. " `dzs_Wedstrijden` "

			. " WHERE "
				. " `dzs_Wedstrijden`.`Online` = '1' ";
				
			switch($wedstrijdStatus)
			{
				case DZS_WEDSTRIJDSTATUS_GESPEELD:
					$sqlQuery .= " AND `dzs_Wedstrijden`.`Datum` < '".date('Y-m-d')."'";
					break;

				case DZS_WEDSTRIJDSTATUS_GEPLAND:
					$sqlQuery .= " AND `dzs_Wedstrijden`.`Datum` >= '".date('Y-m-d')."'";
					break;
			}
			
			$sqlQuery .= " ORDER BY "
				. " `dzs_Wedstrijden`.`Datum` "
				. (($wedstrijdStatus == DZS_WEDSTRIJDSTATUS_GESPEELD)?" DESC":"");

			$result = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			while($record = $result->fetch_object())
			{
				$wedstrijdDag = [
					'value' => $record->Datum,
					'name' => substr($record->Datum, 8,2)."-".substr($record->Datum, 5,2)."-".substr($record->Datum, 0,4)
				];

				$this->_addToDataArray($wedstrijdDagen, $wedstrijdDag);
			}

			return $this->_finishDataArray($wedstrijdDagen);
		}

		/**
		 * Fetches all 'wedstrijden' from the dzs-tables and returns an array with all
		 * 'wedstrijden' (scheduled or played, based on argument $wedstrijdStatus) and if
		 * available, the 'uitslag'.
		 *
		 * Please note, queries and functionalities could perhaps be improved, but have been
		 * adopted from how they were previously in Aino, so that this new implementation
		 * produces the same results.
		 */
		private function _getWedstrijden(int $wedstrijdStatus=0) : array
		{	
			$now = new DateTime();
			$wedstrijden = $this->_createDataArray();

			$sqlQuery = " SELECT "
				. " `dzs_Wedstrijden`.`WedstrijdId` "
				. " , `dzs_Wedstrijden`.`Datum` "
				. " , `dzs_Wedstrijden`.`Tijd` "
				. " , `dzs_Wedstrijden`.`ZaalId` "
				. " , `dzs_Wedstrijden`.`KlasseId` "
				. " , `dzs_Wedstrijden`.`TeamIdThuis` "
				. " , `dzs_Wedstrijden`.`DoelpuntenTeamThuis` "
				. " , `dzs_Wedstrijden`.`TeamIdUit` "
				. " , `dzs_Wedstrijden`.`DoelpuntenTeamUit` "

			.  " FROM "
				. " `dzs_Wedstrijden` "


			. " LEFT JOIN "
				. " `dzs_Zalen` ON `dzs_Wedstrijden`.`ZaalId` = `dzs_Zalen`.`ZaalId` "

			. " WHERE "
				. " `dzs_Wedstrijden`.`Online`='1' ";
						
			if(!empty($klasseid) || !empty($teamid) || !empty($zaalid) || !empty($date))
			{
				if(!empty($date))
				{
					$sqlQuery .= " AND `dzs_Wedstrijden`.`Datum` = '".$date."' ";			
				}
				
				if(!empty($klasseid))
				{
					$sqlQuery .= " AND `dzs_Wedstrijden`.`KlasseId` = '".$klasseid."' ";
				}
				
				if(!empty($teamid))
				{
					$sqlQuery .= " AND ( "
						. " `dzs_Wedstrijden`.`TeamIdThuis` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdUit` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdZaaldienst_01` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdZaaldienst_02` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdZaaldienst_03` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdZaaldienst_04` = '".$teamid."' "
						. " OR `dzs_Wedstrijden`.`TeamIdZaaldienst_05` = '".$teamid."' "
					. " ) ";
				}
				
				if(!empty($zaalid))
				{
					$sqlQuery .= " AND `dzs_Wedstrijden`.`ZaalId` = '".$zaalid."' ";
				}
			}
			
			switch($wedstrijdStatus)
			{
				case DZS_WEDSTRIJDSTATUS_GESPEELD:
					$sqlQuery .= " AND `dzs_Wedstrijden`.`Datum` < '".date('Y-m-d')."'";
					break;

				case DZS_WEDSTRIJDSTATUS_GEPLAND:
					$sqlQuery .= " AND `dzs_Wedstrijden`.`Datum` >= '".date('Y-m-d')."'";
					break;
			}
			
			$sqlQuery .= " AND `dzs_Wedstrijden`.`Archive`='0' "
				. " ORDER BY "
					. " `dzs_Wedstrijden`.`Datum` "
					. (($wedstrijdStatus == DZS_WEDSTRIJDSTATUS_GESPEELD)?" DESC":"")
					. ", `dzs_Wedstrijden`.`Tijd`, "
					. " `dzs_Zalen`.`Zaal` ";
					
			$result = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			
			$MaandTekst = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
			$MaandGetal = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
			
			while($record = $result->fetch_object())
			{	
				// When no 'wedstrijdStatus' is specified, go check the data of the
				// 'wedstijd' to see if it's planned/scheduled (in the future) or
				// played/completed.
				//
				if($wedstrijdStatus === 0)
				{
					$date = new DateTime($record->Datum);
					if($date < $now)
					{
						$status = DZS_WEDSTRIJDSTATUS_GESPEELD;
					}
					else
					{
						$status = DZS_WEDSTRIJDSTATUS_GEPLAND;
					}
				}
				else
				{
					$status = $wedstrijdStatus;		
				}

				$wedstrijd = [
					'id' => intval($record->WedstrijdId),
					'idZaal' => $record->ZaalId,
					'idKlasse' => $record->KlasseId,
					'datumTijd' => $record->Datum.'T'.$record->Tijd,
					'datum' => substr($record->Datum, 8,2)."-".substr($record->Datum, 5,2)."-".substr($record->Datum, 0,4),
					'datumText' => substr($record->Datum, 8,2)."-".str_replace($MaandGetal, $MaandTekst, substr($record->Datum, 5,2)),
					'tijd' => $record->Tijd,
					'idTeamThuis' => intval($record->TeamIdThuis),
					'status' => $status,
					// 'teamThuis' => $record->TeamnaamThuis,
					'doelpuntenTeamThuis' => (intval($record->DoelpuntenTeamThuis)>-1?intval($record->DoelpuntenTeamThuis):null),
					'idTeamUit' => intval($record->TeamIdUit),
					// 'teamUit' => $record->TeamnaamUit,
					'doelpuntenTeamUit' => (intval($record->DoelpuntenTeamUit)>-1?intval($record->DoelpuntenTeamUit):null),
					// 'uitslag' => (($record->DoelpuntenTeamThuis > -1 && $record->DoelpuntenTeamUit > -1)?$record->DoelpuntenTeamThuis." - ".$record->DoelpuntenTeamUit:null),
				];

				$this->_addToDataArray($wedstrijden, $wedstrijd);
			}

			return $this->_finishDataArray($wedstrijden);
		}

		/**
		 * Fetches all 'zalen' from the dzs-tables and returns an array with all 'zalen'.
		 *
		 * Please note, queries and functionalities could perhaps be improved, but have been
		 * adopted from how they were previously in Aino, so that this new implementation
		 * produces the same results.
		 */
		private function _getZalen() : array
		{	
			$zalen = $this->_createDataArray();

			$sqlQuery = "SELECT "
				. " `dzs_Zalen`.`ZaalId` "
				. " , `dzs_Zalen`.`Zaal` "

			. " FROM "
				. " `dzs_Zalen` "

			. " WHERE "
				. " `dzs_Zalen`.`Online` = '1' "

			. " ORDER BY "
				. " `dzs_Zalen`.`Zaal`";

			$result = $this->_mysqliConnection->ExecuteQuery($sqlQuery, __METHOD__.'@'.__FILE__.'#'.__LINE__);
			while($record = $result->fetch_object())
			{
				$zaal = [
					'id' => intval($record->ZaalId),
					'name' => $record->Zaal,
				];

				$this->_addToDataArray($zalen, $zaal);
			}

			return $this->_finishDataArray($zalen);
		}
	}

	// Include the MySQLi class, create an instance of the dzs_api class and call method
	// fetch with the request parameter _fetch from the URL. When no data is returned, return
	// a 404 Not Found response and terminate the script.
	//
	include('mysqli.class.php');
	$dzsApi = new dzs_api($mysqliConnection);
	$data = $dzsApi->Fetch($_REQUEST['_fetch']);

	if(count($data) == 0)
	{
		http_response_code(404); // Not found.
		die();
	}
	
	// Go encode the received data from the dzs_api class to a JSON-string and validate it. When
	// a JSON-error is thrown, return a 500 Internal Server Error and terminate the script.
	//
	$json = json_encode($data, JSON_THROW_ON_ERROR);
	if(json_last_error() !== JSON_ERROR_NONE)
	{
		http_response_code(500); // Internal Server Error
		die();
	}
	
	// When ending up here, everything went ok. The request was valid, a connection to the
	// database is established, we were able to fetch data from the dzs_api class based on the
	// requested data and converted it to a JSON string. Return a 200 Ok, set the content type
	// of the request to json, set the headers of our CORS policy so browsers are able to handle
	// the request, output the contents and terminate the script.
	//
	http_response_code(200); // Ok
	header('Content-Type: application/json; charset=utf-8');
	header('Access-Control-Allow-Methods: ' . $requestMethod);
	header('Access-Control-Allow-Origin: ' . $origin);

	print $json;
	die();