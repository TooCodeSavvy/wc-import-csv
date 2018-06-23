<?php

/*
Plugin Name: WC Importer
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: WC - Product Importer.
Version: 1.0
Author: dipesh.kakadiya
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;


class WC_Product_CLI_Importer extends WP_CLI_Command {

	/**
	 * Add 301 Redirect for old article from CSV.
	 *
	 * CSV
	 * ----
	 * redirect_from_url,redirect_to_url
	 * ----
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *      wp wc-product product_import_from_csv --url=example.com
	 *
	 * @param array $args Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 */
	public function product_import_from_csv( $args = array(), $assoc_args = array() ) {

		if ( empty( $assoc_args['csv'] ) ) {
			WP_CLI::error( __( "Please pass .csv file in command.", 'wc_importer' ) );
		} elseif ( ! file_exists( $assoc_args['csv'] ) ) {
			WP_CLI::error( __( "Given .csv file does not exists.", 'wc_importer' ) );
		}

		echo '<pre>';
		var_dump( time() );
		echo '</pre>';

		WP_CLI::log( __( "Stated...", 'wc_importer' ) );

		$csv_file = $assoc_args['csv'];

		$handle = fopen( $csv_file, 'r' );

		if ( false === $handle ) {
			WP_CLI::error( __( "Please pass a valid .csv file in command..", 'wc_importer' ) );
		}

		$count        = 0;
		$post_updated = 0;
		$columns = $fields = array();

		while ( ( $row = fgetcsv( $handle, 0, ',' ) ) !== false ) {
			$count++;

			if ( 1 == $count ){
				$columns = $fields = $row;
				$fields = array_flip( $fields );
				continue;
			}

			if ( 0 === ( $count % 100 ) ) {
				sleep( 2 );
			}

			//check post already existed
			$post = get_page_by_path( $row [ $fields['post_name'] ], OBJECT, 'product' );
			if ( empty( $post ) ) {

				/*$cats = explode( '|', $row [ $fields['tax:product_cat'] ] );
				$j_cats = array();
				foreach ( $cats as $cat ){
					$j_cats[] = array(
						'name' => $cat,
					);
				}

				--categories="'. json_encode( $j_cats ) .'"
			   --tags="'. $row [ $fields['tax:product_tag'] ] .'"
				*/

				$attributes = array();

				$data         = explode( '|', $row [ $fields['attribute_data:pa_design-set'] ] );
				$attributes[] = array(
					'name'      => 'design-set',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_design-set'] ] ),
				);

				$data         = explode( '|', $row [ $fields['attribute_data:pa_format'] ] );
				$attributes[] = array(
					'name'      => 'format',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_format'] ] ),
				);

				$data         = explode( '|', $row [ $fields['attribute_data:pa_language'] ] );
				$attributes[] = array(
					'name'      => 'language',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_language'] ] ),
				);

				$data         = explode( '|', $row [ $fields['attribute_data:pa_legacy-code-tweak'] ] );
				$attributes[] = array(
					'name'      => 'legacy-code-tweak',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_legacy-code-tweak'] ] ),
				);

				$data         = explode( '|', $row [ $fields['attribute_data:pa_product-set'] ] );
				$attributes[] = array(
					'name'      => 'product-set',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_product-set'] ] ),
				);

				$data         = explode( '|', $row [ $fields['attribute_data:pa_size'] ] );
				$attributes[] = array(
					'name'      => 'size',
					'position'  => $data[0],
					'visible'   => $data[1],
					'variation' => $data[2],
					'options'   => explode( '|', $row [ $fields['attribute:pa_size'] ] ),
				);

				foreach ( $row as $key => $value ) {
					$row[ $key ] = htmlspecialchars( $value );
				}


				if ( empty( $row [ $fields['tax:product_visibility'] ] ) ) {
					$row [ $fields['tax:product_visibility'] ] = 'visible';
				}

				if ( empty( $row [ $fields['tax:product_type'] ] ) ) {
					$row [ $fields['tax:product_type'] ] = 'simple';
				}

				if ( empty( $row [ $fields['tax_status'] ] ) ) {
					$row [ $fields['tax_status'] ] = 'none';
				}

				if ( empty( $row [ $fields['backorders'] ] ) ) {
					$row [ $fields['backorders'] ] = 'no';
				}

				if ( empty( $row [ $fields['stock_status'] ] ) ) {
					$row [ $fields['stock_status'] ] = 'instock';
				}

				/*$command = 'wp wc product create
				--name="'. $row [ $fields['post_title'] ] .'"
				--slug="'. $row [ $fields['post_name'] ] .'"
				--parent_id="'. (int)$row [ $fields['Parent'] ] .'"
				--description="'. $row [ $fields['post_content'] ] .'"
				--short_description="'. $row [ $fields['post_excerpt'] ] .'"
				--status="'. $row [ $fields['post_status'] ] .'"
				--menu_order="'. $row [ $fields['menu_order'] ] .'"
				--sku="'. $row [ $fields['sku'] ] .'"
				--downloadable="'. ( 'yes' === $row [ $fields['downloadable'] ] ? 'true' : 'false' ) .'"
				--stock_quantity="'. $row [ $fields['stock'] ] .'"
				--regular_price="'. $row [ $fields['regular_price'] ] .'"
				--sale_price="'. $row [ $fields['sale_price'] ] .'"
				--weight="'. $row [ $fields['weight'] ] .'"
				--dimensions="{ "length" : '. $row [ $fields['length'] ] .', "width": '. $row [ $fields['width'] ] .', "height":'. $row [ $fields['height'] ] .'  }"
				--tax_class="'. $row [ $fields['tax_class'] ] .'"
				--tax_class="'. $row [ $fields['tax_class'] ] .'"
				--in_stock="'. ( "instock" == $row [ $fields['stock_status'] ] ? 'true' : 'false' ) .'"
				--backorders="'. $row [ $fields['backorders'] ] .'"
				--manage_stock="'. ( 'yes' === $row [ $fields['manage_stock'] ] ? 'true' : 'false' ) .'"
				--tax_status="'. $row [ $fields['tax_status'] ] .'"
				--upsell_ids="'. (int) $row [ $fields['upsell_ids'] ] .'"
				--cross_sell_ids="'. (int)$row [ $fields['crosssell_ids'] ] .'"
				--featured="'. ( 'yes' === $row [ $fields['featured'] ] ? 'true' : 'false' ) .'"
				--date_on_sale_from="'. $row [ $fields['sale_price_dates_from'] ] .'"
				--date_on_sale_to="'. $row [ $fields['sale_price_dates_to'] ] .'"
				--download_limit="'. (int)$row [ $fields['download_limit'] ] .'"
				--download_expiry="'. (int)$row [ $fields['download_expiry'] ] .'"
				--external_url="'. $row [ $fields['product_url'] ] .'"
				--button_text="'. $row [ $fields['button_text'] ] .'"
				--meta_data="[ { "key":"_yoast_wpseo_focuskw", "value":'. $row [ $fields['meta:_yoast_wpseo_focuskw'] ] .' },
							   { "key":"_yoast_wpseo_metadesc", "value":'. $row [ $fields['meta:_yoast_wpseo_metadesc'] ] .'},
							   { "key":"_yoast_wpseo_metakeywords", "value":'. $row [ $fields['meta:_yoast_wpseo_metakeywords'] ] .'}
							   { "key":"total_sales", "value":'. $row [ $fields['meta:total_sales'] ] .'}
							   { "key":"wc_productdata_options", "value":'. $row [ $fields['meta:wc_productdata_options'] ] .'}
							   { "key":"attribute_pa_format", "value":'. $row [ $fields['meta:attribute_pa_format'] ] .'}
							   { "key":"attribute_pa_language", "value":'. $row [ $fields['meta:attribute_pa_language'] ] .'}
							   { "key":"attribute_pa_size", "value":'. $row [ $fields['meta:attribute_pa_size'] ] .'} ]"
			   --images="[ { "src": '. explode( '!', $row [ $fields['meta:_yoast_wpseo_focuskw'] ] )[0] .' } ]"
			   --downloads="'. json_encode( (object) $row [ $fields['downloads'] ] ) .'"
			   --type="'. $row [ $fields['tax:product_type'] ] .'"
			   --catalog_visibility="'. $row [ $fields['tax:product_visibility'] ] .'"
			   --shipping_class="'. $row [ $fields['tax:product_shipping_class'] ] .'"
			   --shipping_class="'. $row [ $fields['tax:product_shipping_class'] ] .'"
			   --attributes="'. json_encode( $attributes ) .'"';*/


				$command = '--allow-root --user=1 wc product create --name="' . $row [ $fields['post_title'] ] . '" --slug="' . $row [ $fields['post_name'] ] . '" --parent_id="' . (int) $row [ $fields['Parent'] ] . '" --description="' . $row [ $fields['post_content'] ] . '" --short_description="' . $row [ $fields['post_excerpt'] ] . '" --status="' . $row [ $fields['post_status'] ] . '" --menu_order="' . $row [ $fields['menu_order'] ] . '" --sku="' . $row [ $fields['sku'] ] . '" --downloadable="' . ( 'yes' === $row [ $fields['downloadable'] ] ? 'true' : 'false' ) . '" --stock_quantity="' . (int) $row [ $fields['stock'] ] . '" --regular_price="' . $row [ $fields['regular_price'] ] . '" --sale_price="' . $row [ $fields['sale_price'] ] . '" --weight="' . $row [ $fields['weight'] ] . '" --dimensions="{ "length" : ' . $row [ $fields['length'] ] . ', "width": ' . $row [ $fields['width'] ] . ', "height":' . $row [ $fields['height'] ] . '  }" --tax_class="' . $row [ $fields['tax_class'] ] . '" --tax_class="' . $row [ $fields['tax_class'] ] . '" --in_stock="' . ( "instock" == $row [ $fields['stock_status'] ] ? 'true' : 'false' ) . '" --backorders="' . $row [ $fields['backorders'] ] . '" --manage_stock="' . ( 'yes' === $row [ $fields['manage_stock'] ] ? 'true' : 'false' ) . '" --tax_status="' . $row [ $fields['tax_status'] ] . '" --upsell_ids="' . (int) $row [ $fields['upsell_ids'] ] . '" --cross_sell_ids="' . (int) $row [ $fields['crosssell_ids'] ] . '" --featured="' . ( 'yes' === $row [ $fields['featured'] ] ? 'true' : 'false' ) . '" --date_on_sale_from="' . $row [ $fields['sale_price_dates_from'] ] . '" --date_on_sale_to="' . $row [ $fields['sale_price_dates_to'] ] . '" --download_limit="' . (int) $row [ $fields['download_limit'] ] . '" --download_expiry="' . (int) $row [ $fields['download_expiry'] ] . '" --external_url="' . $row [ $fields['product_url'] ] . '" --button_text="' . $row [ $fields['button_text'] ] . '" --meta_data="[ { "key":"_yoast_wpseo_focuskw", "value":' . $row [ $fields['meta:_yoast_wpseo_focuskw'] ] . ' }, { "key":"_yoast_wpseo_metadesc", "value":' . $row [ $fields['meta:_yoast_wpseo_metadesc'] ] . '},{ "key":"_yoast_wpseo_metakeywords", "value":' . $row [ $fields['meta:_yoast_wpseo_metakeywords'] ] . '},{ "key":"total_sales", "value":' . $row [ $fields['meta:total_sales'] ] . '},{ "key":"wc_productdata_options", "value":' . $row [ $fields['meta:wc_productdata_options'] ] . '},{ "key":"attribute_pa_format", "value":' . $row [ $fields['meta:attribute_pa_format'] ] . '},{ "key":"attribute_pa_language", "value":' . $row [ $fields['meta:attribute_pa_language'] ] . '},{ "key":"attribute_pa_size", "value":' . $row [ $fields['meta:attribute_pa_size'] ] . '} ]" --images="[ { "src": ' . explode( '!', $row [ $fields['meta:_yoast_wpseo_focuskw'] ] )[0] . ' } ]" --downloads="' . json_encode( (object) $row [ $fields['downloads'] ] ) . '" --type="' . $row [ $fields['tax:product_type'] ] . '" --catalog_visibility="' . $row [ $fields['tax:product_visibility'] ] . '" --shipping_class="' . $row [ $fields['tax:product_shipping_class'] ] . '" --shipping_class="' . $row [ $fields['tax:product_shipping_class'] ] . '" --attributes="' . json_encode( $attributes ) . '"';

				//WP_CLI::log( $command  );

				WP_CLI::runcommand( $command );

				$post_updated++;
			}
		}

		WP_CLI::log( sprintf( __( 'Total %d Redirection done.', 'wc_importer' ), $post_updated ) );

		WP_CLI::success( __( "Completed...", 'wc_importer' ) );

		echo '<pre>';
		var_dump( time() );
		echo '</pre>';

	}
}
