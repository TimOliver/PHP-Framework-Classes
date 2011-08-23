<?php
/*
* Downloader Class
* By Timothy 'Tim' Oliver
*
* Streamlines and simplifies
* downloading of files to the user's
* computer. Download managers can
* also perform resume downloads with it.
*
* Based on code and logic from:
* http://w-shadow.com/blog/2007/08/12/how-to-force-file-download-with-php/
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

class Downloader
{
	//name/route of file to download
	private $filename = '';
	
	//filename to download the file under
	private $dl_filename = '';
	
	//the mimetype of the file
	private $mimetype = '';
	
	//length of the file
	private $file_size = 0;
	
	//disallow multi-threaded downloading
	private $force_single = false;
	
	//in multi-threaded downloading, the offset to start at
	private $mt_range = 0; 
	
	/*
	* Class Constructor
	*/
	function __construct( $dl_filename = '', $mimetype = 'application/octet-stream', $force_single = false )
	{	
		//import members
		$this->force_single = $force_single;
		$this->dl_filename 	= $dl_filename;
		$this->mimetype 	= $mimetype;
	
		//if safe mode is enabled, raise a warning
		if( ini_get('safe_mode') )
			trigger_error( '<b>Downloader:</b> Will not be able to handle large files while safe mode is enabled.'. E_USER_WARNING );
	}

	/*
	* Prepare Headers
	*
	* Prepare the main output header strings for the download
	*/
	private function prepare_headers( $size = 0 )
	{
		// required for IE, otherwise Content-Disposition may be ignored
		if(ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');
		
		header('Content-Type: ' . $this->mimetype);
		header('Content-Disposition: attachment; filename="'.$this->dl_filename.'"');
		header("Content-Transfer-Encoding: binary");
		header('Accept-Ranges: bytes');
		
		/* The three lines below basically make the 
		download non-cacheable */
		header("Cache-control: private");
		header('Pragma: private');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		
		// multipart-download and download resuming support
		if( isset($_SERVER['HTTP_RANGE']) && !$this->force_single )
		{
			list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
			list($range) = explode(",",$range,2);
			list($range, $range_end) = explode("-", $range);
			
			$range = intval($range);
			
			if(!$range_end) 
				$range_end = $size-1;
			else
				$range_end = intval( $range_end );
		
			$new_length = $range_end - $range+1;
			header('HTTP/1.1 206 Partial Content');
			header('Content-Length: '.$new_length );
			header('Content-Range: bytes '.$range.'-'.$range_end.'/'.$size);
			
			//set the offset range
			$this->mt_range = $range;
		} 
		else 
		{
			$new_length = $size;
			header("Content-Length: ".$size);
		}
		
		return $new_length;			
	}

	/*
	* Download File
	*
	* Set up the headers and download the file to the 
	*/
	function download_file( $filename = '' )
	{
		//assert the file is valid
		if( !is_file( $filename ) )
			throw new Exception( 'Downloader: Could not find file \''.$filename.'\'' );
		
		//make sure it's read-able
		if( !is_readable( $filename ) )	
			throw new Exception( 'Downloader: File was unreadable \''.$filename.'\'' );	
	
		//set script execution time to 0 so the script
		//won't time out.
		set_time_limit(0);
	
		//get the size of the file
		$this->file_size = filesize( $filename );
		
		//set up the main headers
		//find out the number of bytes to write in this iteration
		$block_size = $this->prepare_headers( $this->file_size );
		
		/* output the file itself */
		$chunksize = 1*(1024*1024);
		$bytes_send = 0;
		
		if ($file = fopen($filename, 'r'))
		{
			if( isset( $_SERVER['HTTP_RANGE'] ) && !$this->force_single )
				fseek( $file, $this->mt_range );
			
			//write the data out to the browser
			while( !feof( $file ) && !connection_aborted() && $bytes_send < $block_size )
			{
				$buffer = fread( $file, $chunksize );
				echo $buffer;
				flush();
				$bytes_send += strlen( $buffer );
			}
			
			fclose($file);
		} 
		else 
		{
			throw new Exception( 'Downloader: Could not open file \''.$filename.'\'' );
		}
		
		//terminate script upon completion
		die();
	}
}
?>