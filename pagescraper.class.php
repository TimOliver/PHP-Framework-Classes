<?php
/*
* HTML Page Scraper Class
* by Timothy 'TiM' Oliver
* 
* Abstraction class to streamline downloading
* web pages and then scraping data using regular
* expressions.
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

class PageScraper
{
	/**
	* Scrape URL
	*
	* Loads down a page of HTML, and runs it against a regex expression.
	* The results are retuned as an array, or as an object if matched names are provided.
	*
	* @param String $url 			The complete URL to the target page (http://www.example.com/)
	* @param String $regex			The regex pattern to test against
	* @param Array 	$match_names	An array of strings that will become the names of the object properties
	* @param Array 	$options		Any additional options for cURL
	*
	* NB: $match_names need to be in the same order as the parenthesised sections of the regex pattern
	* (eg. array( 'foo', 'bar' ) will become $object->foo, $object->bar, in that order)
	*/
	static function scrape_url( $url='', $regex='', $match_names = NULL, $options = NULL )
	{
		//create cURL class
		require_once( 'curl.class.php' );
		$curl = new cURL();
		
		//insert any additional options into cURL
		if( is_array( $options ) )
			$curl->options( $options );
		
		//grab the HTML data
		$html = $curl->get( $url );

		//scrape HTML
		return PageScraper::scrape_html( $html, $regex, $match_names );
	}
	
	/**
	* Scrape HTML
	*
	* Takes a block of HTML code and runs it against a regex expression.
	* The results are retuned as an array, or as an object if matched names are provided.
	*
	* @param String $html 			The block of HTML to parse
	* @param String $regex 			The regex expression to use
	* @param Array 	$match_names	An array of strings that will become the names of the object properties
	* 
	* NB: $match_names need to be in the same order as the parenthesised sections of the regex pattern
	* (eg. array( 'foo', 'bar' ) will become $object->foo, $object->bar, in that order)
	*/
	static function scrape_html( $html = '', $regex='', $match_names = NULL )
	{
		//run it through the regex match function
		if( preg_match_all( $regex, $html, $matches, PREG_SET_ORDER ) <= 0 )
			return NULL;
		
		//set up an array to hold each sequence of matches
		$output = array();
		
		//loop through each match and extract the parameters
		foreach( $matches as $match )
		{
			//remove the first cell (as it contains the full found string )
			array_shift( $match );
			
			//if match_names was supplied, attach each entry as an object.
			if( is_array( $match_names ) )
			{
				$entry = array();
				for( $i = 0; $i < count( $match ); $i++ )
				{
					//assign the value to an assoc array using match_names as the key
					$entry[$match_names[$i]] = $match[$i];
				}
				
				//convert entry to an object and attach it to output.
				$output[] = (object)$entry;
			}
			else //else, just push out the results
			{
				$output[] = $match;	
			}
		}
		
		//output the final data
		return $output;			
	}
}	

/*EOF*/