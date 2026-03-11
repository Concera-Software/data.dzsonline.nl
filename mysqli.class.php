<?php
	/**
	 * -- FILEDESCRIPTION:
	 *
	 * This file contains the mysqliConnection class, which is a wrapper around the mysqli
	 * extension to provide a more secure and convenient way to interact with MySQL databases.
	 * It includes methods for connecting to the database, executing queries, and handling
	 * results, while also providing basic protection against SQL injection through query
	 * building and argument validation.
	 *
	 * By including this file somewhere else, the $mysqliConnection variable will be become
	 * available as an instance of the mysqliConnection class, which can be used to interact
	 * with the database using the methods provided by the class. The configuration for the
	 * database connection is loaded from the mysqli.config.php file, which should be located
	 * in the same directory as this file and should contain the necessary configuration
	 * parameters for connecting to the database (host, username, password, database name, and
	 * optionally charset).
	 */

	// -----------------------------------------------------------------------------------------

	// Enable strict types (must be the very first statement in the script) and error reporting
	//
	declare(strict_types=1);
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	/**
	 * This class is a wrapper around the mysqli extension to provide a more secure and
	 * convenient way to interact with MySQL databases. It includes methods for connecting to
	 * the database, executing queries, and handling results, while also providing basic
	 * protection against SQL injection through query building and argument validation.
	 *
	 * The class is designed to be compatible with the old Aino-method Framework, and includes
	 * methods that mimic the behavior of the old framework's methods for configuration and
	 * query building.
	 *
	 * Error-handling is implemented very basic and only throws an exceptions when database
	 * connection or query execution fails.
	 */
	class mysqliConnection
	{
		private $_connection = null;
		private $_host = '127.0.0.1';
		private $_username = '';
		private $_password = '';
		private $_database = '';
		private $_characterSet = 'utf8mb4';

		/**
		 * Constructor for the mysqliConnection class. Initializes the connection
		 * parameters and connects to the database. The character set is set to UTF-8 by
		 * default for proper encoding.
		 *
		 * @param string $host
		 * @param string $username
		 * @param string $password
		 * @param string $database
		 * @param string $characterSet
		 */
		public function __construct(string $host, string $username, string $password, string $database, string $characterSet = 'utf8mb4')
		{
			$this->_host = $host;
			$this->_username = $username;
			$this->_password = $password;
			$this->_database = $database;
			$this->_characterSet = $characterSet;
			$this->connect();
		}

		/**
		 * Destructor for the mysqliConnection class. Closes the database connection (if
		 * available) when the object is destroyed. This way, we prevent leaving connections
		 * open unintentionally.
		 */
		public function __destruct()
		{
			$this->close();
		}

		/**
		 * Connects to the MySQL database using the mysqli extension. If the connection
		 * fails, an exception is thrown with the error message. If the connection is
		 * successful, the character set is set to the specified value (default is UTF-8)
		 * for proper encoding.
		 *
		 * @return boolean
		 */
		private function connect() : bool
		{
			$this->_connection = new mysqli(
				$this->_host,
				$this->_username,
				$this->_password,
				$this->_database
			);

			if ($this->_connection->connect_error)
			{
				// Handle connection error
				throw new Exception('Database connection failed: ' . $this->_connection->connect_error);
				return false;
			}

			// Set charset to UTF-8 for proper encoding
			$this->_connection->set_charset($this->_characterSet);
			return true;
		}

		/**
		 * Closes the database connection if it is open. This method is called in the
		 * destructor of this class to ensure that the connection is properly closed when
		 * the object is destroyed.
		 *
		 * @return void
		 */
		public function close() : void
		{	
			if( (gettype($this->_connection) == 'object') && method_exists($this->_connection, 'close') )
			{
				$this->_connection->close();
			}

			//
			$this->_connection = NULL;
		}

		/**
		 * Extracted from the old Aino-method Framework::GetConfigVar() in file
		 * system/framework/z999_framework.fw.php. This method is only here to be compatible
		 * with the old code and only returns the default value for the requested
		 * configuration variable.
		 *
		 * @param mixed $varname
		 * @param mixed $mode
		 * @param mixed $defaultt
		 * @param bool $getAsParsedVar
		 * @return
		 */
		function _getConfigVar($varname, $mode = null, $defaultt=null, $getAsParsedVar=true, $debug=false, $comments=null)
		{
			switch($varname)
			{
				case 'CHARSET_Default':
					return 'utf8';
			}

			return $defaultt;
		}

		/**
		 * Extracted from the old Aino-method Framework::getMode() in file
		 * system/framework/z999_framework.fw.php. This method is only here to be compatible
		 * with the old code and only returns 'web' as mode.
		 *
		 * Get system modus
		 */
		private function _getMode($mode='web') : string
		{
			return $mode;
		}

		/**
		 * Extracted from the old Aino-method Framework::getObjectValue() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * Return a value of a specific object key just to prevent php notices.
		 *
		 * @param mixed $value_object
		 * @param mixed $var
		 * @param mixed $default
		 * @return
		 */
		function _getObjectValue($value_object, $var, $default=null)
		{
			//
			if (is_array($value_object))
			{
				return $this->_getArrayValue($value_object,$var,$default);
			}

			//
			if (isset($value_object->$var))
			{
				return $value_object->$var;
			}

			return $default;
		}

		/**
		 * Extracted from the old Aino-method Framework::getArrayValue() in file
		 * system/framework/z999_framework.fw.php
		 *
	 	 * Return a value of a specific array key just to prevent php notices.
		 *
		 * @param mixed $value_array
		 * @param mixed $key
		 * @param mixed $default
		 * @return
		 */
		function _getArrayValue($value_array, $key, $default=null)
		{
			if (!is_array($value_array))
			{
				return $this->_getObjectValue($value_array, $key, $default);
			}

			if (isset($value_array[$key]))
			{
				return $value_array[$key];
			}

			return $default;
		}

		/**
		 * Extracted from the old Aino-method Framework::mySqlCharsetEncode() in file
		 * system/framework/z999_framework.fw.php
		*
		* @param mixed $value
		* @return
		*/
		private function _mySqlCharsetEncode(string $value) : string
		{
			$charset = strtolower($this->_getConfigVar("CHARSET_Default",$this->_getMode(),"utf8"));

			switch($charset)
			{
				case "utf-8" : return utf8_encode($value); break;
			}

			return utf8_encode($value);
		}

		/**
		 * Extracted from the old Aino-method Framework::mySqlCharsetDecode() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * @param mixed $value
		 * @return
		 */
		private function _mySqlCharsetDecode(string $value) : string
		{
			$charset = strtolower($this->GetConfigVar("CHARSET_Default",$this->_getMode(),"utf8"));

			//print "mySqlCharsetDecode($value) = ".utf8_decode($value)."<br>";

			switch($charset)
			{
				case "utf-8" : return utf8_decode($value); break;
			}
			
			return utf8_decode($value);
		}

		/**
		 * Escapes a string for use in a MySQL query to prevent SQL injection. This method
		 * uses the real_escape_string method of the mysqli connection to properly escape
		 * special characters in the string. It is important to use this method for any user
		 * input that will be included in a query to ensure that the input is treated as a
		 * literal string and not as part of the SQL syntax.
		 *
		 * @param string $string The string to escape.
		 * @return string The escaped string.
		 */
		private function _escapeString(string $string) : string
		{
			return $this->_connection->real_escape_string($string);
		}

		/**
		 * Extracted from the old Aino-method Framework::BuildQuery() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * Build a secure mysql query to prevent SQL-injections.
		 *
		 * @param mixed $query
		 * @param mixed $args
		 * @return string
		 */
		private function _buildQuery(string &$query, array $args, $encode=false) : string|bool
		{
			//check arguments.
			if (!is_array($args)) return false;

			//validate query.
			if (!$this->_validateQuery($query)) return false;

			/**
			// $lang_id = $this->getLanguageId();
			// if (is_array($lang_id)) $lang_id=$lang_id[0];
		
			//replace constants
			// $query = str_ireplace("%LANGUAGE.ID%", $lang_id, $query);
			// $query = str_ireplace("%LANGUAGE.SHORTCODE%", $this->getLanguageCode(), $query);
			*/

			//replace the variable-indicators by the variables (if secure);
			$args = array_reverse($args,true);

			//
			foreach($args as $key => $value)
			{
				// NEW:
				$value = strval($value);
				if (!$this->_validateArgument($value))
				{
					return false;
				}
				
				if ($encode) $value = $this->_valueEncode($value);
				$value = $this->_formatArgument(str_replace("%","&#37;", $value));
				$query = str_replace("{%".$key."}",$value,$query);
				$query = str_replace("%".$key,$value,$query);
			}

			//replace percentage signs just because we may want to search
			//for them later on.
			$query = str_replace("&#37;","%",$query);
			$query = str_replace("[%]","%",$query);

			return $query;
		}

		/**
		 * Extracted from the old Aino-method Framework::countChars() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * @param mixed $string
		 * @param mixed $char
		 * @return
		 */
		private function _countChars(string $string, string $char) : int
		{
			return strlen($string)-strlen(str_replace($char,'',$string));
		}

		/**
		 * Extracted from the old Aino-method Framework::ValidateQuery() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * Validate the query just bij counting the number of quotes. It's
		 * a very simple way to check the integrity of te query so you as
		 * a programma is warned if your query is insecure.
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		private function _validateQuery(string $value) : bool
		{
			$count  = $this->_countChars($value,"'");
			$count += $this->_countChars($value,'"');
			$count += $this->_countChars($value,'`');

			return (($count & 0x1) == 0);
		}

		/**
		 * Extracted from the old Aino-method Framework::ValidateArgument() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * Validate the value for usages in dynamicly build query-strings
		 * is non-secure items are found return false.
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		public function _validateArgument($value)
		{
			return true;
		}

		/**
		 * Extracted from the old Aino-method Framework::valueEncode() in file
		 * system/framework/z999_framework.fw.php
		 *
		 * @param mixed $value
		 * @return
		 */
		function _valueEncode($value)
		{
			return $this->_mySqlCharsetEncode($value);
		}

		/**
		 * Extracted from the old Aino-method Framework::FormatArgument() in file
		 * system/framework/z999_framework.fw.php
		 *
 		 * Format the argument in the first place just by adding slashes. In the
		 * future we can alles make sure formatting also controls character sets
		 *
		 * @param mixed $value
		 * @return string
		 */
		private function _formatArgument($value) : string
		{
			if (is_array($value))
			{
				$value = implode(" ",$value);
			}

			//first simple security action:
			// $value = addslashes($value);
			$value = $this->_escapeString(strval($value));

			//return secure value
			return $value;
		}

		/**
		 * Extracted from the old Aino-method Framework::mySqlExecuteQuery() in file
		 * system/framework/z999_framework.fw.php - Arguments like $connectionName, $cache
		 * and $encodeUtf8 are removed because they are not needed in this new
		 * implementation.
		 *
		 * @param string $queryString
		 * @param string $origin
		 * @return mysqli_result|boolean
		 */
		public function ExecuteQuery(string $queryString, string $origin) : mysqli_result|bool
		{
			//trim query-string.
			while(in_array($queryString[0],array(" ","\n","\r","\t"))){
				$queryString = ltrim($queryString);
			}

			// print $origin."\n".$queryString."\n\n";
			if (empty($queryString)) return false;
			
			try {
				$result = $this->_connection->query($queryString);

				if ($result === false)
				{
					// Log or handle the error as needed
					throw new Exception('Got MySQL Query Error #'.$this->_connection->errno.': ' . $this->_connection->error.' while executing query \''.$queryString.'\' at \''.$origin.'\'.');
					return false;
				}
			}
			catch (mysqli_sql_exception  $e)
			{
				throw new Exception('Got MySQL Query Error #'.$e->getCode().': ' . $e->getMessage().' while executing query \''.$queryString.'\'.');
				return false;
			}

			return $result;
		}

		/**
		 * Extracted from the old Aino-method Framework::mySqlExecuteQueryAndReturn() in file
		 * system/framework/z999_framework.fw.php - Arguments like $connectionName, $cache
		 * and $encodeUtf8 are removed because they are not needed in this new
		 * implementation.
		 *
		 * @param mixed $dbField
		 * @param mixed $queryString
		 * @param string $connectionName
		 * @param mixed $origin
		 * @param bool $cache
		 * @param bool $encodeUtf8
		 * @return
		 */
		public function ExecuteQueryAndReturn(string $dbField, string $queryString, string $origin, bool $encodeUtf8=false) : string|array|null
		{
			if (empty($queryString)) return null;

			$rslQuery = $this->ExecuteQuery($queryString, $origin);
			if ($rslQuery === false)
			{
				return null;
			}

			$rows = $rslQuery->num_rows;
			if ($rows > 1)
			{
				$fieldArray = array();
				$rslQuery->data_seek(0); //rewind the result set to the beginning
				
				while ($row = $rslQuery->fetch_object())
				{
					if ($encodeUtf8)
					{
						$fieldArray[] = $this->_mySqlCharsetDecode($row->$dbField);
					}
					else{
						$fieldArray[] = $this->_getObjectValue($row, $dbField);
					}
				}

				if (empty($seperator))
					return $fieldArray;
				else
					return implode(",", $fieldArray);
			}
			else{ //just return 1 value;
				if ($row = $rslQuery->fetch_object())
				{
					if ($encodeUtf8){
						return $this->_mySqlCharsetDecode($row->$dbField);
					}
					return $this->_getObjectValue($row, $dbField);
				}
				else{
					return null;
				}
			}
		}

		/**
		* Framework::mySqlSafeExecuteQuery()
		*
		* @param mixed $queryString
		* @param mixed $arguments
		* @param string $connectionName
		* @param mixed $origin
		* @param bool $cache
		* @param bool $encodeUtf8
		* @param bool $debug
		* @return void
		*/
		public function SafeExecuteQuery($queryString, $arguments, $origin) : mysqli_result|bool
		{
			if ($queryString = $this->_buildQuery($queryString, $arguments))
			{
				return $this->ExecuteQuery($queryString, $origin);
			}

			return false;
		}

		/**
		 * Extracted from the old Aino-method Framework::mySqlSafeExecuteQueryAndReturnArray() in file
		 * system/framework/z999_framework.fw.php
		 *	
		 * @param mixed $dbField
		 * @param mixed $queryString
		 * @param mixed $arguments
		 * @param string $connectionName
		 * @param mixed $origin
		 * @param bool $cache
		 * @param bool $encodeUtf8
		 * @param bool $debug
		 * @return
		 */
		public function SafeExecuteQueryAndReturn(string $dbField, string $queryString, array $arguments, string $origin) : string|array|null
		{
			if ($queryString = $this->_buildQuery($queryString, $arguments))
			{
				return $this->ExecuteQueryAndReturn($dbField, $queryString, $origin);
			}

			return false;
		}

		/**
		 * Returns the ID generated by a query on a table with a column having the
		 * AUTO_INCREMENT attribute. This method should be called immediately after the
		 * query that generated the ID to ensure that the correct ID is returned.
		 *
		 * @return integer  Returns the number of affected rows, or -1 if the last query
		 * failed.
		 */
		public function GetInsertId() : int
		{
			return $this->_connection->insert_id;
		}

		/**
		 * Returns the number of rows affected by the last INSERT, UPDATE, or DELETE query.
		 * This method should be called immediately after the query to ensure that the
		 * correct number of affected rows is returned.
		 *
		 * @return integer  Returns the number of affected rows, or -1 if the last query
		 * failed.
		 */
		public function GetAffectedRows() : int
		{
			return $this->_connection->affected_rows;
		}

		/**
		 * Method used to check if a table, based on the specified name, exists.
		 *
		 * @param string $tableName  The name of the table to check for existence.
		 * @return bool              Returns true if the table exists, false otherwise.
		 */
		public function TableExists(string $tableName) : bool
		{
			$tableNameEscaped = $this->_escapeString($tableName);
			$query = "SHOW TABLES LIKE '$tableNameEscaped'";
			$result = $this->ExecuteQuery($query, __METHOD__."@".__LINE__);
			return $result->num_rows > 0;
		}
	}

	// Start this file by checking if the configuration is already loaded, if not, try to load
	// it from the MySQLi configuration file. If the file is not found or the configuration is
	// incomplete, throw an exception.
	//
	if(!isset($mysqliConfig))
	{
		if(!file_exists($msqliConfig))
		{
			throw new Exception('MySQLi ConfigurationFile not found: $msqliConfig');
			die();
		}

		// Create variable $mysqliConfig as an empty array before including the
		// configuration file, so the configuration file can check if the variable is set
		// to prevent direct access and append the configuration to this variable.
		//
		$mysqliConfig = [];
		include($msqliConfig);
	}

	if(!isset($mysqliConfig['username'], $mysqliConfig['password'], $mysqliConfig['database']))
	{
		throw new Exception('MySQLi Configuration is incomplete. Please provide host, username, password and database in mysqli.config.php');
		die();
	}

	// When ending up here, we should have a valid configuration in the $mysqliConfig variable
	// and can create a new instance of the mysqliConnection class using the loaded
	// configuration.
	//
	$mysqliConnection = new mysqliConnection(
		($mysqliConfig['host']??'127.0.0.1'),
		$mysqliConfig['username'],
		$mysqliConfig['password'],
		$mysqliConfig['database'],
		($mysqliConfig['charset']??'utf8mb4'),
	);