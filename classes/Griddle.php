<?php

/**
 * Class Griddle
 *
 * Render one or more images with a grid, center lines, and percent lines for use in testing uploads, cropping and resizing of images.
 *
 * @author Modern Tribe, Inc.
 */
class Griddle {

	const MAX_ARRAY_LENGTH = 10;
	const MAX_IMAGE_PIXELS = 2000;

	protected $accepted_params = array(
		'sizes',
		'download',
		'width',
		'height',
		'grid1-size',
		'grid1-color',
		'grid2-size',
		'grid2-color',
		'centerlines',
		'src',
		'percent-lines',
		'percent-color',
		'font',
		'title',
	);

	public $errors = array();

	function __construct() {
	}

	/**
	 * List of parameters accepted by griddle
	 *
	 * @return array
	 */
	public function get_accepted_parameters() {
		return $this->accepted_params;
	}

	/**
	 * Process a single image
	 *
	 * @param int  $width
	 * @param int  $height
	 * @param bool $download
	 */
	public function process_single( $width, $height, $download = false ) {

		$file     = $this->get_image( $width, $height );
		$filename = $this->get_file_name( $file );

		if ( file_exists( $file ) ) {
			if ( $download ) {
				header( "Content-Disposition: Attachment;filename=$filename" );
			}
			header( "Content-type: image/png" );
			$fp = fopen( $file, 'rb' );
			fpassthru( $fp );
			exit;
		} else {
			$this->errors[] = "Image file does not exist.";
		}
	}

	/**
	 * Process an array of sizes
	 *
	 * @param array $sizes
	 * @param bool  $download
	 */
	public function process_multiple( $sizes, $download = false ) {

		if ( ! is_array( $sizes ) ) {
			$sizes = explode( "\n", $sizes );
		}

		if ( ! is_array( $sizes ) ) {
			$this->errors[] = "No sizes specified.";

			return;
		}

		if ( count( $sizes ) > self::MAX_ARRAY_LENGTH ) {
			$this->errors[] = sprintf( "Too many size pairs specified. Only %s allowed per request.", self::MAX_ARRAY_LENGTH );
			$sizes          = array_slice( $sizes, 0, self::MAX_ARRAY_LENGTH );
		}

		$zip_file = 'test_images_' . crc32( join( '|', $sizes ) ) . '.zip';
		$zip_path = 'cache/' . $zip_file;

		if ( ! file_exists( $zip_path ) ) {
			$zip = new ZipArchive;
			if ( $zip->open( $zip_path, ZipArchive::CREATE ) === true ) {
				foreach ( $sizes as $size_pair ) {
					if ( ! is_array( $size_pair ) ) {
						$size_pair = explode( ',', $size_pair );
					}
					$file     = $this->get_image( $size_pair[0], $size_pair[1] );
					$filename = $this->get_file_name( $file );
					$zip->addFile( $file, $filename );
				}
				$zip->close();
			}
		}

		if ( file_exists( $zip_path ) ) {
			if ( $download ) {
				header( "Content-Disposition: Attachment;filename=$zip_file" );
			}
			header( "Content-type: application/zip" );
			$fp = fopen( $zip_path, 'rb' );
			fpassthru( $fp );
			exit;
		} else {
			$this->errors[] = "Zip file does not exist.";
		}
	}

	/**
	 * Derive the file name from the path
	 *
	 * @param $path
	 *
	 * @return string
	 */
	private function get_file_name( $path ) {
		$file_info = pathinfo( $path );

		return $file_info['basename'];
	}

	/**
	 * Cache and return the image
	 *
	 * @param int  $width
	 * @param int  $height
	 * @param bool $download
	 *
	 * @return string
	 */
	private function get_image( $width, $height, $download = false ) {

		$width    = min( abs( $width ), self::MAX_IMAGE_PIXELS );
		$height   = min( abs( $height ), self::MAX_IMAGE_PIXELS );
		$filename = "{$width}x{$height}.png";

		$cachefile = "cache/$filename";

		// look for the file of that size in cache
		if ( ! file_exists( $cachefile ) ) {
			$this->render_image( $width, $height, $cachefile );
		}

		return $cachefile;

	}

