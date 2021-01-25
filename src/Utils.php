<?php

namespace OfflineSiteGenerator;

use Exception;

class Utils {
	/*
	 * Takes either an http or https URL and returns a // protocol-relative URL
	 *
	 * @param string $start timer start
	 * @param string $end timer end
	 * @return float time between start and finish
	 */
	public static function microtime_diff(
		string $start,
		string $end = null
	) : float {
		if ( ! $end ) {
			$end = microtime();
		}

		list( $start_usec, $start_sec ) = explode( ' ', $start );
		list( $end_usec, $end_sec )     = explode( ' ', $end );

		$diff_sec  = intval( $end_sec ) - intval( $start_sec );
		$diff_usec = floatval( $end_usec ) - floatval( $start_usec );

		return floatval( $diff_sec ) + $diff_usec;
	}

	/*
	 * Adjusts the max_execution_time ini option
	 *
	 */
	public static function set_max_execution_time() : void {
		if (
			! function_exists( 'set_time_limit' ) ||
			! function_exists( 'ini_get' )
		) {
			return;
		}

		$current_max_execution_time  = ini_get( 'max_execution_time' );
		$proposed_max_execution_time =
			( $current_max_execution_time == 30 ) ? 31 : 30;
		set_time_limit( $proposed_max_execution_time );
		$current_max_execution_time = ini_get( 'max_execution_time' );

		if ( $proposed_max_execution_time == $current_max_execution_time ) {
			set_time_limit( 0 );
		}
	}

	  /**
	   * Recursively create a path from one page to another
	   *
	   * Takes a path (e.g. /blog/foobar/) extracted from a page (e.g. /blog/page/3/)
	   * and returns a path to get to the extracted page from the current page
	   * (e.g. ./../../foobar/index.html). Since this is for offline use, the path
	   * return will include a /index.html if the extracted path doesn't contain
	   * an extension.
	   *
	   * The function recursively calls itself, cutting off sections of the page path
	   * until the base matches the extracted path or it runs out of parts to remove,
	   * then it builds out the path to the extracted page.
	   *
	   * @param  string $extracted_path Relative or absolute URL extracted from page
	   * @param  string $page_path      URL of page
	   * @param  int    $iterations     Number of times the page path has been chopped
	   * @return string|null                 Absolute URL, or null
	   */
	public static function create_offline_path( $extracted_path, $page_path, $iterations = 0 ) {
		// We're done if we get a match between the path of the page and the extracted URL
		// OR if there are no more slashes to remove
		if ( strpos( $page_path, '/' ) === false || strpos( $extracted_path, $page_path ) === 0 ) {
			$extracted_path = substr( $extracted_path, strlen( $page_path ) );
			$iterations     = ( $iterations == 0 ) ? 0 : $iterations - 1;
			$new_path       = '.' . str_repeat( '/..', $iterations ) .
				self::add_leading_slash( $extracted_path );
			return $new_path;
		} else {
			// match everything before the last slash
			$pattern = '/(.*)\/[^\/]*$/';
			// remove the last slash and anything after it
			$new_page_path = preg_replace( $pattern, '$1', (string) $page_path );
			return self::create_offline_path(
				$extracted_path,
				(string) $new_page_path,
				++$iterations
			);
		}
    }
    
    /**
     * Remove the //, http://, https:// protocols from a URL
     *
     * @param  string $url URL to remove protocol from
     * @return string|null      URL sans http/https protocol
     */
    public static function strip_protocol_from_url( $url ) {
        $pattern = '/^(https?:)?\/\//';
        return preg_replace( $pattern, '', $url );
    }

    /**
     * Add a slash to the beginning of a path
     *
     * @param string $path URL path to add leading slash to
     */
    public static function add_leading_slash( $path ) : string {
        return '/' . self::remove_leading_slash( $path );
    }

    /**
     * Remove a slash from the beginning of a path
     *
     * @param string $path URL path to remove leading slash from
     */
    public static function remove_leading_slash( $path ) : string {
        return ltrim( $path, '/' );
    }

	 /**
	  * Get the path from a local URL, removing the protocol and host
	  *
	  * @param  string $url URL to strip protocol/host from
	  * @return string       URL sans protocol/host
	  */
	public static function get_path_from_local_url( $url ) {
		$url = self::strip_protocol_from_url( $url );
		$url = str_replace( self::origin_host(), '', (string) $url );
		return $url;
	}

	/**
	 * Returns a URL w/o the query string or fragment (i.e. nothing after the path)
	 *
	 * @param  string $url URL to remove query string/fragment from
	 * @return string|null      URL without query string/fragment
	 */
	public static function remove_params_and_fragment( $url ) {
		return preg_replace( '/(\?|#).*/', '', $url );
    }
    
    /**
     * Get the host for the origin URL
     *
     * @return string host (URL minus the protocol)
     */
    public static function origin_host() {
        return untrailingslashit( (string) self::strip_protocol_from_url( self::origin_url() ) );
    }

    /**
     * Wrapper around home_url(). Useful for swapping out the URL during debugging.
     */
    public static function origin_url() : string {
        return home_url();
    }

	/**
	 * Similar to PHP's pathinfo(), but designed with URL paths in mind (instead of directories)
	 *
	 * Example:
	 *   $info = self::url_path_info( '/manual/en/function.pathinfo.php?test=true' );
	 *     $info['dirname']   === '/manual/en/'
	 *     $info['basename']  === 'function.pathinfo.php'
	 *     $info['extension'] === 'php'
	 *     $info['filename']  === 'function.pathinfo'
	 *
	 * @param  string $path The URL path
	 * @return mixed[]        Array containing info on the parts of the path
	 */
	public static function url_path_info( $path ) {
		$info = array(
			'dirname'   => '',
			'basename'  => '',
			'filename'  => '',
			'extension' => '',
		);

		$path = self::remove_params_and_fragment( $path );

		// everything after the last slash is the filename
		$last_slash_location = strrpos( (string) $path, '/' );
		if ( $last_slash_location === false ) {
			$info['basename'] = $path;
		} else {
			$info['dirname']  = substr( (string) $path, 0, $last_slash_location + 1 );
			$info['basename'] = substr( (string) $path, $last_slash_location + 1 );
		}

		// finding the dot for the extension
		$last_dot_location = strrpos( (string) $info['basename'], '.' );
		if ( $last_dot_location === false ) {
			$info['filename'] = $info['basename'];
		} else {
			$info['filename']  = substr( (string) $info['basename'], 0, $last_dot_location );
			$info['extension'] = substr( (string) $info['basename'], $last_dot_location + 1 );
		}

		// substr sets false if it fails, we're going to reset those values to ''
		foreach ( $info as $name => $value ) {
			if ( ! $value ) {
				$info[ $name ] = '';
			}
		}

		return $info;
	}

}
