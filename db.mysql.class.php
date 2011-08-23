<?php
/*
* MySQL Database Class
* By Timothy 'TiM' Oliver
* http://www.timoliver.com.au
* 
* Abstraction class that manages the establishment of a connection
* to a MySQL database, and streamlines queries made to it.
* 
* ============================================================================
* 
* Copyright (C) 2011 by Tim Oliver
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
* 
*/

class MySQLDatabase
{
	/**
	* The target DB hostname
	* @var string
	*/
	var $hostname = '';
	
	/** 
	* The name of the database to connect to
	* @var string
	*/
	var $database = '';
	
	/**
	* The database login name
	* @var string
	*/
	var $username = '';
	
	/** 
	* The database login password
	* @var string
	*/
	var $password = '';
	
	/**
	* A single prefix or list of prefixes
	* @var string or array
	*/
	var $prefix = NULL;
	
	/**
	* Link ID for MySQL connection
	* @var int
	*/
	var $link = NULL;

	/**
	* Main results handle for all general queries
	* @var int
	*/
	var $main_handle = NULL;
	
	/**
	* List of handles to manage concurrent queries
	* @var array
	*/ 
	var $handles = NULL;

	/*
	* Class Constructor
	*
	* Init MySQL and connect to server based on supplied arguments
	*
	* @param string 	$hostname 	The hostname of the target database
	* @param string 	$database 	The name of the target database
	* @param string 	$username 	The login username for the database
	* @param string 	$password 	The login password for the database
	* @param str/array	$prefix		A single or list of table prefixes
	* @param bool		$connect	Connect immediately on instantiation
	*
	*/
	function __construct( $hostname, $database, $username, $password, $prefix=NULL, $connect = true )
	{
		//init default values across the board
		$this->results = array();
		
		//set up database properties
		$this->database = $database;
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->prefix	= $prefix;

		//on by default, connect to the database straight away
		if( $connect )
			$this->connect();
	}

	/**
	* Class destructor
	*
	* Closes the connection to the server and frees up resources as needed
	*
	*/
	function __destruct()
	{
		mysql_close( $this->link );	
	}
	
	/**
	* Connect
	*
	* Connects to the MySQL database and throws an exception if it fails
	*
	*/
	function connect()
	{
		$this->link = mysql_connect( $this->hostname, $this->username, $this->password );
		
		if( $this->link === FALSE )
			throw new Exception('MySQLDatabase: Failed to connect to database.');	
			
		$db_selected = mysql_select_db( $this->database, $this->link );
		
		if( $db_selected === FALSE )
			throw new Exception('MySQLDatabase: Failed to select database: '.$this->database );	
	}
	
	/**
	* Query 
	*
	* Perform a query on the MySQL database.
	* 
	* @param string $query The MySQL query to execute. (MUST be sanitized by calling function beforehand)
	* @param string $handle - A unique ID that can be used to refer to this query later
	* 
	* @return bool(varies) Depending on the type of query:
	*		- 'SELECT' int Number of rows that were selected
	*		- 'UPDATE|REPLACE|DELETE' int Number of rows that were affected
	*		- 'INSERT' int The unique ID of the newly inserted row
	* 
	*/
	function query($query, $handle='')
	{
		//Perform the MySQL query
		$result = mysql_query($query, $this->link);

		//if result was straight-up false, return
		if( $result === FALSE )
		{
			$this->throw_mysql_error();
			return FALSE;
		}
		
		//if query was one that returns a resultset, grab and store it
		if( preg_match( '%^(select|show|describe|explain)%is', $query ) > 0 )
			$this->set_handle( $result, $handle );

		//if query was a 'SELECT', return the number of affected rows
		if( preg_match( '%^(select)%is', $query ) > 0 )
		{
			return mysql_num_rows($result);
		}
		//if query was one that affected existing rows (ie UPDATE, DELETE etc), return the number of affected rows
		elseif( preg_match( '%^(update|replace|delete)%is', $query ) > 0 )
		{
			return mysql_affected_rows($this->link);
		}
		//if query was an insert, return the new ID
		elseif( preg_match( '%^(insert)%is', $query ) > 0 )
		{
			return mysql_insert_id( $this->link );
		}

		return TRUE;
	}
	
