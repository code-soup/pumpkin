<?php

/**
 * Get paged value
 */
if ( ! function_exists('get_page_num')) :

	function get_page_num() {

		if ( get_query_var('paged') ) {
			$paged = intval(get_query_var('paged'));
		} else if ( get_query_var('page') ) {
			$paged = intval(get_query_var('page'));
		} else {
			$paged = 1;
		}

		return $paged;
	}
endif;


/**
 * Get value from array key
 */
if ( ! function_exists('get_key')) :

	function get_key($k, $a = false) {

		global $widget, $cs;
		$val = false;


		/**
		 * Falback for old way of getting things
		 */
		if ( is_array($k) || is_object($k) ) :

			$key   = $a;
			$array = $k;
		else:

			$key   = $k;
			$array = $a;
		endif;


		/**
		 * Get value from froots/widget variable
		 */
		if ( ! $array ) :

			if ( is_array($widget) ) {
				$array = $widget;
			}
			elseif ( is_array($cs)) {
				$array = $cs;
			}
		endif;


		if (!is_array($array) && !is_object($array))
			return false;

		if (is_array($array) && array_key_exists($key, $array))
			$val = $array[$key];

		if (is_object($array) && property_exists($array, $key))
			$val = $array->$key;

		return is_string($val) ? trim($val) : $val;
	}
endif;




/**
 * Get value from array/objec property and echo along with tag and class
 */
if ( ! function_exists('the_key') ) :

	function the_key( $key, $tag = false, $class = false, $array = false ) {

		if ( is_array($array) || is_object($array) ) {
			$val = get_key($key, $array);
		}
		else {
			$val = get_key($key);
		}

		if ( ! $val )
			return;


		if ( ! $tag ) {

			echo $val;
			return;
		}

		$open = '<' . $tag;

		if ( $class ) {
			$open .= ' class="'. $class .'"';
		}

		$open .= '>' . $val;
		$open .=  '</' . $tag . '>';

		echo $open;
	}
endif;


/**
 * Strip all but numbers
 */
if ( ! function_exists('get_tel')) :

	function get_tel( $tel ) {
		return preg_replace("/[^0-9]/","",$tel);
	}
endif;



/**
 * Trim text to X words
 */
if ( ! function_exists('vb_trim_word')) :

	function cs_trim_word( $text, $length ) {
		$trimmed = wp_trim_words( $text, $num_words = $length, $more = null );
		return $trimmed;
	}
endif;



/**
 * Trim text to X chars
 */
if ( ! function_exists('vb_trim_chars')) :

	function cs_trim_chars( $text, $length = 45, $append = '&hellip;' ) {

		$length = (int) $length;
		$text   = trim( strip_tags( $text ) );

		if ( strlen( $text ) > $length ) {
			$text  = substr( $text, 0, $length + 1 );
			$words = preg_split( "/[\s]|&nbsp;/", $text, -1, PREG_SPLIT_NO_EMPTY );
			preg_match( "/[\s]|&nbsp;/", $text, $lastchar, 0, $length );

			if ( empty( $lastchar ) )
				array_pop( $words );

			$text = implode( ' ', $words ) . $append;
		}

		return $text;
	}
endif;


/**
 * Debug
 */
if ( ! function_exists('debug_wpmail')) :

	function debug_wpmail( $re ) {

		if ( ! $re ) {

			global $ts_mail_errors;
			global $phpmailer;

			if ( ! isset($ts_mail_errors) )
				$ts_mail_errors = array();

			if ( isset($phpmailer) ) {
				$ts_mail_errors[] = $phpmailer->ErrorInfo;
			}

			print_r('<pre>');
			print_r($ts_mail_errors);
			print_r('</pre>');
		}
	}

endif;



function get_acf_img( $array, $size = 'full', $nowh = false ) {

	if ( ! is_array($array)) {
		return;
	}

	/**
	 * Attributes
	 */
	if ( $size == 'full' ) :

		$w = $array['width'];
		$h = $array['height'];
		$s = $array['url'];
	else :

		$w = $array['sizes'][$size . '-width'];
		$h = $array['sizes'][$size . '-height'];
		$s = $array['sizes'][$size];
	endif;

	if ( $w < 1 ) {
		$w = '100';
	}

	if ( $h < 1 ) {
		$h = '100';
	}

	// printaj($array);

	if ( $nowh ) :
		echo '<img src="'. $s .'" alt="'. $array['alt'] .'">';
	else :
		echo '<img src="'. $s .'" width="'. $w .'" height="'. $h .'" alt="'. $array['alt'] .'">';
	endif;
}


if ( ! function_exists('printaj')) :

	function printaj( $var, $return = false ) {
		print_r('<pre>');
		print_r($var, $return);
		print_r('</pre>');
	}

endif;


if ( ! function_exists('dumpaj')) :

	function dumpaj( $var, $return = false ) {
		var_dump('<pre>');
		var_dump($var, $return);
		var_dump('</pre>');
	}
endif;



if ( ! function_exists('svg_icon')) :
	function svg_icon( $icon = false, $return = false, $original = false ) {

		if ( ! $icon ) {
			$icon = get_key('select_icon');
		}

		if ( is_array($icon) ) {
			$icon = $icon['select_icon'];
		}

		if ( strpos($icon, '.png') !== false ) :

			$re  = '<span class="png-icon ' . sanitize_title( $icon ) . '">';
			$re .= '<img src="' . get_stylesheet_directory_uri() . '/dist/icons/'. $icon .'" alt="'. preg_replace('/\\.[^.\\s]{3,4}$/', '', $icon) .'" />';
			$re .= '</span>';

		elseif ( $original ) :

			$src = get_stylesheet_directory_uri() . '/dist/icons/'. $icon .'.svg';
			$replace = 'Layer' . rand();

			$re  = '<span class="svg-icon ' . sanitize_title( $icon ) . '">';
			$re .= str_replace('id="Layer_1"', $replace, file_get_contents($src));
			$re .= '</span>';

		else :

			$re  = '<span class="svg-icon ' . sanitize_title( $icon ) . '">';
			$re .= '<svg class="svg-'. $icon .'">';
			$re .= '<use xlink:href="'.get_stylesheet_directory_uri().'/dist/sprite/spritemap.svg#sprite-'. $icon .'"></use>';
			$re .= '</svg>';
			$re .= '</span>';

		endif;

		if ( $return )
			return $re;

		echo $re;
	}
endif;



if ( ! function_exists( 'get_acf_oembed' ) ) {
	/**
	 * Include ACF Youtube oembed
	 */
	function get_acf_oembed( $field ) {

		if ( empty( $field ) ) {
			return;
		}

		preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $field, $match );

		if ( empty( $match[1] ) ) {
			return $field;
		}

		return sprintf(
			'<iframe width="640" height="360"
				src="https://www.youtube-nocookie.com/embed/%s?feature=oembed"
				frameborder="0"
				allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
				allowfullscreen>
			</iframe>',
			$match[1]
		);
	}
}
