<?php
namespace OfflineSiteGenerator;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ppath = plugin_dir_path( dirname( __FILE__ ) );
require_once $ppath . 'includes/libraries/Phpuri.php';

/**
 * Simpler Static utility class
 */
class Util {

	/**
	 * Get the protocol used for the origin URL
	 *
	 * @return string|null http or https
	 */
	public static function origin_scheme() {
		$pattern = '/:\/\/.*/';
		return preg_replace( $pattern, '', self::origin_url() );
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
	 * Wrapper around site_url(). Returns the URL used for the WP installation.
	 */
	public static function wp_installation_url() : string {
		return site_url();
	}

	/**
	 * Echo the selected value for an option tag if the statement is true.
	 */
	public static function selected_if( bool $statement ) : void {
		echo ( $statement == true ? 'selected="selected"' : '' );
	}

	/**
	 * Echo the checked value for an input tag if the statement is true.
	 */
	public static function checked_if( bool $statement ) : void {
		echo ( $statement == true ? 'checked="checked"' : '' );
	}

	/**
	 * Truncate if a string exceeds a certain length (30 chars by default)
	 *
	 * @return string
	 */
	public static function truncate(
		string $string,
		int $length = 30,
		string $omission = '...'
	) : string {
		return ( strlen( $string ) > $length + 3 ) ?
			( substr( $string, 0, $length ) . $omission ) :
			$string;
	}

	/**
	 * Use trailingslashit unless the string is empty
	 */
	public static function trailingslashit_unless_blank( string $string ) : string {
		return $string === '' ? $string : trailingslashit( $string );
	}

	/**
	 * Dump an object to error_log
	 *
	 * @param mixed $object Object to dump to the error log
	 * @return void
	 */
	public static function error_log( $object = null ) {
		$contents = self::get_contents_from_object( $object );
        // phpcs:disable
        error_log( (string) $contents );
        // phpcs:enable
	}

	/**
	 * Delete the debug log
	 *
	 * @return void
	 */
	public static function delete_debug_log() {
		$debug_file = self::get_debug_log_filename();
		if ( file_exists( $debug_file ) ) {
			unlink( $debug_file );
		}
	}

	/**
	 * Save an object/string to the debug log
	 *
	 * @param mixed $object Object to save to the debug log
	 * @return void
	 */
	public static function debug_log( $object = null ) {
		$options = Options::instance();
		if ( $options->get( 'debugging_mode' ) !== '1' ) {
			return;
		}

		$debug_file = self::get_debug_log_filename();

		// add timestamp and newline
		$message = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ';

		$trace = debug_backtrace();
		if ( isset( $trace[0]['file'] ) ) {
			$file = basename( $trace[0]['file'] );
			if ( isset( $trace[0]['line'] ) ) {
				$file .= ':' . $trace[0]['line'];
			}
			$message .= '[' . $file . '] ';
		}

		$contents = self::get_contents_from_object( $object );

		// get message onto a single line
		$contents = preg_replace( "/\r|\n/", '', (string) $contents );

		$message .= $contents . "\n";

		// log the message to the debug file instead of the usual error_log location
        // phpcs:disable
        error_log( $message, 3, $debug_file );
        // phpcs:enable
	}

	/**
	 * Return the filename for the debug log
	 *
	 * @return string Filename for the debug log
	 */
	public static function get_debug_log_filename() {
		$upload_path_and_url = wp_upload_dir();
		$uploads_path        = trailingslashit( $upload_path_and_url['basedir'] );
		return $uploads_path . 'simplerstatic-debug.txt';
	}

	/**
	 * Get contents of an object as a string
	 *
	 * @param  mixed $object Object to get string for
	 * @return string|bool String containing the contents of the object
	 */
	protected static function get_contents_from_object( $object ) {
		if ( is_string( $object ) ) {
			return $object;
		}

		ob_start();
        // phpcs:disable
        var_dump( $object );
        // phpcs:enable
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Given a URL extracted from a page, return an absolute URL
	 *
	 * Takes a URL (e.g. /test) extracted from a page (e.g. http://foo.com/bar/) and
	 * returns an absolute URL (e.g. http://foo.com/bar/test). Absolute URLs are
	 * returned as-is. Exception: links beginning with a # (hash) are left as-is.
	 *
	 * A null value is returned in the event that the extracted_url is blank or it's
	 * unable to be parsed.
	 *
	 * @param  string $extracted_url   Relative or absolute URL extracted from page
	 * @param  string $page_url        URL of page
	 * @return string|null                   Absolute URL, or null
	 */
	public static function relative_to_absolute_url( $extracted_url, $page_url ) {

		$extracted_url = trim( $extracted_url );

		// we can't do anything with blank urls
		if ( $extracted_url === '' ) {
			return null;
		}

		// if we get a hash, e.g. href='#section-three', just return it as-is
		if ( strpos( $extracted_url, '#' ) === 0 ) {
			return $extracted_url;
		}

		// check for a protocol-less URL
		// (Note: there's a bug in PHP <= 5.4.7 where parsed URLs starting with //
		// are treated as a path. So we're doing this check upfront.)
		// http://php.net/manual/en/function.parse-url.php#example-4617
		if ( strpos( $extracted_url, '//' ) === 0 ) {

			// if this is a local URL, add the protocol to the URL
			if ( stripos( $extracted_url, '//' . self::origin_host() ) === 0 ) {
				$extracted_url = self::origin_scheme() . ':' . $extracted_url;
			}

			return $extracted_url;

		}

		$parsed_extracted_url = parse_url( $extracted_url );

		// parse_url can sometimes return false; bail if it does
		if ( $parsed_extracted_url === false ) {
			return null;
		}

		// if no path, check for an ending slash; if there isn't one, add one
		if ( ! isset( $parsed_extracted_url['path'] ) ) {
			$clean_url     = (string) self::remove_params_and_fragment( $extracted_url );
			$fragment      = substr( $extracted_url, strlen( $clean_url ) );
			$extracted_url = trailingslashit( $clean_url ) . $fragment;
		}

		if ( isset( $parsed_extracted_url['host'] ) ) {

			return $extracted_url;

		} elseif ( isset( $parsed_extracted_url['scheme'] ) ) {

			// examples of schemes without hosts: java:, data:
			return $extracted_url;

		} else { // no host on extracted page (might be relative url)
			$path = isset( $parsed_extracted_url['path'] ) ?
				$parsed_extracted_url['path'] :
				'';

			$query    = isset( $parsed_extracted_url['query'] ) ?
				'?' . $parsed_extracted_url['query'] :
				'';
			$fragment = isset( $parsed_extracted_url['fragment'] ) ?
				'#' . $parsed_extracted_url['fragment'] :
				'';

			// turn our relative url into an absolute url
			$extracted_url = \PhpUri::parse( $page_url )->join( $path . $query . $fragment );

			return $extracted_url;
		}
	}

/**
 * Create offline path.
 * 
 * Takes the extracted URL, and the target URL, and finds out how deep in the structure it is. 
 *
 * @param string $extracted_path The source URL.
 * @param string $page_path The target URL.
 * @return void
 */
	public static function create_offline_path( $extracted_path, $page_path ) {

		$target_arr = explode( '/', untrailingslashit( wp_make_link_relative( $extracted_path ) ) );
		$source_arr = explode( '/', untrailingslashit( wp_make_link_relative( $page_path ) ) );

		$levels     = sizeof( $source_arr );

        $segments = array();
        
		unset( $target_arr[0] );
		$target_arr = array_reverse( $target_arr );
		for ( $i = 0; $i < $levels - 2;$i++ ) {
			$target_arr[] = '..';
		}

		$target_arr = array_reverse( $target_arr );
		$new_path   = implode( '/', $target_arr );
		return $new_path;
	}

	/**
	 * Check if URL starts with same URL as WordPress installation
	 *
	 * Both http and https are assumed to be the same domain.
	 *
	 * @param  string $url URL to check
	 * @return boolean      true if URL is local, false otherwise
	 */
	public static function is_local_url( $url ) {
		return (
			stripos( (string) self::strip_protocol_from_url( $url ), self::origin_host() ) === 0
		);
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
	 * Converts a textarea into an array w/ each line being an entry in the array
	 *
	 * @param  string $textarea Textarea to convert
	 * @return mixed[]            Converted array
	 */
	public static function string_to_array( $textarea ) {
		// using preg_split to intelligently break at newlines
		// see: https://stackoverflow.com/q/1483497/1668057
		$lines = preg_split( "/\r\n|\n|\r/", $textarea );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		array_walk( $lines, 'trim' );
		$lines = array_filter( $lines );
		return $lines;
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
	 * Remove index.html/index.php from a URL
	 *
	 * @param  string $url URL to remove index file from
	 * @return string|null      URL sans index file
	 */
	public static function strip_index_filenames_from_url( $url ) {
		$pattern = '/index.(html?|php)$/';
		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Get the current datetime formatted as a string for entry into MySQL
	 *
	 * @return string|bool MySQL formatted datetime
	 */
	public static function formatted_datetime() {
		return gmdate( 'Y-m-d H:i:s' );
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

	/**
	 * Ensure there is a single trailing directory separator on the path
	 *
	 * @param string $path File path to add trailing directory separator to
	 */
	public static function add_trailing_directory_separator( $path ) : string {
		return self::remove_trailing_directory_separator( $path ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Remove all trailing directory separators
	 *
	 * @param string $path File path to remove trailing directory separators from
	 */
	public static function remove_trailing_directory_separator( $path ) : string {
		return rtrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Ensure there is a single leading directory separator on the path
	 *
	 * @param string $path File path to add leading directory separator to
	 */
	public static function add_leading_directory_separator( $path ) : string {
		return DIRECTORY_SEPARATOR . self::remove_leading_directory_separator( $path );
	}

	/**
	 * Remove all leading directory separators
	 *
	 * @param string $path File path to remove leading directory separators from
	 */
	public static function remove_leading_directory_separator( $path ) : string {
		return ltrim( $path, DIRECTORY_SEPARATOR );
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
	 * Add a message to the array of status messages for the job
	 *
	 * @param  mixed[] $messages  Array of messages to add the message to
	 * @param  string  $task_name Name of the task
	 * @param  string  $message   Message to display about the status of the job
	 * @return mixed[] messages
	 */
	public static function add_archive_status_message( $messages, $task_name, $message ) {
		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $task_name, $messages ) ) {
			$messages[ $task_name ] = array(
				'message'  => $message,
				'datetime' => self::formatted_datetime(),
			);
		} else { // otherwise just update the message
			$messages[ $task_name ]['message'] = $message;
		}

		return $messages;
	}

}
