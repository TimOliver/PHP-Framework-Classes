<?php
	/*
	* Mailer Class
	* By Timothy 'TiM' Oliver
	*
	* A simple class to allow quick and
	* easy customisation, and sending 
	* of mail from PHP.
	*/
	
	class Mailer
	{
		//a string holding all of the emails to send to
		private $to = '';
		
		//a string stating who the mail is from
		private $from = '';
		
		//as needed, an alt reply address than 'from'
		private $reply_to = '';
		
		//an string holding all of the emails to CC this email to
		private $cc = '';
		
		//an string holding all of the emails to BCC this email to
		private $bcc = '';
		
		//the subject of the email
		private $subject = '';
		
		//the email message to mail
		private $message = '';
		
		//an alternate message to send if the receiver can't handle HTML
		private $alt_message = '';
		
		//an array to hold all of the config values
		//(Too large for individual variables)
		private $config = NULL;
		
		//An array to store any errors that occur
		private $debugger = '';
		
		function __construct( $config = NULL )
		{
			//init all of the mail variables
			$this->clear();
			
			//init the config with default values
			$this->config = array( 
							'useragent' 	=> 'PHP Mailer',			//Name of UserAgent
							'wordwrap'		=> TRUE,					//Wrap long lines of text
							'wraplength'	=> 	70,						//Wrap line length in chars
							'mailtype'		=> 'html',					//Type of email (text, HTML)
							'charset'		=> 'utf-8',					//Character set to use (utf-8, iso-8859-1, etc)
							'validate'		=> TRUE,					//Validate each email address supplied
							'newline'		=> "\n",					//Newline symbols used in the request header
							);
			
			//if a config argument was supplied, overlay it into the default one
			if( $config !== NULL && is_array($config) )
				$this->config = array_merge( $this->config, $config );
		}
		
		/*
		* To
		*
		* Sets the single or list of emails to send the email to.
		*
		* Arguments can either be a single string, multiple comma-delimited strings
		* or an array of email addresses.
		*
		* NB: Each email must conform to the RFC 2822 standards format.
		*/
		function to( $address='' )
		{
			if( func_num_args() == 0 )
				return FALSE;

			$this->to = $this->parse_address_args( func_get_args() );
			
			return TRUE;
		}

		/*
		* CC
		*
		* Sets the single or list of emails to CC the email to.
		*
		* Arguments can either be a single string, multiple comma-delimited strings
		* or an array of email addresses.
		*
		* NB: Each email must conform to the RFC 2822 standards format.
		*/
		function cc( $address = '' )
		{
			if( func_num_args() == 0 )
				return FALSE;

			$this->cc = $this->parse_address_args( func_get_args() );	
			
			return TRUE;
		}

		/*
		* BCC
		*
		* Sets the single or list of emails to BCC the email to.
		*
		* Arguments can either be a single string, multiple comma-delimited strings
		* or an array of email addresses.
		*
		* NB: Each email must conform to the RFC 2822 standards format.
		*/
		function bcc( $address = '' )
		{
			if( func_num_args() == 0 )
				return FALSE;

			$this->bcc = $this->parse_address_args( func_get_args() );	
			
			return TRUE;
		}

		/*
		* From
		*
		* Set the source of this message
		*/
		function from( $address='', $name = '' )
		{
			if( !$address )
				return FALSE;
			
			//if required, test the email for validity
			if( $this->config['validate'] )
			{
				if( !$this->email_is_valid( $address, true ) )
				{
					$this->debug('Invalid source email was supplied: '.$address );
					return FALSE;
				}
			}
			
			if( $name )
				$this->from = $name.' <'.$address.'>';
			else
				$this->from = $address;
				
			return TRUE;
		}
		
		/*
		* Reply to
		*
		* Set a reply-to address for this message
		*/
		function reply_to( $address = '', $name = '' )
		{
			if( !$address )
				return FALSE;
			
			//if required, test the email for validity
			if( $this->config['validate'] )
			{
				if( !$this->email_is_valid( $address, true ) )
					$this->debug('Invalid reply-to email was supplied: '.$address );
			}
			
			if( $name )
				$this->reply_to = $name.' <'.$address.'>';
			else
				$this->reply_to = $address;
				
			return TRUE;				
		}
		
		/*
		* Subject
		*
		* Set the subject of the email
		*/
		function subject( $subject = '' )
		{
			if( !$subject )
				return FALSE;
				
			$this->subject = $subject;
		}

		/*
		* Message
		*
		* Set the message of the email
		*/
		function message( $message = '' )
		{
			if( !$message )
				return;
			
			//if the message needs to be wrapped, do it now.
			if( $this->config['wordwrap'] )
				$this->message = $this->wrap_message( $message, $this->config['wraplength'], ($this->config['mailtype'] == 'html') );
			else
				$this->message = $message;
		}

		/*
		* Alt Message
		*
		* A textual version of an HTML email that can be
		* passed along if the event the client can't read HTML.
		*/
		function alt_message( $alt_message = '' )
		{
			//pointless if the primary message is already text
			if( $this->config['mailtype'] == 'text' )
				return;

			//if the message needs to be wrapped, do it now.
			if( $this->config['wordwrap'] )
				$this->alt_message = $this->wrap_message( $alt_message, $this->config['wraplength'], false );
			else
				$this->alt_message = $alt_message;
		}

		/*
		* Parse Address Arguments
		*
		* Parse a list of addresses, and validate them if need be
		*
		* Arguments can either be a single string, multiple comma-delimited strings
		* or an array of email addresses.
		*
		* NB: Each email must conform to the RFC 2822 standards format.
		*/
		private function parse_address_args( $args = NULL )
		{
			if( $args == NULL )
				return NULL;
			
			//new list of addresses
			$address_list = array();
			
			//if the first argument is an array,
			//extract all of the addresses from it,
			//else assume it's a delimited list of strings
			if( is_array( $args[0] ) )
			{
				foreach( $args[0] as $address )
				{
					if( is_string($address ) ) //skip non-string entries
						array_push( $address_list, $address );
				}
			}
			else
			{
				foreach( $args as $address )
				{
					if( is_string($address ) )//skip non-string entries
						array_push( $address_list, $address );				
				}
			}

			//if validation is enabled, check for proper formatting
			//and email validity
			if( $this->config['validate'] ) 
			{
				//check for proper
				foreach( $address_list as $address )
				{
					if( !$this->email_is_valid( $address ) )
					{
						$this->debug( 'A supplied email address wasn\'t formatted properly: '.$address );
						return NULL;
					}
				}
			}
			
			//compile the list into the correct string format
			$output = '';
			
			for( $i=0; $i<count($address_list); $i++ )
			{
				$output .= $address_list[$i];
				
				if( $i < count($address_list)-1 )
					$output .= ', ';
			}
			
			return $output;
		}
		
		/*
		* Email is Valid
		*
		* Checks the email ensures it conforms to one of the following formats:
		* example@domain.com
		* Foo Bar <example@domain.com>
		*
		* Arguments
		* $address - str - The target email address
		* $ignore_format - bool - If only checking the validity of the email address is required
		*/
		private function email_is_valid( $address = '', $ignore_format = false )
		{
			if( !strlen( $address ) )
				return FALSE;
				
			//verify there is a valid email address in the string
			if( preg_match( '%[a-z0-9._-]+@[a-z0-9_-]+\.[a-z.]+%is', $address ) <= 0)
				return FALSE;
			
			//if ANY invalid email characters are found, assume it's the name encapsulated variant of the standard
			if( !$ignore_format )
			{
				if( preg_match( '%[^a-z0-9@._-]%is', $address ) > 0 )
				{
					//ensure it matches the format of 
					if( preg_match( '%.*\s<[a-z0-9@.]+>%is', $address ) <= 0 )
						return FALSE;
				}
			}
			
			return TRUE;
		}
		
		/*
		* Wrap Message
		*
		* Some emails may need to be line wrapped
		* in order to conform to email standards.
		* This function automates that process
		*
		* NB: Chunks encapsulated by {nowrap}{/nowrap} will be ignored
		*
		* Arguments:
		* $message - the message to parse
		* $html - whether the message is html formatted or not
		*/
		private function wrap_message( $message = '', $wraplength = 75,  $html = false )
		{
			$new_message = '';
			
			if( $html )
				$newline = "<br/>\n";
			else
				$newline = "\n";
			
			//split the array up to isolate the nowrap bits
			$chunks = preg_split( '%(\{nowrap\}[^{]+?\{/nowrap\})%is', $message, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
			
			foreach( $chunks as $chunk )
			{
				//a nowrap chunk was found
				if( strpos( $chunk, '{nowrap}' ) !== FALSE )
				{
					$chunk = str_replace( array( '{nowrap}', '{/nowrap}'), '', $chunk );
					$new_message .= $chunk.$newline;
					continue;
				}
				
				//append the wordwrapped chunk to the final string
				$new_message .= wordwrap($chunk, $wraplength, $newline );
			}
			
			return $new_message;
		}
		
		/*
		* Generate Alt Message
		*
		* Convert an HTML block to normal text by extracting
		* all of the HTML tags and tweaking the line breaks
		*/
		function generate_alt_message( $message = '' )
		{
			if( !$message )
				return NULL;
				
			//fix linebreaks
			//replace <br/> with \n
			$message = preg_replace( '%<br\s?/>([^\n\s])%im', "\n$1", $message );
			//replace <p> and </p>
			$message = preg_replace( '%([^\n\s])<p[^>]+?>%im', "$1\n\n", $message );
			$message = preg_replace( '%</p[^>\s]+?>([^\n])%im', "\n\n$1", $message );
			
			//strip all HTML tags
			$message = preg_replace( '%<[^>]+?>%is', '', $message );
			
			return $message;
		}
		
		/*
		* Send
		*
		* Compiles all of the supplied data into an email
		* entry and sends it off
		*/
		function send()
		{
			if( !$this->to )
				return FALSE;
			
			//If no alt_message was supplied, generate one
			if( $this->config['mailtype'] == 'html' && !$this->alt_message )
				$this->alt_message = $this->generate_alt_message( $this->message );	
			
			//if no reply-to was set, use the 'from' field
			if( !$this->reply_to )
				$this->reply_to = $this->from;
			
			//generate a random boundary
			$boundary = '------phpmailer------'.md5(time());
			
			$n = $this->config['newline'];
			
			//setup the header
			$headers = '';
			
			//apply useragent
			$headers .= 'X-Mailer: '.$this->config['useragent'].$n;
			
			//From field
			if( $this->from ) 
				$headers .= 'From: '.$this->from.$n;
			
			if( $this->cc )
				$headers .= 'Cc: '.$this->cc.$n;
				
			if( $this->bcc )
				$headers .= 'Bcc: '.$this->bcc.$n;
			
			//Reply-To field
			if( $this->reply_to )
			{
				$headers .= 'Reply-To: '.$this->reply_to.$n;
				$headers .= 'Return-Path: '.$this->reply_to.$n;
			}
			
			$headers .= 'MIME-Version: 1.0'.$n;
			$headers .= 'Content-Type: multipart/alternative; boundary = "'.$boundary.'"'.$n;
			
			$message = '';
			
			//set up the messages
			if( $this->config['mailtype'] == 'html' )
			{
				$message .= $n.'--'.$boundary.$n;
				$message .= 'Content-type: text/plain; charset='.$this->config['charset'].$n;
				$message .= 'Content-Transfer-Encoding: 8bit'.$n.$n;
				$message .=  $this->alt_message.$n;				
				
				$message .= $n.'--'.$boundary.$n;
				$message .= 'Content-type: text/html; charset='.$this->config['charset'].$n;
				$message .= 'Content-Transfer-Encoding: 8bit'.$n.$n;
				$message .= $this->message.$n;
			}
			else
			{
				$message .= $n.'--'.$boundary.$n;
				$message .= 'Content-type: text/plain; charset='.$this->config['charset'].$n;
				$message .= $this->message.$n;	
			}
			
			$message .= $n.'--'.$boundary.'--'.$n.$n;
			
			$this->debug( 'Headers: <br/>'.$headers );
			$this->debug( 'Message: <br/>'.$message );
		
			return mail( $this->to, $this->subject, $message, $headers );
		}
		
		/*
		* Clear
		*
		* Clear the class of all of the variable
		* data, ready for a totally fresh email
		*/
		function clear()
		{
			$this->to 		= '';
			$this->from 	= '';
			$this->reply_to	= '';
			$this->cc		= '';
			$this->bcc		= '';
			$this->subject 	= '';
			$this->message	= '';
			$this->debugger = '';
		}
		
		/*
		* Debug
		*
		* Append an error message to the debug log
		*/
		private function debug( $msg = '' )
		{
			$this->debugger .= $msg."<br/>\n";	
		}
		
		/*
		* Print Debugger
		*
		* Output the debugger to the browser for review
		*/
		function print_debugger()
		{
			$output = str_replace( "\n", "<br/>\n", $this->debugger );
			echo $output;	
		}
	}
?>