<?php
	/*
	* SVG Renderer Class
	* by Timothy 'TiM' Oliver
	* 
	* A class that abstracts and
	* simplifies the process of dynamically
	* drawing geometry in the Scalable Vector Graphics format.
	*/
	
	define( 'SVG_XML_HEADER', '<?xml version="1.0" standalone="no"?>' );
	define( 'SVG_NAMESPACE', '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' );
	
	//Hard-coded array cells for each element
	define( 'SVG_NAME', 'name' );			//name 		- the name of the SVG element (ie 'line', 'path')
	define( 'SVG_CONTENT', 'content' );		//content 	- element has contents (so it needs a value, and a closing tag)
	define( 'SVG_VALUE', 'value' );			//value		- the value of the contents of this element
	define( 'SVG_PARAMS', 'params' );		//params	- attributes for the element
	
	class SVGRenderer
	{
		//array of object shapes
		var $shapes = NULL;
		
		//array of CSS classes
		var $classes = NULL;
		
		//array of gradient objects
		var $gradients = NULL;
		
		//the current number of tab indents to print
		var $tabs = 0;
		
		/*
		* Class Constructor
		*
		* Initialize all of the variables
		* and prepare for data input
		*/
		function __construct()
		{
			$this->shapes 		= array();
			$this->classes 		= array();
			$this->gradients	= array();
		}
	
		/*
		* Add Class
		*
		* Define a class and supply attributes to it
		*/
		function css_class( $name = '', $attr = NULL )
		{
			if( !$name || !$attr )
				return FALSE;
			
			//push the class to the list
			$classes[$name] = (object)$attr;
		}
		
		/*
		* Define a linear gradient
		*
		*/
		function linear_gradient( $name='', $stops = NULL, $x1 = 0, $y1 = 0, $x2 = 100, $y2 = 0 )
		{	
			if( !$name || !$stops )
				return FALSE;
		
			$gradient = array();		
			$gradient[SVG_NAME] = 'linearGradient';
			
			//build the object and place in the array
			$gradient[SVG_PARAMS] = array();
			$gradient[SVG_PARAMS]['id'] 		= $name;
			$gradient[SVG_PARAMS]['stops'] 		= $stops;
			$gradient[SVG_PARAMS]['x1'] 		= $x1;
			$gradient[SVG_PARAMS]['y1'] 		= $y1;
			$gradient[SVG_PARAMS]['x1'] 		= $x2;
			$gradient[SVG_PARAMS]['y1'] 		= $y2;
			
			$this->gradients[] = $gradient;
		}
		
		/*
		* Define a radial gradient
		*
		*/
		function radial_gradient( $name='', $stops = NULL, $cx = 0, $cy = 0, $r = 50, $fx = 50, $fy = 50 )
		{	
			if( !$name || !$stops )
				return FALSE;
		
			$gradient = array();		
			$gradient[SVG_NAME] = 'radialGradient';
			
			//build the object and place in the array
			$gradient[SVG_PARAMS] = array();
			$gradient[SVG_PARAMS]['id'] 		= $name;
			$gradient[SVG_PARAMS]['stops'] 		= $stops;
			$gradient[SVG_PARAMS]['cx'] 		= $cx;
			$gradient[SVG_PARAMS]['cy'] 		= $cy;
			$gradient[SVG_PARAMS]['r'] 			= $r;
			$gradient[SVG_PARAMS]['fx'] 		= $fx;
			$gradient[SVG_PARAMS]['fy'] 		= $fy;
			
			$this->gradients[] = $gradient;		
		}		
		
		/*
		* Open SVG
		*
		* Opens a new nested SVG element
		*/
		function open_svg( $x=0, $y=0 )
		{
			$svg = array();
			$svg[SVG_NAME] = 'svg';
			$svg[SVG_CONTENT] = true;
			
			$svg[SVG_PARAMS] = array();
			$svg[SVG_PARAMS]['x'] = $x;
			$svg[SVG_PARAMS]['y'] = $y;
			
			$this->shapes[] = $svg;
		}

		/*
		* Close SVG
		*
		* Closes the latest nested SVG element
		*/
		function close_svg()
		{
			$svg = array();
			$svg[SVG_NAME] = '/svg';
			$svg[SVG_CONTENT] = true;
			
			$this->shapes[] = $svg;
		}
		
		/*
		* Open Group
		*
		* Open up a new group
		*/
		function open_group( $options=NULL )
		{
			$group = array();
			$group[SVG_NAME] = 'g';
			$group[SVG_CONTENT] = true;
			
			if( is_array($options) )
			{
				$group[SVG_PARAMS] = array();
				
				foreach( $options as $name => $value )
					$group[SVG_PARAMS][$name] = $value;
			}
			
			$this->shapes[] = $group;
		}
		
		/*
		* Close Group
		*
		* Closes an active group
		*/
		function close_group()
		{
			$group = array();
			$group[SVG_NAME] = '/g';
			$group[SVG_CONTENT] = true;
				
			$this->shapes[] = $group;
		}	
		
		/*
		* Rect
		*
		* Draws a Rectangle
		*/
		function rect( $x = 0, $y = 0, $w = 0, $h = 0, $options = NULL )
		{
			//move the function arguments to an array object
			$rect = array();
			$rect[SVG_NAME] = 'rect';
			
			$rect[SVG_PARAMS] = array();
			$rect[SVG_PARAMS]['x'] = $x;
			$rect[SVG_PARAMS]['y'] = $y;
			$rect[SVG_PARAMS]['width'] = $w;
			$rect[SVG_PARAMS]['height'] = $h;
			
			//add each option to the list
			if( is_array($options) )
			{
				foreach( $options as $name => $value )
					$rect[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] =  $rect;
		}
		
		/*
		* Circle
		*
		* Draws an SVG circle object
		*/
		function circle( $cx = 0, $cy = 0, $r = 0, $options = NULL )
		{
			//move the function arguments to an array object
			$circle = array();
			$circle[SVG_NAME] = 'circle';
			
			$circle[SVG_PARAMS] = array();
			$circle[SVG_PARAMS]['cx'] = $cx;
			$circle[SVG_PARAMS]['cy'] = $cy;
			$circle[SVG_PARAMS]['r'] = $r;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$circle[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] =  $circle;				
		}
		
		/*
		* Ellipse
		*
		* Draws an SVG ellipse object
		*/
		function ellipse( $cx = 0, $cy = 0, $rx = 0, $ry = 0, $options = NULL )
		{
			//move the function arguments to an array object
			$ellipse = array();
			$ellipse[SVG_NAME] = 'ellipse';
			
			$ellipse[SVG_PARAMS] = array();
			$ellipse[SVG_PARAMS]['cx'] = $cx;
			$ellipse[SVG_PARAMS]['cy'] = $cy;
			$ellipse[SVG_PARAMS]['rx'] = $rx;
			$ellipse[SVG_PARAMS]['ry'] = $ry;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$ellipse[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] =  $ellipse;				
		}		
		
		/*
		* Line
		*
		* Draws an SVG line object
		*/		
		function line( $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $options = NULL )
		{
			//move the function arguments to an array object
			$line = array();
			$line[SVG_NAME] = 'line';
			
			$line[SVG_PARAMS] = array();
			$line[SVG_PARAMS]['x1'] = $x1;
			$line[SVG_PARAMS]['y1'] = $y1;
			$line[SVG_PARAMS]['x2'] = $x2;
			$line[SVG_PARAMS]['y2'] = $y2;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$line[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] =  $line;				
		}
		
		/*
		* Polyline
		*
		* Draws an SVG polyline object
		*/			
		function polyline( $points = NULL, $options = NULL )
		{
			//move the function arguments to an array object
			$polyline = array();
			$polyline[SVG_NAME] = 'polyline';
			
			//spawn array for arguments
			$polyline[SVG_PARAMS] = array();
			
			//compile all of the points into a string
			$point_str = '';
			foreach( $points as $point )
				$point_str .= $point[0].','.$point[1].' ';
			
			//add point list to the arguments list
			$polyline[SVG_PARAMS]['points'] = $point_str;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$polyline[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] = $polyline;
		}

		/*
		* Polygon
		*
		* Draws an SVG polygon object
		*/			
		function polygon( $points = NULL, $options = NULL )
		{
			//move the function arguments to an array object
			$polygon = array();
			$polygon[SVG_NAME] = 'polygon';
			
			//spawn array as arguments
			$polygon[SVG_PARAMS] = array();
			
			//compile points into a string
			$point_str = '';
			foreach( $points as $point )
				$point_str .= $point[0].','.$point[1].' ';
				
			//add point list to the arguments list
			$polygon[SVG_PARAMS]['points'] = $point_str;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$polygon[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] = $polygon;
		}
		
		/*
		* Path
		*
		* Draws an SVG path object
		*/
		function path( $points = NULL, $options = NULL )
		{
			if( !$points )
				return FALSE;
				
			$path = array();
			$path[SVG_NAME] = 'path';
			
			$path[SVG_PARAMS] = array();
			
			if( is_string( $points ) )
				$path[SVG_PARAMS]['d'] = $points;
			else
				$path[SVG_PARAMS]['d'] = $points->get_points();
				
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$path[SVG_PARAMS][$name] = $value;
			}
			
			//push the object to the shapes array
			$this->shapes[] = $path;			
		}

		/*
		* Text
		*
		* Render a line of text
		*/
		function text( $x, $y, $textstr='', $options = NULL )
		{
			$text = array();
			$text[SVG_NAME] = 'text';
			$text[SVG_VALUE] = $textstr;
		
			$text[SVG_PARAMS]['x'] = $x;
			$text[SVG_PARAMS]['y'] = $y;
			
			//add each option to the list
			if( $options )
			{
				foreach( $options as $name => $value )
					$text[SVG_PARAMS][$name] = $value;
			}
			
			$this->shapes[] = $text;
		}

		/*
		* Render Shape
		*
		* Passed a shape object, render it as SVG
		*/
		function render_shape( $shape = NULL )
		{
			if( !$shape )
				return NULL;
			
			$output = '';
			$output .= '<'.$shape[SVG_NAME];
			
			foreach( $shape[SVG_PARAMS] as $name => $val )
			{
				//non-element attributes are prefixed with a _. Ignore them.
				if( strpos( $name, '_' ) === 0 )
					continue;
					
				$output .= ' '.$name.'="'.$val.'"';
			}
			
			//if element contains a value, render it here
			if( isset( $shape[SVG_VALUE] ) )
			{
				$output .= '>'."\n";
				$output .= str_repeat( "\t", $this->tabs+1 ).$shape[SVG_VALUE]."\n";
				$output .= str_repeat( "\t", $this->tabs ).'</'.$shape[SVG_NAME].'>';
			}
			else //if not, just close it
			{
				//some elements just need regular capping
				if( isset( $shape[SVG_CONTENT] ) )
					$output .= '>';
				else
					$output .= ' />';	
			}
			
			//remove a tab indent if this is a closing brace
			if( $shape[SVG_NAME] == '/g' || $shape[SVG_NAME] == '/svg' )
				$this->tabs--;			
			
			//format string with newlines/tabs
			//NB: must be done before new tabs are changed below
			$output = $this->_f($output);
			
			//if this tag is a new group, increment the tab indents
			if( $shape[SVG_NAME] == 'g' || $shape[SVG_NAME] == 'svg' )
				$this->tabs++;
				
			return $output;
		}
		
		
		/*
		* Format String
		*
		* A small function to help automate the formatting of each line of code
		* such as tab indents and newlines
		*/
		private function _f( $line = '', $newlines = 1 )
		{
			return str_repeat( "\t", $this->tabs ).$line.str_repeat( "\n", $newlines );	
		}
		
		/*
		* Draw
		*
		* Render the objects out to SVG and return them
		*/
		function draw( $w = 256, $h = 256 )
		{
			$output = '';
			$output .= $this->_f(SVG_XML_HEADER);
			$output .= $this->_f(SVG_NAMESPACE, 2);
			$output .= $this->_f('<svg width="'.$w.'" height="'.$h.'" version="1.1" xmlns="http://www.w3.org/2000/svg">');
			
			//increment the number of tab spaces
			$this->tabs++;
			
			if( count( $this->gradients ) || count( $this->classes ) )
			{
				$output .= $this->_f('<defs>');
				$this->tabs++;
				
				
				
				$this->tabs--;
				$output .= $this->_f('</defs>');
			}
			
			//render each element specified
			foreach( $this->shapes as $shape )
				$output .= $this->render_shape( $shape );
			
			//remove the number of tabs
			$this->tabs--;			
			
			$output .= $this->_f('</svg>');
			
			return $output;
		}
	}
	
	
	/*
	* SVG Path Points
	* 
	* A class to handle 
	* the different points
	* and parameters available in
	* the SVG path element
	*/
	class SVGPathPoints
	{
		//array containing all of the commands
		private $commands = NULL;
		
		/*
		* Move To
		*
		* Move the pen to a new point on the canvas
		*/
		function move_to( $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'm' : 'M';
			//build command
			$command = $name.$x.','.$y;
			//push to list
			$this->commands[] = $command;
		}
		
		/*
		* Line To
		*
		* Draw a line from the current point to the point specified here
		*/
		function line_to( $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'l' : 'L';
			//build command
			$command = $name.$x.','.$y;
			//push to list
			$this->commands[] = $command;			
		}

		/*
		*Horizontal Line To
		*
		* Draw a horizontal line (Y is locked to current value)
		*/
		function horizontal_line_to( $x, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'h' : 'H';
			//build command
			$command = $name.$x;
			//push to list
			$this->commands[] = $command;		
		}
		

		/*
		* Vertical Line To
		*
		* Draw a vertical line (X is locked to current value)
		*/
		function vertical_line_to( $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'v' : 'V';
			//build command
			$command = $name.$y;
			//push to list
			$this->commands[] = $command;			
		}
		
		/*
		* Curve To
		*
		* Draws a cubic bezier curve
		*/
		function curve_to( $x1, $y1, $x2, $y2, $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'c' : 'C';
			//build the command
			$command = $name.$x1.','.$y1.' '.$x2.','.$y2.' '.$x.','.$y;
			//push to list
			$this->commands[] = $command;
		}
		
		/*
		* Curve To
		*
		* Draws a cubic bezier curve. The control point for point 1 is
		* assumed to be the same as the previous curve
		*/
		function smooth_curve_to( $x1, $y1, $x2, $y2, $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'c' : 'C';
			//build the command
			$command = $name.$x1.','.$y1.' '.$x2.','.$y2.' '.$x.','.$y;
			//push to list
			$this->commands[] = $command;
		}
		
		/*
		* Quadratic Curve To
		*
		* Draws a Quadratic Bezier Curve
		*/
		function quadratic_curve_to( $x1, $y1, $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'q' : 'Q';
			//build the command
			$command = $name.$x1.','.$y1.' '.$x.','.$y;
			//push to list
			$this->commands[] = $command;				
		}
		
		/*
		* Quadratic Curve To
		*
		* Draws a Quadratic Bezier Curve. The control point for point 1 is
		* assumed to be the same as the previous curve
		*/
		function smooth_quadratic_curve_to( $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 't' : 'T';
			//build the command
			$command = $name.$x.','.$y;
			//push to list
			$this->commands[] = $command;				
		}	
		
		/*
		* Elliptical Arch
		*
		* Draws an elliptical arch.
		*/
		function elliptical_arch( $rx, $ry, $x_axis_rotation, $large_arch_flag, $sweep_flag, $x, $y, $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'a' : 'A';
			//build the command
			$command = $name.$rx.','.$ry.' '.$x_axis_rotation.' '.$large_arch_flag.','.$sweep_flag.' '.$x.','.$y;
			//push to list
			$this->commands[] = $command;	
		}
		
		/*
		* Elliptical Arch
		*
		* Draws an elliptical arch.
		*/
		function close_path( $relative = false )
		{
			//get relaative or not
			$name = $relative ? 'z' : 'Z';
			//build the command
			$command = $name;
			//push to list
			$this->commands[] = $command;	
		}		
		
		/*
		* Get Points
		*
		* Renders out all of the points
		* into the final parameter string.
		*/
		function get_points()
		{
			$output = '';
			
			foreach( $this->commands as $command )
				$output .= $command.' ';
				
			return $output;
		}
	}
?>