	/**
	* Prepare
	*
	* Sanitizes a MySQL query and its arguments to reduce the 
	* potential of SQL injection attacks.
	*
	* @param string $query MySQL string to sanitize
	*
	*	Takes the following wildcards for the substitution (a la sprintf style)
	*	%s		- string value (No quotes required) 
	*	%d/%i 	- integer value
	*	%[0-9]f - float value [Int indicates rounding]
	*	%[0-9]t - table name (auto-appends prefix) [Int indicates prefix array index]
	*
	* After $query, an arbitrary number of arguments of types int, float, or string can be added which
	* will be substituted for each wildcard in that order.
	* In addition, a single array() containing the values can be used instead.
	* 
	* @return string The sanitized query string
	*/
	function prepare( $query = '' )
	{
		if( $query == '' )
			return '';
		
		//get the arguments from this method
		$args = func_get_args();
		array_shift($args); //remove $query
		
		//If the first optional argument
		//is an array, then assume that that
		//is the supplied list of arguments
		if( is_array($args[0]) )
			$args = $args[0];
		
		//loop through each argument
		foreach( $args as $arg )
		{
			//escape any '%' in the args
			$arg = str_replace( '%', '\\\\%', $arg );
			
			//select each argument from left to right, one at a time, retrieving the symbol, and offset
			if( preg_match( '/[^\\\\]%([0-9]*?)([dfst])/is', $query, $match, PREG_OFFSET_CAPTURE ) <= 0 )
				break;

			//grab the info from the match
			$tag	= strval($match[0][0]);		//The full match (eg %3t )
			$param 	= intval($match[1][0]);		//The number (if any) after the % (ie 4)
			$offset = intval($match[0][1]+1); 	//The location of these chars from the front of the string (ie 15) (+1 to offset escape slash)
			$symbol = strval($match[2][0]); 	//The particular parameter defined in the query (ie t)
		
			//prepare the argument for insertion
			switch( strtolower( $symbol ) )
			{
				//parse as an int
				case 'd':
				case 'i':
					$arg = intval( $arg );
					break;
				//parse as a float
				case 'f':
					//if an argument was given, round the float off to that set
					if( $param > 0 )
						$arg = round( $arg, $param );
						
					$arg = floatval( $arg );
					break;
				case 't':
					//if prefix is an array, then param becomes the index
					if( is_array( $this->prefix ) )
						$prefix = $this->prefix[$param];
					else
						$prefix = $this->prefix;
				
					$arg = ($prefix).(strval( $arg ));
					break;
				//parse as a string
				case 's':
				default:
					if( get_magic_quotes_gpc() )
						$arg = stripslashes( $arg );
					
					//sanitize with MySQL sanitation function
					$arg = "'".mysql_real_escape_string($arg, $this->link)."'";
					break;
			}
			
			//remove the '%%%' string from the query
			$query = substr_replace( $query, '', $offset, strlen($tag)-1 );
			
			//insert the sanitized value in its place
			$query = substr_replace( $query, $arg, $offset, 0 );
		}
		
		//unescape the % symbols
		$query = str_replace( '\\\\%', '%', $query );
		
		return $query;
	}
	
	/**
	* Fetch Row
	*
	* Iterates through all of the rows selected from a query and 
	* iteratively returns one on each call of this method.
	*
	* @param string $handle 			The unique handle of the SELECT query
	* @param bool 	$return_as_array	Return the row as an array, instead of an object
	* @param bool 	$close_on_finish	Automatically close the connection on completion
	*
	* @return array|object Depending on $return_as_array, an array or object containing all of the data from the current row
	*/
	function fetch_row( $handle='',  $return_as_array = FALSE, $close_on_finish = TRUE, )
	{
		$result = $this->get_handle($handle);
		if( !$result )
			return FALSE;
		
		//get the row
		$row = mysql_fetch_assoc( $result );
		
		//if loop has finished, close the result
		if( $row === FALSE )
		{
			if( $close_on_finish )
				mysql_free_result( $result );
				
			return FALSE;	
		}
		
		//return an object by default, but also allow for arrays
		if( $return_as_array )
			return $row;

		return (object)$row;
	}
	
	/**
	* Get Row
	* Return a single row from a query, formatted as array or object
	*
	* @param string $query 			The MySQL query (MUST be properly sanitized)
	* @param bool 	$return_as_array  Return associative array instead of object
	*
	* @return array|object Depending on $return_as_array, an array or object containing the first row from the query
	*
	*/
	function get_row( $query, $return_as_array = false)
	{
		if( $query == '' )
			return NULL;
		
		//perform the query
		$num_rows = $this->query( $query );
		if( $num_rows === FALSE || $num_rows <= 0 )
			return FALSE;
		
		//get the first row
		$row = $this->fetch_row( '', $return_as_array, false );
		
		//close the result
		mysql_free_result( $this->get_handle() );
		
		return $row;
	}
	
	/**
	* Get Rows
	*
	* Return multiple rows from a query as an array of objects or arrays
	*
	* @param string	$query  			The MySQL query (MUST be properly sanitized)
	* @param bool	$return_as_array  	Return associative array instead of object
	*
	* @return array|object Depending on $return_as_array, an index array containing either arrays or objects of each row
	*
	*/		
	function get_rows( $query, $return_as_array = false )
	{
		if( $query == '' )
			return NULL;
		
		//perform the query
		$num_rows = $this->query( $query );
		if( $num_rows === FALSE || $num_rows <= 0 )
			return FALSE;
			
		//final output
		$rows = array();
	
		//grab each row and add to the local array
		while( ( $row = $this->fetch_row( '', $return_as_array ) ) !== FALSE )
			$rows[] = $row;
		
		return $rows;
	}
	
