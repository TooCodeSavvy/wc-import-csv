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
					'options'   => array_map('trim', explode(',', $row['Attribute 1 value(s)'])),
				);
			}
			if ( ! empty( $row['Attribute 2 name'] ) && ! empty( $row['Attribute 2 value(s)'] ) ) {
				$attributes[] = array(
					'name'      => $row['Attribute 2 name'],
					'position'  => 1,
					'visible'   => $row['Attribute 2 visible'],
					'variation' => $row['Type'] == 'variable' ? '1' : '0',
					'options'   => array_map('trim', explode(',', $row['Attribute 2 value(s)'])),
				);
			}

			foreach ( $row as $key => $value ) {
				$row[$key] = htmlspecialchars( $value );
			}

			$command = 'wc product create';
			$command .= ' --name="' . esc_attr( $row['Name'] ) . '"';
			$command .= ' --type="' . esc_attr( $row['Type'] ) . '"';
			$command .= ' --sku="' . esc_attr( $row['SKU'] ) . '"';
			$command .= ' --regular_price="' . esc_attr( $row['Regular price'] ) . '"';
			$command .= ' --sale_price="' . esc_attr( $row['Sale price'] ) . '"';
			$command .= ' --description="' . esc_attr( $row['Description'] ) . '"';
			$command .= ' --short_description="' . esc_attr( $row['Short description'] ) . '"';
			$command .= ' --status="' . ( isset( $row['Published'] ) && $row['Published'] == 1 ? 'publish' : 'draft' ) . '"';
			$command .= ' --featured="' . ( isset( $row['Is featured?'] ) && $row['Is featured?'] == 1 ? 'true' : 'false' ) . '"';
			$command .= ' --catalog_visibility="' . esc_attr( $row['Visibility in catalog'] ) . '"';
			$command .= ' --tax_status="' . esc_attr( $row['Tax status'] ) . '"';
			$command .= ' --tax_class="' . esc_attr( $row['Tax class'] ) . '"';
			$command .= ' --weight="' . esc_attr( $row['Weight (lbs)'] ) . '"';
			$command .= ' --length="' . esc_attr( $row['Length (in)'] ) . '"';
			$command .= ' --width="' . esc_attr( $row['Width (in)'] ) . '"';
			$command .= ' --height="' . esc_attr( $row['Height (in)'] ) . '"';
			$command .= ' --in_stock="' . ( isset( $row['In stock?'] ) && $row['In stock?'] == 1 ? 'true' : 'false' ) . '"';
			
			// Check and set categories
			if ( ! empty( $row['Categories'] ) ) {
				$categories = array_map( 'trim', explode( '>', $row['Categories'] ) );
				$category_ids = [];
				foreach ( $categories as $category ) {
					$category_term = get_term_by( 'name', $category, 'product_cat' );
					if ( $category_term ) {
						$category_ids[] = $category_term->term_id;
					} else {
						WP_CLI::warning( 'Category "' . $category . '" not found.' );
					}
				}
				if ( ! empty( $category_ids ) ) {
					$command .= ' --categories="' . implode( ',', $category_ids ) . '"';
				}
			}

			// Check and set tags
			if ( ! empty( $row['Tags'] ) ) {
				$tags = array_map( 'trim', explode( ',', $row['Tags'] ) );
				$tag_ids = [];
				foreach ( $tags as $tag ) {
					$tag_term = get_term_by( 'name', $tag, 'product_tag' );
					if ( $tag_term ) {
						$tag_ids[] = $tag_term->term_id;
					} else {
						WP_CLI::warning( 'Tag "' . $tag . '" not found.' );
					}
				}
				if ( ! empty( $tag_ids ) ) {
					$command .= ' --tags="' . implode( ',', $tag_ids ) . '"';
				}
			}

			// Check and set images
			if ( ! empty( $row['Images'] ) ) {
				$image_urls = array_map( 'trim', explode( ',', $row['Images'] ) );
				$image_ids = [];
				foreach ( $image_urls as $image_url ) {
					$image_id = wc_import_product_image( esc_url_raw( $image_url ), $row['Name'] );
					if ( $image_id ) {
						$image_ids[] = $image_id;
					} else {
						WP_CLI::warning( 'Failed to import image: ' . $image_url );
					}
				}
				if ( ! empty( $image_ids ) ) {
					$command .= ' --images="' . implode( ',', $image_ids ) . '"';
				}
			}

			// Check and set shipping class
			if ( ! empty( $row['Shipping class'] ) ) {
				$shipping_class_term = get_term_by( 'name', $row['Shipping class'], 'product_shipping_class' );
				if ( $shipping_class_term ) {
					$command .= ' --shipping_class="' . $shipping_class_term->term_id . '"';
				} else {
					WP_CLI::warning( 'Shipping class "' . $row['Shipping class'] . '" not found.' );
				}
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
