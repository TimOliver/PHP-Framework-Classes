<?php
/*
* Code Prettifier
* By Timothy 'TiM' Oliver
* 
* A simple class to easily allow refactoring
* of standard 3GL code to proper formatting standards
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

class CodePrettifier
{
	//configuration settings for class
	private $config = NULL;
	
	/*
	* Class Constructor
	*
	* $config - array - Array of settings to add to the class
	*/
	function __construct( $config = NULL )
	{
		//default values for settings
		$default_settings = array( 
								  	'indent_char' 	=> "\t",
									'newline'		=> "\n"
								);
		
		if( $config )
			$default_settings = array_merge( $default_settings, $config );
		
		//merge custom settings and store in class
		$this->config = (object)$default_settings;
	}
	
	/*
	* Refactor a block of code with proper indenting
	*
	*/
	function prettify( $code = '' )
	{
		if( !$code )
			return '';
		
		//number of times to indent
		$indent = 0;
		
		//break the code up into lines
		$code_lines = explode( $this->config->newline, $code );
		
		//init final output
		$output = '';
		
		//loop through each line
		foreach( $code_lines as $line )
		{
			//Special exception for 1 line conditions ( eg if (cond) { do; } )
			//Ignore indent increment/decrement
			if( strpos( $line, '{' ) !== FALSE && 
				strpos( $line, '}' ) !== FALSE && 
				strpos( $line, '{' ) < strpos( $line, '}' ) )
			{
				$output .= str_repeat( $this->config->indent_char, $indent ).$line.$this->config->newline;
				continue;
			}
			
			//remove an indent if there is a close bracket on this line
			if( strpos( $line, '}' ) !== FALSE )
				$indent--;
			
			//append the line to the output, re-add the newline, and the indents
			$output .= str_repeat( $this->config->indent_char, $indent ).$line.$this->config->newline;
			
			//add an indent if there is an open bracket on this line
			if( strpos( $line, '{' ) !== FALSE )
				$indent++;
		}
		
		//output final code
		return $output;
	}
}
?>