	/**
	* Get Column
	*
	* Gets all of the entries from one column of a table (eg all of the IDs of a certain condition)
	* 
	* @param string $query 		The MySQL query (MUST be sanitized)
	* @param int	$col_offset Starting from 0, the offset ofthe column to get
	*
	* @return array An array containing all of the column items from each row
	*
	*/
	function get_column( $query, $col_offset = 0 )
	{
		$num_rows = $this->query( $query );
		
		if( $num_rows === FALSE )
			return FALSE;
		
		//grab handle of the call we just made
		$handle = $this->get_handle();
		
		//init final output array
		$rows = array();
		
		while( ($row = mysql_fetch_row($handle)) !== FALSE )
			array_push( $rows, $row[$col_offset] );
		
		//clean up the query in memory
		mysql_free_result( $handle );
		
		//return the result
		return $rows;
	}
	
	/**
	* Insert
	* 
	* Inserts a new row into a table
	*
	* @param array|string $table	Table to insert to (eg 'table' or array( 'table', '%2t' ) )
	* @param array $data			Data to insert into the table (eg array( 'col_name' => 'value' )
	* @param array $format			Array matching the order of $data, dictating each value data type (eg array('%s', '%d') )
	*
	* @return int The unique ID of the newly inserted row.
	*
	*/
	function insert( $table = '', $data = NULL, $format = NULL )
	{
		//check all necessary arguments
		if( !$table || !$data )
			return FALSE;
		
		//prepare an array to store the arg values
		$arg_list = array();
		
		//start building the query
		$query = 'INSERT INTO';
		
		//set the table name in the query
		if( is_array( $table ) ) //allow for different prefix
		{
			$query .= $table[0];
			$arg_list[] = $table[1];
		}
		else
		{
			$query .= ' %t ';
			$arg_list[] = $table;
		}
		
		$query .= ' (';
		
		//add the name of each column
		$i=0;
		foreach( $data as $name => $value )
		{
			$query .= ' '.$name;
			
			//if not the final value, be sure to append a comma
			if( $i < count( $data ) - 1)
				$query .= ',';
				
			$i++;				
		}
		
		$query .= ' ) VALUES (';
		
		//add the value from the data array
		//if possible, use proper formatting
		$i=0;
		foreach( $data as $name => $value )
		{
			if( is_array( $format ) )
				$query .= ' '.$format[$i];
			else
				$query .= ' %s';
			
			$arg_list[] = $value;
			
			//if not the final value, be sure to append a comma
			if( $i < count( $data ) - 1)
				$query .= ',';
			
			$i++;				
		}
		
		//cap off the end
		$query .= ' );';
		
		//prepare/sanitze the query
		$query = $this->prepare( $query, $arg_list );

		//execute the query and return the results
		return $this->query( $query );			
	}
	
	
	/**
	* Update
	*
	* Construct a query and then execute to update one or more entries in a table.
	*
	* @param array|string 	$table 	The name of table, and/or formatting 										(eg 'table' or array( 'table', '%2t' ) )
	* @param array 			$data  	The data to insert into table in name => value format 						(eg array( 'foo' => 'bar' ) )
	* @param array 			$where 	An array stating 1 or more conditions of the update query 					(eg array('id' => 1) )
	* @param array 			$format (optional) array dictating the data type of each data value 				(eg array( '%s', '%d' ) ) 
	* @param array			$where_format (optional)  array dictating the data type of each where data value 	(eg array( '%s', '%d' ) ) 
	*
	* @return int The number of affected rows
	*
	*/
	function update( $table = '', $data = NULL, $where = NULL, $format = NULL, $where_format = NULL )
	{
		//check all necessary arguments
		if( !$table || !$data || !$where )
			return FALSE;
		
		//prepare a list to store the insert args as they come
		$arg_list = array();
		
		//begin building the query
		$query = 'UPDATE';			
		
		//set the table name in the query
		if( is_array( $table ) ) //allow for different prefix
		{
			$query .= $table[0];
			$arg_list[] = $table[1];
		}
		else
		{
			$query .= ' %t';
			$arg_list[] = $table;
		}
		
		$query .= ' SET';
		
		//add each piece of data to the query
		$i=0;
		foreach( $data as $name => $value )
		{
			//if format is specified, use it, else default to string
			if( is_array( $format ) )
				$query .= ' '.$name.' = '.$format[$i];
			else
				$query .= ' '.$name.' = %s';
		
			//append the value to the arglist
			$arg_list[] = $value;
			
			//if not the final value, be sure to append a comma
			if( $i < count( $data ) - 1)
				$query .= ',';
				
			$i++;			
		}
		
		$query .= ' WHERE';
		
		//add each where condition to the query
		$i=0;
		foreach( $where as $name => $value )
		{
			//if format is specified, use it, else default to string
			if( is_array( $where_format ) )
				$query .= ' '.$name.' = '.$where_format[$i];
			else
				$query .= ' '.$name.' = %s';
		
			//append the value to the arglist
			$arg_list[] = $value;
			
			//if not the final value, be sure to append a comma
			if( $i < count( $where ) - 1)
				$query .= ',';
				
			$i++;			
		}
		
		$query .= ';';
		
		//prepare/sanitze the query
		$query = $this->prepare( $query, $arg_list );

		//execute the query and return the results
		return $this->query( $query );
	}
	
