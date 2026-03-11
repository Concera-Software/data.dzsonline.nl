<?php

	/**
	 * -- FILEDESCRIPTION:
	 *
	 * This file ONLY contains the configuration for the MySQLi connection. It can be included
	 * in the file mysqli.class.php to load the configuration and create a new instance of the
	 * mysqliConnection class using the loaded configuration. The configuration should be an
	 * associative array with the following keys:
	 *
	 * - host: the hostname of the MySQL server (default: '127.0.0.1')
	 * - username: the username to connect to the MySQL server (required)
	 * - password: the password to connect to the MySQL server (required)
	 * - database: the name of the database to use (required)
	 * - charset: the character set to use (default: 'utf8mb4')
	 *
	 * Note: the host and charset keys are commented out, as they will use the default values
	 * if not set.
	 */

	// -----------------------------------------------------------------------------------------

	// Enable strict types (must be the very first statement in the script) and error reporting
	//
	declare(strict_types=1);
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	$documentRoot		= $_SERVER['DOCUMENT_ROOT']."/";
	$uploadDir		= $_SERVER['DOCUMENT_ROOT']."/uploads";
	$CFG_verifiers		= ["support@concera.com"];
	$CFG_importPwd		= "dzsonline";

	// To prevent this file from being accessed directly, we check if the variable $mysqliConfig
	// is set. This variable should be set in the file that includes this one. When the variable
	// is not set, return a 204 No Content response and terminate the script.
	//
	if(!isset($mysqliConfig))
	{
		http_response_code(204); // No content
		die();
	}

	// Setup the MySQLi configuration, used to connect with the database. 
	//
	$mysqliConfig = [
		// 'host' => '127.0.0.1', // default value
		'username' => 'dzsonline',
		'password' => 'dzsonline',
		'database' => 'dzsonline',
		// 'charset' => 'utf8mb4' // default value
	];