	/**
	 * Render the image
	 *
	 * @param int    $width
	 * @param int    $height
	 * @param string $cachefile
	 */
	private function render_image( $width, $height, $cachefile ) {

		// Settings
		$grid1_size    = 10;
		$grid1_color   = array( 0, 0, 0, 124 );
		$grid2_size    = 100;
		$grid2_color   = array( 0, 0, 0, 124 );
		$percent_lines = array( 5, 10, 15, 20 );
		$percent_color = array( 255, 255, 255, 40 );
		$font          = 'fonts/OSP-DIN.ttf';

		// Colors
		$colors                 = array();
		$colors['background']   = array( 222, 222, 222, 0 );
		$colors['text']         = array( 255, 255, 255, 0 );
		$colors['grid1']        = $grid1_color;
		$colors['grid2']        = $grid2_color;
		$colors['percent']      = $percent_color;
		$colors['crosshair']    = array( 255, 255, 255, 50 );

		ini_set( 'memory_limit', '64M' );

		$img = imagecreatetruecolor( $width, $height );

		foreach ( $colors as $ck => $cv ) {
			if ( is_int( $cv ) ) {
				$colors[$ck] = imagecolorallocate( $img, $cv, $cv, $cv );
			} elseif ( count( $cv ) == 4 ) {
				$colors[$ck] = imagecolorallocatealpha( $img, $cv[0], $cv[1], $cv[2], $cv[3] );
			} elseif ( count( $cv ) == 3 ) {
				$colors[$ck] = imagecolorallocate( $img, $cv[0], $cv[1], $cv[2] );
			}
		}

		imagefill( $img, 0, 0, $colors['background'] );

		$this->image_grid( $img, $width, $height, $grid1_size, $colors['grid1'] );
		$this->image_grid( $img, $width, $height, $grid2_size, $colors['grid2'] );

		$this->image_percent( $img, $width, $height, $percent_lines, $colors['percent'], $font );

		$this->image_crosshair( $img, $width, $height, $colors['crosshair'] );

		$text     = $width . ' x ' . $height;
		$fontsize = $this->dynamic_font_size( $width );
		if ( $fontsize > 0 ) {
			$this->ttf_center( $img, $font, $text, $colors['text'], $fontsize );
		}

		imagepng( $img, $cachefile ); # store the image to cachefile
		imagedestroy( $img );
	}

	/**
	 * Figure out how big the font should be for the title
	 *
	 * @param int $width
	 *
	 * @return int
	 */
	private function dynamic_font_size( $width ) {
		if ( $width > 220 ) {
			$fontsize = 40;
		} elseif ( $width > 170 ) {
			$fontsize = 30;
		} elseif ( $width > 150 ) {
			$fontsize = 20;
		} elseif ( $width > 90 ) {
			$fontsize = 15;
		} else {
			$fontsize = 0;
		}

		return $fontsize;
	}

	/**
	 * Render centered crosshairs
	 *
	 * @param resource $img
	 * @param int      $width
	 * @param int      $height
	 * @param int      $color
	 * @param int      $color_bg
	 */
	private function image_crosshair( $img, $width, $height, $color ) {

		$center_w = round( $width / 2 );
		$center_h = round( $height / 2 );
		imageline( $img, $center_w, 0, $center_w, $height, $color );
		imageline( $img, 0, $center_h, $width, $center_h, $color );
	}

	/**
	 * Render percent lines
	 *
	 * @param resource $img
	 * @param int      $w
	 * @param int      $h
	 * @param array    $percent_lines
	 * @param int      $color
	 */
	private function image_percent( $img, $w, $h, $percent_lines, $color, $font ) {
		foreach ( $percent_lines as $percent ) {
			$p = $percent / 100;
			$x = $w * $p;
			$y = $h * $p;
			imageline( $img, $x, $y, $x, $h - $y, $color );
			imageline( $img, $w - $x, $y, $w - $x, $h - $y, $color );
			imageline( $img, $x, $y, $w - $x, $y, $color );
			imageline( $img, $x, $h - $y, $w - $x, $h - $y, $color );

			$size     = 10;
			$text     = $percent . '%';
			$ttf_size = imagettfbbox( $size, 0, $font, $text );
			$txtw     = abs( $ttf_size[2] - $ttf_size[0] );
			$txth     = abs( $ttf_size[1] - $ttf_size[7] );
			$txtx     = $x + 2;
			$txty     = $y + $txth;
			imagettftext( $img, $size, 0, $txtx, $txty, $color, $font, $text );
		}
	}

	/**
	 * Render a grid
	 *
	 * @param resource $img
	 * @param int      $w
	 * @param int      $h
	 * @param int      $grid_size
	 * @param int      $color
	 */
	private function image_grid( $img, $w, $h, $grid_size, $color ) {
		for ( $iw = 1; $iw < ( $w / $grid_size ); $iw ++ ) {
			imageline( $img, $iw * $grid_size, 0, $iw * $grid_size, $h, $color );
		}
		for ( $ih = 1; $ih < ( $h / $grid_size ); $ih ++ ) {
			imageline( $img, 0, $ih * $grid_size, $w, $ih * $grid_size, $color );
		}
	}

	/**
	 * Render centered text
	 *
	 * @param resource $img
	 * @param string   $font
	 * @param string   $text
	 * @param int      $color
	 * @param int      $size
	 */
	private function ttf_center( &$img, $font, $text, $color, $size ) {
		$ttf_size = imagettfbbox( $size, 0, $font, $text );
		$txtw     = abs( $ttf_size[2] - $ttf_size[0] );
		$txth     = abs( $ttf_size[1] - $ttf_size[7] );
		$x        = ( imagesx( $img ) - $txtw ) / 2;
		$y        = ( imagesy( $img ) + $txth ) / 2;
		imagettftext( $img, $size, 0, $x, $y, $color, $font, $text );
	}

}

?>