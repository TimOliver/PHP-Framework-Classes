<?php
	/*
	* PHP Input Sanitation Class
	* by Timothy 'TiM' Oliver
	* 
	* A class to allow efficient
	* filtering of all of the 
	* PHP global variables
	* to prevent potential
	* XSS exploits and hacks
	*/
	
	//The PHP data types that
	//this class can sanitize
	define( 'TYPE_STR', 	0 );
	define( 'TYPE_CHAR',	1 );
	define( 'TYPE_UINT', 	2 );
	define( 'TYPE_INT', 	3 );
	define( 'TYPE_FLOAT', 	4 );
	define( 'TYPE_BOOL', 	5 );
	
	class InputSanitizer
	{
		//GPC Variables (Merging of GET, POST and COOKIES)
		var $gpc_vars 	= NULL;
		
		//GET Variables
		var $get_vars 	= NULL;
		
		//POST Variables
		var $post_vars 	= NULL;
		
		//COOKIE Variables
		var $cookie_vars = NULL;
		
		//Session Variables
		var $sess_vars 	= NULL;
		
		/*
		* Class Constructor
		*
		* Inits the class and performs any requested
		* sanitation now
		*
		* Arguments
		* Array stating the name and type
		* of the data to sanitize
		*
		* ie
		* array( 'foo' => TYPE_STR )
		*/
		function __construct( $variables = NULL, $method = 'r' )
		{
			//init all of the vars as objects
			$this->gpc_vars		= new stdClass();
			$this->get_vars 	= new stdClass();
			$this->post_vars 	= new stdClass();
			$this->cookie_vars 	= new stdClass();
			$this->sess_vars 	= new stdClass();
			
			
			//if variables were supplied here,
			//sanitize them now
			if( $variables != NULL )
				$this->sanitize_array( $variables, $method );
		}
		
		/*
		* Sanitize Var
		*
		* Sanitizes a value from a PHP
		* Global Variable. The value is both
		* added to the internal properties of this class, and returned
		* here. Returns False on fail.
		*
		* Arguments:
		* $name - str - The name of the value ($_GET['foo'] -> 'foo' )
		* $method - char - The name of the global variable method to get ( g, p, c, s )
		* $type - enum - The data type to sanitize the data against
		*/
		function sanitize( $name = '', $method = 'r', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;
			
			//set up the var to store
			//this variable
			$value = NULL;
			
			//get value based on var
			switch( $method )
			{
				case 'g':	//GET var
					if( isset( $_GET[$name] ) )
						$value = $_GET[$name];
					else
						$value = '';
					break;
				case 'c': 	//Cookie
					if( isset( $_COOKIE[$name] ) )
						$value = $_COOKIE[$name];
					else
						$value = '';
					break;
				case 'p':	//POST var
					if( isset( $_POST[$name] ) )
						$value = $_POST[$name];
					else
						$value = '';
					break;
				case 'r':
				default:	//REQUEST var
					if( isset( $_REQUEST[$name] ) )
						$value = $_REQUEST[$name];
					else
						$value = '';
					break;
			}
			
			//check a value was actually recieved
			if( $value === NULL )
				return FALSE;
				
			//now parse the value based on the desired types
			switch( $type )
			{
				//Boolean type
				case TYPE_BOOL:
					//bool is true if not 0/null
					if( !$value )
						$value = FALSE;
					else
						$value = TRUE;
				
					break;
				//Float variable
				case TYPE_FLOAT: 
					$value = floatval( $value ); //force it as a float
					break;
				//Integer value
				case TYPE_INT:
					$value = intval( $value ); //force it as an int
					break;
				//unsigned integer
				case TYPE_UINT:
					$value = intval( $value ); //force as int
					if($value < 0 ) { $value = 0; } //cap at 0 if negative
					break;
				//Default/String value
				case TYPE_CHAR:
					$value = strval( $value ); //force string version
					$value = substr( $value, 0, 1 ); //clip it at 1 char
					break;
				case TYPE_STR:
				default:
					$value = strval( $value ); 				//first force it as a string
					$value = urldecode( $value );			//next, decode any URL ecoding
					$value = htmlspecialchars( $value );	//Decode HTML chars to safer values
					break;
			}
			
			//load it into the class properties for later access
			//get value based on var
			switch( $method )
			{
				case 'g':	//GET var
					$this->get_vars->{$name} 	= $value;
					break;
				case 'c': 	//Cookie
					$this->cookie_vars->{$name} 	= $value;
					break;
				case 'p':	//POST var
					$this->post_vars->{$name} 	= $value;
					break;
				case 'r':	//REQUEST var
				default:
					$this->gpc_vars->{$name}		= $value;
					break;
			}
			
			//Finally, return the value 
			return $value;
		}
		
		/*
		* Sanitize batch
		* 
		* Sanitize a batch of variables in
		* one method call!
		* Arguments
		* Array of arrays stating the var, name and type
		* of the data to sanitize
		*
		* ie
		* array( 'foo', => TYPE_STR )
		*/
		function sanitize_array( $variables = NULL, $method = 'r', $return_array = FALSE )
		{
			if( $variables == NULL )
				return FALSE;
			
			//init output
			$output = array();
			
			//sanitize the array of variables
			foreach( $variables as $name => $type )
				$output[$name] = $this->sanitize( $name, $method, $type );
			
			//if required, convert to object
			if( !$return_array )
				$output = (object)$output;
			
			//return the objects
			return $output;
		}
		
		/*
		* Extract 
		*
		* Extract all of the objects from a
		* specific method and return as a condensed
		* object
		*/
		function extract( $method = 'g' )
		{
			$output = new stdClass();
		
			//select which method array to get from
			switch( $method )
			{
				case 'r':
					$method_array = $_REQUEST;
					break;
				case 'c':
					$method_array = $_COOKIE;
					break;
				case 'p':
					$method_array = $_POST;
					break;
				case 'g':
				default:
					$method_array = $_GET;
			}
			
			//loop through and sanitize each one
			foreach( $method_array as $name=>$value )
				$output->{$name} = $this->sanitize( $name, $method );
			
			//return the results
			return $output;
		}
		
		/*
		* get REQUEST variable
		*
		* Grabs the value from REQUEST, sanitizes
		* it, and returns it
		*/
		function gpc( $name = '', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;
			
			//if this value was already sanitized,
			//return the saved one. (REQUEST values are always static)
			if( isset( $this->gpc_vars->{$name} ) )
				return $this->gpc_vars->{$name};
				
			return $this->sanitize( $name, 'r', $type );
		}		
		
		/*
		* get GET variable
		*
		* Grabs the value from GET, sanitizes
		* it, and returns it
		*/
		function get( $name = '', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;
			
			//if this value was already sanitized,
			//return the saved one. (GET values are always static)
			if( isset( $this->get_vars->{$name} ) )
				return $this->get_vars->{$name};
				
			return $this->sanitize( $name, 'g', $type );
		}
		
		/*
		* get POST variable
		*
		* Grabs the value from POST, sanitizes
		* it, and returns it
		*/
		function post( $name = '', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;
			
			//if this value was already sanitized,
			//return the saved one. (POST values are always static)
			if( isset( $this->post_vars->{$name} ) )
				return $this->post_vars->{$name};
				
			return $this->sanitize( $name, 'p', $type );
		}
		
		/*
		* get REQUEST variable
		*
		* Grabs the value from REQUEST, sanitizes
		* it, and returns it
		*/
		function request( $name = '', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;
			
			//if this value was already sanitized,
			//return the saved one. (POST values are always static)
			if( isset( $this->request_vars->{$name} ) )
				return $this->request_vars->{$name};
				
			return $this->sanitize( $name, 'r', $type );
		}		
		
		/*
		* get COOKIE variable
		*
		* Grabs the value from COOKIE, sanitizes
		* it, and returns it
		*/
		function cookie( $name = '', $type = TYPE_STR )
		{
			if( !$name )
				return FALSE;

			//if this value was already sanitized,
			//return the saved one. (COOKIE values are always static)
			if( isset( $this->cookie_vars->{$name} ) )
				return $this->cookie_vars->{$name};

			return $this->sanitize( $name, 'c', $type );
		}		
	}
?>