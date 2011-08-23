<?php
/*
* MIME Type PHP Class
* By Timothy 'TiM' Oliver
* 
* Static class that allows easy
* retrieval of the MIME types of
* most common web formats.
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

class MIME
{
	//True after the class has been initialized
	private static $class_init = false;
	
	//List of MIME types in an assoc array.
	private static $mime_types = NULL;
	
	/*
	* Private constructor means the class
	* can't be instantiated. Static class only.
	*/
	private function __construct() { }
	
	/*
	* Type from ext
	*
	* Get the MIME type from extension
	*/
	static function type( $ext = '' )
	{	
		//if no ext, return generic binary stream
		if( !$ext )
			return 'application/octet-stream';
	
		//Init class data if needed
		MIME::init();
		
		//strip leading period
		if( strpos( $ext, '.' ) === 0 )
			$ext = substr( $ext, 1 );
		
		//check exists
		if( isset( MIME::$mime_types[$ext] ) )
			return MIME::$mime_types[$ext];
		
		//return binary stream by default
		return 'application/octet-stream';
	}
	
	/*
	* Init
	*
	* Init the class data if first time
	*/
	static function init()
	{
		//return if already done
		if( MIME::$class_init )
			return;
			
		//populate the types list with data
		MIME::$mime_types = array (
		   'txt' 	=> 'text/plain',
		   'html' 	=> 'text/html',
		   'htm'	=> 'text/html',
		   'php' 	=> 'text/plain',
		   'css' 	=> 'text/css',
		   'js'		=> 'application/x-javascript',
		   'jpg' 	=> 'image/jpeg',
		   'jpeg' 	=> 'image/jpeg',
		   'gif' 	=> 'image/gif',
		   'png' 	=> 'image/png',
		   'bmp' 	=> 'image/bmp',
		   'tif' 	=> 'image/tiff',
		   'tiff'	=> 'image/tiff',
		   'doc' 	=> 'application/msword',
		   'docx'	=> 'application/msword',
		   'xls' 	=> 'application/excel',
		   'xlsx'	=> 'application/excel',
		   'ppt' 	=> 'application/powerpoint',
		   'pptx' 	=> 'application/powerpoint',
		   'pdf'	=> 'application/pdf',
		   'wmv' 	=> 'application/octet-stream',
		   'mpg' 	=> 'video/mpeg',
		   'mov' 	=> 'video/quicktime',
		   'mp4' 	=> 'video/quicktime',
		   'zip' 	=> 'application/zip',
		   'rar' 	=> 'application/x-rar-compressed',
		   'dmg' 	=> 'application/x-apple-diskimage',
		   'exe'	=> 'application/octet-stream'
		);
		
		//set init state to true
		MIME::$class_init = true;
	}
}
?>
