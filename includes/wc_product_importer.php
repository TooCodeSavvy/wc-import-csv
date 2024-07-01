<?php
/*
Plugin Name: WC Importer
Description: WooCommerce Product Importer via WP-CLI
Version: 1.0
Author: dipesh.kakadiya
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class WC_Product_CLI_Importer extends WP_CLI_Command {

		/**
		 * Import products from a CSV file.
		 *
		 * ## OPTIONS
		 *
		 * [--csv=<csv-file>]
		 * : The CSV file to import.
		 *
		 * ## EXAMPLES
		 *
		 * wp wc-product product_import_from_csv --csv=/path/to/your/file.csv
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public function product_import_from_csv( $args, $assoc_args ) {
			if ( empty( $assoc_args['csv'] ) ) {
				WP_CLI::error( 'Please provide the --csv argument.' );
			}

			$csv_file = $assoc_args['csv'];

			if ( ! file_exists( $csv_file ) ) {
				WP_CLI::error( 'CSV file does not exist.' );
			}

			// Open CSV file
			$handle = fopen( $csv_file, 'r' );

			if ( false === $handle ) {
				WP_CLI::error( 'Unable to open CSV file.' );
			}

			$count = 0;
			$post_updated = 0;
			$columns = $fields = array();

			while ( ( $row = fgetcsv( $handle, 0, ',' ) ) !== false ) {
				$count++;

				if ( 1 == $count ) {
					$columns = $fields = $row;
					$fields = array_flip( $fields );
					continue;
				}

				// Construct command
				$command = 'wp wc product create';
				$command .= ' --name="' . esc_attr( $row[ $fields['post_title'] ] ) . '"';
				$command .= ' --slug="' . esc_attr( $row[ $fields['post_name'] ] ) . '"';
				$command .= ' --sku="' . esc_attr( $row[ $fields['sku'] ] ) . '"';
				$command .= ' --description="' . esc_attr( $row[ $fields['post_content'] ] ) . '"';
				$command .= ' --short_description="' . esc_attr( $row[ $fields['post_excerpt'] ] ) . '"';
				$command .= ' --status="' . esc_attr( $row[ $fields['post_status'] ] ) . '"';
				$command .= ' --regular_price="' . esc_attr( $row[ $fields['regular_price'] ] ) . '"';
				$command .= ' --sale_price="' . esc_attr( $row[ $fields['sale_price'] ] ) . '"';
				$command .= ' --weight="' . esc_attr( $row[ $fields['weight'] ] ) . '"';
				$command .= ' --length="' . esc_attr( $row[ $fields['length'] ] ) . '"';
				$command .= ' --width="' . esc_attr( $row[ $fields['width'] ] ) . '"';
				$command .= ' --height="' . esc_attr( $row[ $fields['height'] ] ) . '"';
				$command .= ' --categories="' . esc_attr( $row[ $fields['tax:product_cat'] ] ) . '"';
				$command .= ' --tags="' . esc_attr( $row[ $fields['tax:product_tag'] ] ) . '"';
				$command .= ' --catalog_visibility="' . esc_attr( $row[ $fields['tax:product_visibility'] ] ) . '"';
				$command .= ' --type="' . esc_attr( $row[ $fields['tax:product_type'] ] ) . '"';

				WP_CLI::runcommand( $command );

				$post_updated++;
			}

			fclose( $handle );

			WP_CLI::success( sprintf( 'Imported %d products.', $post_updated ) );
		}
	}

	WP_CLI::add_command( 'wc-product', 'WC_Product_CLI_Importer' );
}
