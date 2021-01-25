<?php

namespace OfflineSiteGenerator;

use Exception;

class URLHelper {
    /*
     * Returns the current full URL including querystring
     *
     * @return string
     */
    public static function getCurrent() : string {
        $scheme = $_SERVER['HTTPS'] === 'on' ? 'http' : 'https';
        $url = $scheme . '://' . $_SERVER['HTTP_HOST'];

        // Only include port number if needed
        if ( ! in_array( $_SERVER['SERVER_PORT'], [ 80, 443 ] ) ) {
            $url .= ':' . $_SERVER['SERVER_PORT'];
        }

        $url .= $_SERVER['REQUEST_URI'];

        return $url;
    }

    /**
     * Returns a URL with given querystring modifications
     *
     * @param array<string> $changes  List of querystring params to set
     * @param string $url             A complete URL. Leave empty to use current URL
     * @return string                 The new URL
     */
    public static function modifyUrl( array $changes, string $url = '' ) : string {
        // If $url wasn't passed in, use the current url
        if ( $url === '' ) {
            $url = self::getCurrent();
        }

        // Parse the url into pieces
        $url_array = (array) parse_url( $url );

        // The original URL had a query string, modify it.
        if ( array_key_exists( 'query', $url_array ) ) {
            parse_str( $url_array['query'], $query_array );
            foreach ( $changes as $key => $value ) {
                $query_array[ $key ] = $value;
            }
        } else {
            // The original URL didn't have a query string, add it.
            $query_array = $changes;
        }

        return $url_array['scheme'] . '://' .
            $url_array['host'] . $url_array['path'] . '?' .
            http_build_query( $query_array );
    }

    /*
     * Takes either an http or https URL and returns a // protocol-relative URL
     *
     * @param string URL either http or https
     * @return string URL protocol-relative
     */
    public static function getProtocolRelativeURL( string $url ) : string {
        $protocol_relative_url = str_replace(
            [
                'https:',
                'http:',
            ],
            [
                '',
                '',
            ],
            $url
        );

        return $protocol_relative_url;
    }

    public static function startsWithHash( string $url ) : bool {
        // TODO: this won't fire for absolute URLs unless strip site_url first?
        // quickly abort for invalid URLs
        if ( $url[0] === '#' ) {
            return true;
        }

        return false;
    }

    public static function isMailto( string $url ) : bool {
        if ( substr( $url, 0, 7 ) == 'mailto:' ) {
            return true;
        }

        return false;
    }

    public static function isProtocolRelative( string $url ) : bool {
        if ( $url[0] === '/' ) {
            if ( $url[1] === '/' ) {
                return true;
            }
        }

        return false;
    }

    public static function protocolRelativeToAbsoluteURL(
        string $url,
        string $site_url
    ) : string {

        $url = str_replace(
            self::getProtocolRelativeURL( $site_url ),
            $site_url,
            $url
        );

        return $url;
    }

    /*
     * Detect if a URL belongs to our WP site
     * We check against known internal prefixes and WP site host
     *
     */
    public static function isInternalLink(
        string $url,
        string $site_url_host
    ) : bool {
        // quickly match known internal links   ./   ../   /
        $first_char = $url[0];

        // TODO: // was false-positive for things like //fonts.google.com
        // add better detection for doc/site root relative protocol-rel URLs
        if ( $first_char === '.' ) {
            return true;
        }

        // site root relative URLs, like /alink
        if ( $url[0] === '/' ) {
            if ( $url[1] !== '/' ) {
                return true;
            }
        }

        $url_host = parse_url( $url, PHP_URL_HOST );

        if ( $url_host === $site_url_host ) {
            return true;
        }

        return false;
    }

    public static function convert_offline_url( $url ) {
        // remove the scheme/host from the url
        $page_path = Utils::get_path_from_local_url( $url );
        $extracted_path = Utils::get_path_from_local_url( $url );

        // create a path from one page to the other
        $path = Utils::create_offline_path( $extracted_path, $page_path );

        $path_info = Utils::url_path_info( $url );
        if ( $path_info['extension'] === '' ) {
            // If there's no extension, we need to add a /index.html,
            // and do so before any params or fragments.
            $clean_path = (string) Utils::remove_params_and_fragment( (string) $path );
            $fragment = substr( (string) $path, strlen( $clean_path ) );

            $path = trailingslashit( (string) $clean_path );
            $path .= 'index.html' . $fragment;
        }

        return (string) $path;
    }

}
