<?php
/*
* PHP User Login Class
* by Timothy 'TiM' Oliver
* 
* A class that handles
* user registeration, login,
* password persistence, and edit details
* using secure login and sessions
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

//Regex pattern used to validate login name
define( 'USERNAME_REGEX',	'%[^a-z0-9\-\[\]\.\_=!\$\%\^&*(){}?@#$+\'"\/]+%is' );
define( 'EMAIL_REGEX',		'%[a-z0-9._-]+@[a-z0-9_-]+\.[a-z.]+%i' );

//Cookie defines
define( 'COOKIE_TIMEOUT', (52*7*60*60) ); //cookies set to a year by default

//current time
if( !defined( 'TIME_NOW' ) )
	define( 'TIME_NOW', time() );

class UserLogin
{
	//The MySQL DB class to use
	private $db = NULL;
	
	//The user's userinfo in an array
	public $userinfo = NULL;
	
	//a string holding the cookie prefix
	private $cookie_prefix = '';
	
	//an entry holding the DB wildcard
	private $table_wildcard = '';
	
	//user is logged into the system
	private $logged_in = false;
	
	//user privelige level
	private $user_level = 0;
	
	//logout hash - used to verify logout requests
	private $logout_hash = '';
	
	//array to hold all of the errors 
	private $errors = NULL;
	
	//The various user levels
	const LEVEL_PENDING 	= 0; //User is still pending email confirmation
	const LEVEL_USER 		= 1; //Standard user with normal privaleges
	const LEVEL_MODERATOR 	= 2; //Special case users with higher privaleges
	const LEVEL_ADMIN 		= 3; //Administrators with all privaleges
	
	//Password flags
	const PW_NONE			= 0;	//Password is default unencoded
	const PW_MD5 			= 1;	//Password has been ecoded to MD5 already
	const PW_SALT 			= 2;	//Password has been salted already
	
	/*
	* Class Constructor
	*
	* db 				- MySQLDatabase Object - Database to make queries to
	* cookie_prefix 	- str - unique cookie prefix
	* table_wildcard	- str - If needed, a different representation of the table wildcard (eg %2t for multiple prefixes)
	*/
	function __construct( $db=NULL, $cookie_prefix = 'usr', $table_wildcard = '%t' )
	{
		//make sure a valid DB was supplied	
		if( $db == NULL )
			throw new Exception( 'UserLogin: Database object was invalid!' );
		
		$this->cookie_prefix = $cookie_prefix;
		$this->table_wildcard = $table_wildcard;
		$this->db = &$db;
		$this->errors = array();
		
		//set up the session
		session_name( $this->cookie_prefix.'Login' ); 	//sync to the previous/current session (if any)
		session_start(); 								//enable the session
		
		//grab the unique ID of this session
		$session_id = session_id();
		
		//check if a session already exists
		if( isset( $_SESSION['userid'] ) )
		{
			//get user info from DB off ID
			$query = $this->db->prepare( 'SELECT * FROM '.$this->table_wildcard.' WHERE id = %d LIMIT 1', 'users', $_SESSION['userid'] );
			$this->userinfo = $this->db->get_row( $query );
			
			if( $this->userinfo != NULL )
				$this->logged_in = true;
		}
		
		//session failed, try cookies
		if( $this->logged_in == false && isset($_COOKIE[ $this->cookie_prefix.'ID']) )
		{
			$id 			= intval($_COOKIE[ $this->cookie_prefix.'ID']);
			$username 		= strval($_COOKIE[ $this->cookie_prefix.'UserName']);
			$password 		= strval($_COOKIE[ $this->cookie_prefix.'Password']);
							
			if( $id && $username && $password )
				$this->login( $username, $password, true );
		}
	}
	
	/*
	* Login
	*
	* Verify the username and password against the database
	* and log the user in if successful.
	*
	* The password can be set as
	* raw 			- sent unencrypted from the browser
	* unsalted md5 	- encoded as md5, but unsalted (ie sent from the browser as MD5 from JavaScript)
	* salted md5 	- fully encoded as a salted md5 (ie sent from a cookie, or from another persistence method)
	*
	* Arguments:
	* username 		- str - The user's username
	* password 		- str - The user's password (can be raw, unsalted md5 or salted md5)
	* remember_me 	- bool - Whether to remember this user or not
	* password_flags - int - Binary flags denoting the state of the submitted password (PW_NONE, PW_MD5, PW_SALT)
	*/
	function login( $username = '', $password = '', $remember_me = false, $password_flags = PW_NONE )
	{
		if( !strlen( $username ) )
		{
			$this->errors[] = 'No username was submitted!';
			return FALSE;
		}
		
		if( !strlen( !$password ) )
		{
			$this->errors[] = 'No password was submitted!';
			return FALSE;
		}
		
		$query = $this->db->prepare( 'SELECT * FROM '.$this->table_wildcard.' WHERE username = %s LIMIT 1', 'users', $username );
		$userinfo = $this->db->get_row( $query );
		
		//if this fails, it means the username didn't exist
		if( $userinfo == NULL )
		{
			$this->errors[] = 'No user with name \''.$username.'\' could be found.';
			return FALSE;
		}

		//if raw password, md5 it, then salt it
		if( $password_flags == UserLogin::PW_NONE )
			$password = md5( md5( $password ).$userinfo->salt );
		elseif( $password_flags == UserLogin::PW_MD5 ) //password was supplied in md5, but it's unsalted. salt it now
			$password = md5( $password.$userinfo->salt );

		//the money shot. Check the password is correct
		if( strcmp( $password, $userinfo->password ) != 0 )
		{
			$this->errors[] = 'Password was incorrect.';
			return FALSE;	
		}
		
		//hooray! The user is valid!
		$this->logged_in = true;
		
		//if remember me was set, but no cookies exist, create the cookies now
		if( $remember_me && !isset( $_COOKIE[ $this->cookie_prefix.'ID'] ) )
		{
			setcookie( $this->cookie_prefix.'ID', 			$userinfo->id, 				TIME_NOW + COOKIE_TIMEOUT, '' ); //User ID
			setcookie( $this->cookie_prefix.'UserName', 	$userinfo->username, 		TIME_NOW + COOKIE_TIMEOUT, '' ); //User Name
			setcookie( $this->cookie_prefix.'Password', 	$userinfo->password, 		TIME_NOW + COOKIE_TIMEOUT, '' ); //User Password
		}
		
		//finally set up the session
		$this->userinfo 		= $userinfo;
		$_SESSION['userid'] 	= $userinfo->id;
		$this->logout_hash 		= md5( $userinfo->id . $userinfo->username . $userinfo->password );
		
		return TRUE;
	}
	
	/*
	* Logout
	*
	* The user requested a logout. Verify this request is
	* legit, and then destroy the session and delete the cookies
	*
	* Arguments
	* require_hash 	- bool - the logout hash is required (should be true for all browser requests)
	* hash 			- bool - the logout hash supplied to verify this action
	*/
	function logout( $require_hash = true, $hash = '' )
	{
		//if hash is required, yet none was supplied
		if( $require_hash && $hash == '' )
			return FALSE;
			
		//if hash is required, and the hash doesn't match
		if( $require_hash && strcmp( $hash, $this->logout_hash ) != 0 )
			return FALSE;
			
		//valid request, start to log out
		
		//destroy the cookies
		if( isset($_COOKIE[$this->cookie_prefix.'ID']) )
		{	
			//Set cookies to one ago. Browser will auto-purge them.
			setcookie( $this->cookie_prefix.'ID', 			'', 	TIME_NOW - 3600 );	//User ID
			setcookie( $this->cookie_prefix.'UserName', 	'', 	TIME_NOW - 3600 ); //User Name
			setcookie( $this->cookie_prefix.'ID', 			'', 	TIME_NOW - 3600 ); //User Password					
		}
		
		//destroy the session
		unset( $_SESSION['userid'] );
		session_destroy();
		
		//set class to disacknowledge the logged in status
		$this->logged_in = FALSE;
		
		return TRUE;
	}
	
	/*
	* Register
	*
	* Register a new user. It's assumed the password and email are validated before being passed here.
	*
	* Arguments:
	* username 			- The user username (Will be tested for validity)
	* password 			- The user password (Will be assumed to be valid)
	* email				- The user email	(Will be tested for validity)
	* require_confirm	- Requires the user to activate the account via email (optional)
	*
	* Return:
	* If success, it returns an object with all of the new user properties. On fail returns NULL
	*/
	function register( $username = '', $password = '', $email = '', $require_confirm = true )
	{
		//ensure all arguments were supplied
		if( !$username || !$password || !$email )
			return FALSE;
			
		//make sure that the $username is valid
		if( preg_match( USERNAME_REGEX, $username ) > 0 )
		{
			$this->errors[] = 'Username contained invalid characters';
			return FALSE;
		}
		
		//make sure email is valid
		if( !preg_match( EMAIL_REGEX, $email ) )
		{
			$this->errors[] = 'Email address wasn\'t valid.';
			return FALSE;
		}
		
		//check that a user with that email and/or username doesn't already exist
		if( $this->username_exists( $username ) )
		{
			$this->errors[] = 'An account with that username already exists.';
			return FALSE;
		}
		
		if( $this->email_exists( $email ) )
		{
			$this->errors[] = 'An account with that email already exists.';
			return FALSE;
		}			
		
		//At this point, we've confirmed the account credentials are valid
		
		//generate a salt for the password
		//6 letter string, generated randomly
		srand( TIME_NOW );
		$random_hash = md5( TIME_NOW . $username . $email . rand());
		
		$salt = substr( md5($random_hash), 0, 6 );
		
		//generate the encrypted password
		$new_password = md5(md5($password).$salt);
		
		//insert the user into the database
		$user_vars = array( 'username' 		=> $username, 
							 	'password' 		=> $new_password, 
								'salt' 			=> $salt, 
								'email' 		=> $email, 
								'joindate' 		=> TIME_NOW,
								'level' 		=> ($require_confirm ? LEVEL_PENDING : LEVEL_USER ),
								'confirmhash' 	=> ($require_confirm ? $random_hash : '' ) );
		
		$new_id = $this->db->insert( 'users', $user_vars );
		if( $new_id == NULL || $new_id == 0 )
			return NULL;
		
		//incorporate the newly generated ID into the user info
		$user_vars['id'] = $new_id;
		
		//return the array as a PHP object
		return (object)$user_vars;
	}
	
	/*
	* Activate User
	*
	* When an account registration requires confirmation (eg email), the account
	* is activated from the pending state with this function.
	*
	* Arguments:
	* $user_id 				- Int - The database ID of the target user.
	* $user_confirm_hash 	- Str - The confirmation hash currently saved in the user account
	*
	* Return: TRUE on success, FALSE on fail
	*/
	function activate_user( $user_id = 0, $user_confirm_hash = '' )
	{
		if( $user_id == 0 || $user_confirm_hash == '' )
		{
			$this->errors[] = 'Please supply all necessary information';
			return FALSE;
		}
		
		//get the username 
		$query = $this->db->prepare( 'SELECT * FROM '.$this->table_wildcard.' WHERE username = %s LIMIT 1', 'users', $username );
	}
	
	/*
	* Username Exists
	*
	* Check the database to see if a user with that
	* username already exists.
	*
	* Arguments:
	* $username - Str - Username to check. Should be raw (Will be sanitized).
	*/
	function username_exists( $username = '' )
	{
		if( !$username )
			return FALSE;
			
		$query = $this->db->prepare( 'SELECT * FROM '.$this->table_wildcard.' WHERE username = %s LIMIT 1', 'users', $username );
		$userinfo = $this->db->get_row( $query );
		
		if( $userinfo != NULL )
			return TRUE;
		
		return FALSE;
	}
	
	/*
	* Email Exists
	*
	* Check the database to see if a user with that
	* email already exists.
	*
	* Arguments:
	* $email - Str - Username to check. Should be raw (Will be sanitized).
	*/
	function email_exists( $email = '' )
	{
		if( !$email )
			return FALSE;
			
		$query = $this->db->prepare( 'SELECT * FROM '.$this->table_wildcard.' WHERE email = %s LIMIT 1', 'users', $email );
		$userinfo = $this->db->get_row( $query );
		
		if( $userinfo != NULL )
			return TRUE;
		else
			return FALSE;
	}		
	
	/*
	* Logged in
	*
	* Returns if the user is currently logged in or not
	*/
	function logged_in()
	{
		return $this->logged_in;	
	}

	/*
	* Is Mod
	*
	* Returns if the user account is  moderator or not
	*/
	function is_moderator()
	{
		return $this->userinfo->level == UserLogin::LEVEL_MODERATOR;	
	}

	/*
	* Is Admin
	*
	* Returns if the user account is admin or not
	*/
	function is_admin()
	{
		return $this->userinfo->level == UserLogin::LEVEL_ADMIN;	
	}
	
	/*
	* Is User level
	*
	* Returns if the user account is at user level or not
	*/
	function is_user_level()
	{
		return $this->userinfo->level == UserLogin::LEVEL_USER;	
	}
	
	/*
	* Print last error
	*
	* Print the last error that occurred
	*/
	function get_last_error( $echo = false )
	{
		if( $echo )
			echo $this->errors[count($this->errors)-1];
		else
			return $this->errors[count($this->errors)-1];
	}
}
?>