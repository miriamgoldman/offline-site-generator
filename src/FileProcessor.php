<?php
/*
	FileProcessor

	Takes a crawled file and processes it
*/

namespace OfflineSiteGenerator;


class FileProcessor {

	/**
	 * FileProcessor constructor
	 */
	public function __construct() {

	}

	/**
	 * Process StaticSite
	 *
	 * Iterates on each file, not directory
	 *
	 * @param string $filename File in StaticSite
	 */
	public function processFile( string $filename ) : void {
	
		$extractor = new SimpleRewriter( $filename );
		$urls      = $extractor->extract_and_update_urls( $filename );
	
	//	URL_Extractor::extract_and_update_urls( $filename );
	}

}
