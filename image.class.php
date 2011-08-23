<?php
	/*
	* PHP Image Class
	* by Timothy 'TiM' Oliver
	* 
	* A PHP class to handle
	* image manipulation using the 
	* GD Library.
	*/
	
	class Image
	{
		//GD Image Handle
		private $im = NULL;
		
		/*
		* Class Constructor
		*
		* Function logic is based on # of arguments:
		* 1 - URL path to the image to load
		* 2 - W+H of new GD canvas to create
		*/ 
		function __construct( /*..*/ )
		{
			$args = func_get_args();
			
			//1 arg - arg is URL to existing image
			if( func_num_args() == 1 )
				$this->load_image( $args[0] );
			else
				$this->new_image( $args[0], $args[1] );
		}
		
		/*
		* Load image
		*
		* Load an existing and set it up within
		* GD.
		*
		* Supports JPEG, GIF and PNG
		*/
		function load_image( $filename = '' )
		{
			if( !is_file( $filename ) )
				throw new Exception( 'Image Class: could not find image \''.$filename.'\'.' );
									
			$ext = $this->get_ext( $filename );
			
			switch( $ext )
			{
				case 'jpeg':
				case 'jpg':
					$this->im = imagecreatefromjpeg( $filename );
					break;
				case 'gif':
					$this->im = imagecreatefromgif( $filename );
					break;
				case 'png':
					$this->im = imagecreatefrompng( $filename );
					break;
				default:
					throw new Exception( 'Image Class: An unsupported file format was supplied \''. $ext . '\'.' );
			}
			
			return TRUE;
		}
		
		/*
		* New Image
		* Initialize a new blank image in GD
		*/
		function new_image( $w = 0, $h = 0 )
		{
			$this->im = imagecreatetruecolor( $w, $h );
			return TRUE;
		}
		
		/*
		* Resize
		* Resizes an image to a new set of dimensions.
		*/
		function resize( $w = 0, $h = 0 )
		{
			if( $w == 0 || $h == 0 )
				return FALSE;
			
			//get the size of the current image
			$oldsize = $this->size();
			
			//create a target canvas
			$new_im = imagecreatetruecolor ( $w, $h );
		
			//copy and resize image to new canvas
			imagecopyresampled( $new_im, $this->im, 0, 0, 0, 0, $w, $h, $oldsize->w, $oldsize->h );
			
			//delete the old image handle
			imagedestroy( $this->im );
			
			//set the new image as the current handle
			$this->im = $new_im;
		}

		/*
		* Crop
		* rops an image to a new set of dimensions.
		*/
		function crop( $x = 0, $y = 0, $w = 0, $h = 0 )
		{
			if( $w == 0 || $h == 0 )
				return FALSE;
			
			//create a target canvas
			$new_im = imagecreatetruecolor ( $w, $h );
		
			//copy and resize image to new canvas
			imagecopyresampled( $new_im, $this->im, 0, 0, $x, $y, $w, $h, $w, $h );
			
			//delete the old image handle
			imagedestroy( $this->im );
			
			//set the new image as the current handle
			$this->im = $new_im;
		}
		
		/*
		* Resize Axis
		* Resizes an image along a specific axis, whilst
		* maintaining the aspect-ratio of the other axis.
		*
		* $size - size in pixels to resize the image to.
		* $axis - axis the size is targetting (x or y)
		*/
		function resize_axis( $size = 0, $axis = 'x' )
		{
			if( $size == 0 )
				return FALSE;
			
			$old_size = $this->size();
			
			//Y Axis
			if( !strcasecmp( $axis, 'Y' ) )
			{
				$new_w = $old_size->w * ( $size / $old_size->h );
				$new_h = $size;
			}
			else //X axis
			{
				$new_w = $size;
				$new_h = $old_size->h * ( $size / $old_size->w );		
			}

			//resize the image
			$this->resize( $new_w, $new_h );
		}

		/*
		* Size
		* Gets the dimensions of the currently loaded image.
		*
		* $axis - 'W'/'H' returns that dimension. NULL returns an object with both
		*/
		function size( $axis = NULL )
		{
			if( !strcasecmp( $axis, 'W' ) )
				return imageSX( $this->im );
			elseif( !strcasecmp( $axis, 'H' ) )
				return imageSY( $this->im );
			else
				return (object)array( 'w' => imageSX( $this->im ), 'h' => imageSY( $this->im ) );
		}
		
		/*
		* Save
		* Save the resulting image as JPG, GIF or PNG either to file, or 
		* return to calling function.
		*
		* $format 		- Image format: jpg, gif, png
		* $filename 	- filname + route to save to. If NULL, data is returned from this function
		* $jpegquality 	- If JPEG format is chosen, this is the compression level to use (1-100)
		*/
		function save( $format = 'jpg', $filename = NULL, $jpegquality = 100 )
		{
			//if no filename, set up OB to cpature the output
			if( $filename == NULL )
			{
				$do_return = true;
				ob_start();
			}
			
			//save the image based on supplied format
			switch( $format )
			{
				case 'jpg':
				case 'jpeg':
					$result = imagejpeg( $this->im, $filename, $jpegquality );
					break;
				case 'gif':
					$result = imagegif( $this->im, $filename );
					break;
				case 'png':
					$result = imagepng( $this->im, $filename );
					break;
				default:
					if( $do_return ) { ob_end_clean(); }
					throw new Exception( 'Image Class: Invalid save format \''.$format.'\'' );
			}
			
			//return the image data as needed
			if( $do_return )
			{
				$data = ob_get_flush();
				return $data;
			}
			else
			{
				return $result;
			}
		}
		
		/*
		* Get File Extension
		* Return the file extension from a file name
		*/
		private function get_ext( $filename='' )
		{
			return substr( $filename, strrpos( $filename, '.' ) + 1);	
		}

		/*
		* Get File Name
		* From a file route, retrieve only the file name
		*/
		private function get_filename( $fileroute = '' )
		{
			return substr( $filename, strrpos( $filename, '/' ) + 1);	
		}
	}
?>