	/**
	* Delete Row
	*
	* Delete a single row from a table
	*
	* @param array|string 	$table 	The name of table, and/or formatting 										(eg 'table' or array( 'table', '%2t' ) )
	* @param array 			$where 	An array stating 1 or more conditions of the update query 					(eg array('id' => 1) )
	* @param array 			$format (optional) array dictating the data type of each data value 				(eg array( '%s', '%d' ) ) 
	*
	* @return int The number of affected rows
	*
	*/
	function delete_row( $table='', $where = NULL, $format = NULL )
	{
		//check all necessary arguments
		if( !$table || !$where )
			return FALSE;
		
		//prepare a list to store the insert args as they come
		$arg_list = array();
		
		//begin building the query
		$query = 'DELETE FROM';
		
		//set the table name in the query
		if( is_array( $table ) ) //allow for different prefix
		{
			$query .= $table[0];
			$arg_list[] = $table[1];
		}
		else
		{
			$query .= ' %t';
			$arg_list[] = $table;
		}
		
		$query .= ' WHERE';
		
		//add each where condition to the query
		$i=0;
		foreach( $where as $name => $value )
		{
			//if format is specified, use it, else default to string
			if( is_array( $format ) )
				$query .= ' '.$name.' = '.$format[$i];
			else
				$query .= ' '.$name.' = %s';
		
			//append the value to the arglist
			$arg_list[] = $value;
			
			//if not the final value, be sure to append a comma
			if( $i < count( $where ) - 1)
				$query .= ',';
				
			$i++;			
		}
		
		//lock it off at limit 1
		//(to prevent accidents)
		$query .= ' LIMIT 1';
		
		//prepare/sanitze the query
		$query = $this->prepare( $query, $arg_list );

		//execute the query and return the results
		return $this->query( $query );			
	}
	
	/**
	* Num Rows
	*
	* Get the number of rows returned from the query
	*
	* @param string $handle (optional) The unique handle for the target
	*
	* @return int The number of rows the query returned
	*
	*/
	function num_rows( $handle='' )
	{
		return mysql_num_rows($this->get_handle($handle));
	}
	
	/**
	* Affected Rows
	*
	* Get the number of rows affected from the query
	*
	* @param string $handle (optional) The unique handle for the target
	*
	* @return int The number of rows the query affected
	*
	*/		
	function affected_rows( $handle='' )
	{
		return mysql_affected_rows($this->get_handle($handle));			
	}
	
	/**
	* Insert ID
	*
	* Get the insert ID from the last insertion query made
	*
	* @return int The unique ID of the newly inserted row
	*
	*/
	function insert_id()
	{
		return mysql_insert_id($this->link);
	}
	
	/**
	* Get Handle
	*
	* Returns the unique MySQL resource handle given the associated 
	* handle name.
	*
	* @param string $handle (Optional) Name of handle to retrieve
	*
	* @return int The unique MySQL resource handle for that name
	*
	*/
	function get_handle( $handle='' )
	{
		//no handle specified, use the main hardcoded block
		if( !$handle )
			return $this->main_result;
		else
			return $this->results[strval($handle)];
	}
	
	/**
	* Set Result Handle
	*
	* Assigns a unique MySQL resource to a local unique name
	* so it can be retrieved later
	*
	* @param int 	$result The MySQL resource handle to set
	* @param string $handle (Optional) Name of handle to retrieve
	*/
	function set_handle( $result, $handle='' )
	{
		if( !$result )
			return false;
		
		if( !$handle )
			$this->main_result = $result;
		else
			$this->results[strval($handle)] = $result;
			
		return true;
	}
	
	/**
	* Throw MySQL Error
	*
	* Throws an exception and prints the last MySQL error generated.
	*
	*/
	function throw_mysql_error()
	{
		throw new Exception('MySQLDatabase: A MySQL error occurred: '.mysql_error() );	
	}
}

/*EOF*/