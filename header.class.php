<?php
	/*
	* PHP Header Class
	* By Timothy 'Tim' Oliver
	*
	* Streamlines and simplifies
	* Header output calls made by PHP
	*
	*/

	class Header
	{
		//Hold output until called manually
		private $buffer = true;
		
		//Array of header strings
		private $header_strings = NULL;
		
		/*
		* Class Constructor
		*
		* $buffer - bool - Hold header output until called manually
		*/
		function __construct( $buffer = true )
		{
			$this->buffer 			= $buffer;
			$this->header_strings 	= array();
		}

		/*
		* 403 Error
		*
		* Return a 403 Error (Access denied) and terminates PHP if needed
		*/
		static function _403( $die = true )
		{
			//post 403 header
			header('HTTP/1.1 403 Forbidden');
			
			//kill the script
			if( $die )
				die();
		}

		/*
		* 404 Error
		*
		* Return a 404 Error (Page not Found) and terminates PHP if needed
		*/
		static function _404( $die = true )
		{
			//post 404 header
			header('HTTP/1.0 404 Not Found');
			
			//kill the script
			if( $die )
				die();
		}
		
		/*
		* redirect
		*
		* Perform a 302 redirect to another page.
		*/ 
		static function redirect( $url = '', $delay = 0, $die = true )
		{
			$delay_str = '';
			if( $delay > 0 )
				$delay_str = 'Refresh: '.$delay.'; ';
			
			if( $url )
				header( $delay_str.'Location: '.$url );
				
			if( $die )
				die();
		}
		
		/*
		* 200 Response
		*
		* Returns a header response of 200 (All good)
		*/
		function _200()
		{
			$this->do( 'HTTP/1.1 200 OK' );
		}
		
		/*
		* 206 Partial Response
		*
		* Returns a 206 response (partial file download)
		*/
		function _206()
		{
			$this->do( 'HTTP/1.1 206 Partial Content' );
		}

		/*
		* Powered By
		*
		* Modify the output header to specify a new output source
		*/
		function powered_by( $source = '' )
		{
			$this->do( 'X-Powered-By: '.$source );
		}
		
		/*
		* Length
		*
		* Sets the length of the response
		*/
		function content_length( $len = 0 )
		{
			$this->do( 'Content-Length: '.$len );
		}
		
		/*
		* Content Range
		*
		* Specifies the byte range of the data supplied (for partial downloads)
		*/
		function content_range( $start = 0, $end = 0, $next = 0 )
		{
			
		}
		
		/*
		* Download Name
		*
		* Sets the stream as a downloadable file and 
		* sets a name
		*/ 
		function download_name( $name = '' )
		{
			//defaulting these fixes an SSL glitch in IE
			$this->do( 'Expires: 0' );
			$this->do( 'Cache-Control: private' );
			$this->do( 'Pragma: cache' );
			
			$this->do( 'Content-Disposition: attachment; filename="'.$name.'"' );
		}
		
		/*
		* MIME type
		*
		* Set the MIME type of the output
		*/
		function mime( $mime_type = 'application/octet-stream' )
		{
			$this->do( 'Content-Type: '.$mime_type );
		}
		
		/*
		* Expires
		*
		* Sets the expiry date of the cache data here
		*/
		function expires( $time = 0 )
		{
			$this->do( 'Pragma: public' );
			$this->do( 'Cache-Control: maxage='.($time-time()) );
			$this->do( 'Expires: '.gmdate( 'D, d M Y H:i:s', $time).' GMT' );
		}
		
		/*
		* No cache
		*
		* Disables caching for the current request
		*/
		function no_cache()
		{
			$this->do( 'Cache-Control: no-cache, must-revalidate' );
			$this->do( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
		}
		
		/*
		* Add
		*
		* If buffering, add each string to an array, else
		* post it to the browser
		*/
		private function add( $string = '' )
		{
			if( $this->buffer )
				$this->header_strings[] = $string;
			else
				header( $string );
		}
		
		/*
		* Post
		*
		* Post the headers to the request
		*/
		function post()
		{
			foreach( $this->header_strings as $string )
				header( $string );
		}
	}
?>