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

		WP_CLI::log( __( "Started...", 'wc_importer' ) );

		// Open CSV file
		$handle = fopen( $csv_file, 'r' );

		if ( false === $handle ) {
			WP_CLI::error( 'Unable to open CSV file.' );
		}

		$count = 0;
		$post_updated = 0;
		$headers = [];

		while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== FALSE ) {
			$count++;

			if ( $count == 1 ) {
				$headers = $data;
				continue;
			}

			$row = array_combine( $headers, $data );

			$attributes = [];
			if ( ! empty( $row['Attribute 1 name'] ) && ! empty( $row['Attribute 1 value(s)'] ) ) {
				$attributes[] = array(
					'name'      => $row['Attribute 1 name'],
					'position'  => 0,
					'visible'   => $row['Attribute 1 visible'],
					'variation' => $row['Type'] == 'variable' ? '1' : '0',
					'options'   => explode(',', $row['Attribute 1 value(s)']),
				);
			}
			if ( ! empty( $row['Attribute 2 name'] ) && ! empty( $row['Attribute 2 value(s)'] ) ) {
				$attributes[] = array(
					'name'      => $row['Attribute 2 name'],
					'position'  => 1,
					'visible'   => $row['Attribute 2 visible'],
					'variation' => $row['Type'] == 'variable' ? '1' : '0',
					'options'   => explode(',', $row['Attribute 2 value(s)']),
				);
			}

			foreach ( $row as $key => $value ) {
				$row[$key] = htmlspecialchars( $value );
			}

			$command = 'wp wc product create';
			$command .= ' --name="' . esc_attr( $row['Name'] ) . '"';
			$command .= ' --type="' . esc_attr( $row['Type'] ) . '"';
			$command .= ' --sku="' . esc_attr( $row['SKU'] ) . '"';
			$command .= ' --regular_price="' . esc_attr( $row['Regular price'] ) . '"';
			$command .= ' --description="' . esc_attr( $row['Description'] ) . '"';
			$command .= ' --short_description="' . esc_attr( $row['Short description'] ) . '"';
			$command .= ' --status="' . ( isset( $row['Published'] ) && $row['Published'] == 1 ? 'publish' : 'draft' ) . '"';
			$command .= ' --featured="' . ( isset( $row['Is featured?'] ) && $row['Is featured?'] == 1 ? 'true' : 'false' ) . '"';
			$command .= ' --catalog_visibility="' . esc_attr( $row['Visibility in catalog'] ) . '"';
			$command .= ' --stock_quantity="' . esc_attr( $row['Stock'] ) . '"';
			$command .= ' --in_stock="' . ( isset( $row['In stock?'] ) && $row['In stock?'] == 1 ? 'true' : 'false' ) . '"';
			$command .= ' --backorders="' . esc_attr( $row['Backorders allowed?'] ) . '"';
			$command .= ' --tax_status="' . esc_attr( $row['Tax status'] ) . '"';
			$command .= ' --tax_class="' . esc_attr( $row['Tax class'] ) . '"';

			if ( ! empty( $row['Date sale price starts'] ) ) {
				$command .= ' --date_on_sale_from="' . esc_attr( $row['Date sale price starts'] ) . '"';
			}

			if ( ! empty( $row['Date sale price ends'] ) ) {
				$command .= ' --date_on_sale_to="' . esc_attr( $row['Date sale price ends'] ) . '"';
			}

			if ( ! empty( $row['External URL'] ) && $row['Type'] == 'external' ) {
				$command .= ' --product_url="' . esc_attr( $row['External URL'] ) . '"';
				$command .= ' --button_text="' . esc_attr( $row['Button text'] ) . '"';
			}

			if ( ! empty( $attributes ) ) {
				$command .= ' --attributes=\'' . json_encode( $attributes ) . '\'';
			}

			// Uncomment to log command for debugging
			WP_CLI::log( $command );

			// Execute the command
			WP_CLI::runcommand( $command );

			$post_updated++;
		}

		fclose( $handle );

		WP_CLI::success( sprintf( 'Imported %d products.', $post_updated ) );
	}
}

// Register the WP-CLI command
WP_CLI::add_command( 'wc-product', 'WC_Product_CLI_Importer' );
