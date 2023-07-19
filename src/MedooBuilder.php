<?php

namespace XModule\XMedooBuilder;

use Medoo\Medoo;

class MedooBuilder {
	private static array $_db_object_list = [];
	private static array $_db_options_list = [];

	static function db($index=0): ?Medoo {
		return self::$_db_object_list[$index]??null;
	}

	static function all_connections(): array {		return self::$_db_object_list;	}
	static function all_options(): array { 		return self::$_db_options_list;	}

	static function createConnection($database, $user='root', $pass = '', $host='localhost', $options = [], $index=0 ) {
		self::$_db_options_list[$index] = array_merge([
			             // [required]
			             'type' => 'mysql',
			             'host' => $host,
			             'database' => $database,
			             'username' => $user,
			             'password' => $pass,

			             // [optional]
			             'charset' => 'utf8mb4',
			             'collation' => 'utf8mb4_unicode_520_ci',
			             'port' => 3306,

			             // [optional] The table prefix. All table names will be prefixed as PREFIX_table.
			             'prefix' => '',

			             // [optional] To enable logging. It is disabled by default for better performance.
			             //	'logging' => true,

			             // [optional]
			             // Error mode
			             // Error handling strategies when the error has occurred.
			             // PDO::ERRMODE_SILENT (default) | PDO::ERRMODE_WARNING | PDO::ERRMODE_EXCEPTION
			             // Read more from https://www.php.net/manual/en/pdo.error-handling.php.
			             'error' => \PDO::ERRMODE_SILENT,

			             // [optional]
			             // The driver_option for connection.
			             // Read more from http://www.php.net/manual/en/pdo.setattribute.php.
			             'option' => [ \PDO::ATTR_CASE => \PDO::CASE_NATURAL ],

			             // [optional] Medoo will execute those commands after the database is connected.
			             'command' => [ 'SET SQL_MODE=ANSI_QUOTES' ]
		],$options);
		self::$_db_object_list[$index] = new Medoo(self::$_db_options_list[$index]);
	}
}