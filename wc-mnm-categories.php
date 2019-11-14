<?php
/*
* Plugin Name: WooCommerce Mix and Match: Categories
* Plugin URI: https://woocommerce.com/products/woocommerce-mix-and-match-products/
* Description: Add products as mix and match options by product category.
* Version: 1.0.0.beta.1
* Author: Kathy Darling
* Author URI: http://kathyisawesome.com/
*
* Text Domain: wc-mnm-categories
* Domain Path: /languages/
*
* Requires at least: 4.9
* Tested up to: 5.1
*
* WC requires at least: 3.4
* WC tested up to: 3.4.5
*
* Copyright: © 2019 Kathy Darling
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_MNM_Categories {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $version = '1.0.0.beta.1';

	/**
	 * Min required MNM version.
	 *
	 * @var string
	 */
	public static $req_mnm_version = '1.6.0';

	/**
	 * Plugin URL.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename(__FILE__) );
	}

	/**
	 * Plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Fire in the hole!
	 */
	public static function init() {
		
		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Check dependencies.
		if ( ! function_exists( 'WC_Mix_and_Match' ) || version_compare( WC_Mix_and_Match()->version, self::$req_mnm_version ) < 0 ) {
			add_action( 'admin_notices', array( __CLASS__, 'version_notice' ) );
			return false;
		}

		/*
		 * Admin.
		 */
		
		// Admin sripts
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		// Add extra meta.
		add_action( 'woocommerce_mnm_product_options', array( __CLASS__, 'additional_container_option') , 15, 2 );
		add_action( 'woocommerce_mnm_product_options', array( __CLASS__, 'add_container_category_contents_option') , 15, 2 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_meta' ), 20 );

		/*
		 * Product.
		 */
		add_filter( 'woocommerce_product_get_contents', array( __CLASS__, 'get_category_contents' ), 10, 2 );

	}


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-mnm-categories' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Admin */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Load the product metabox script.
	 */
	public static function admin_scripts() {

		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// WooCommerce product admin page.
		if ( 'product' === $screen_id ) {

			wp_enqueue_script( 'wc_mnm_categories_writepanel', self::plugin_url() . '/assets/js/wc-mnm-categories-metabox.js', array( 'jquery', 'wc-enhanced-select' ), self::$version );

		}

	}

	/**
	 * Add a notice if versions not met.
	 */
	public static function version_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Mix & Match: Categories is inactive.</strong> The %sWooCommerce Mix and Match plugin%s must be active and at least version %s for Categories mini-extension to function. Please upgrade or activate WooCommerce Mix and Match.', 'woocommerce-mix-and-match-products' ), '<a href="https://woocommerce.com/products/woocommerce-mix-and-match-products/">', '</a>', $this->req_mnm_version ) . '</p></div>';
	}

	/**
	 * Adds the writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function additional_container_option( $post_id, $mnm_product_object ) {

		woocommerce_wp_radio( array(
			'id'            => 'mnm_use_category',
			'label'       => __( 'Use product category', 'wc-mnm-categories' ),
			'value'	=> $mnm_product_object->get_meta( '_mnm_use_categories' ) == 'yes' ? 'yes' : 'no',
			'options'	=> array( 'no' => __( 'Use default', 'wc-mnm-categories' ),
								  'yes' => __( 'Use Product Category for contents', 'wc-mnm-categories' ) )
		) );
	}

	/**
	 * Adds the writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function add_container_category_contents_option( $post_id, $mnm_product_object ) {
		
		echo '<p class="form-field mnm_product_cat_field">
		<label for="mnm_product_cat_field">' . __( 'Mix and Match product category', 'wc-mnm-categories' ) . '</label>';

		$args = array(
			'orderby'            => 'name',
			'order'              => 'DESC',
			'taxonomy'           => 'product_cat',
			'hide_if_empty'      => false,
			'id' 				 => 'mnm_product_cat',
			'name' 				 => 'mnm_product_cat',
			'selected'			 => $mnm_product_object->get_meta( '_mnm_product_cat' )
		);

		wp_dropdown_categories( $args );

		echo '</p>';
	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function process_meta( $product ) {
		if( isset( $_POST[ 'mnm_use_category' ] ) && $_POST[ 'mnm_use_category' ] == 'yes' ) {
			$product->update_meta_data( '_mnm_use_category', 'yes' );
		} else {
			$product->delete_meta_data( '_mnm_use_category' );
		}

		if( isset( $_POST[ 'mnm_product_cat' ] ) ) {
			$product->update_meta_data( '_mnm_product_cat', intval( $_POST[ 'mnm_product_cat' ] ) );
		} else {
			$product->delete_meta_data( '_mnm_product_cat' );
		}
	}


	/*-----------------------------------------------------------------------------------*/
	/* Front End Display */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Get the product IDs from a product category
	 *
	 * @param  array $contents an array of product IDs
	 * @param  obj WC_Product_Mix_and_Match
	 * @return array 
	 */
	public static function get_category_contents( $contents, $product ) {

		if( ! is_admin() && $product->get_meta( '_mnm_use_category' ) == 'yes' && $product->get_meta( '_mnm_product_cat' ) ) {

			$term = get_term_by( 'id', $product->get_meta( '_mnm_product_cat' ), 'product_cat' );

			if( ! is_wp_error( $term ) ) {

				$args = array( 'type' => 'simple', 'category' => array( $term->slug ), 'return' => 'ids' );
				$contents = wc_get_products( $args );

				// Currently contents array is ID => some data... so flip the results.
				$contents = array_flip( $contents );
			}

		}

		return $contents;

	}

}

add_action( 'plugins_loaded', array( 'WC_MNM_Categories', 'init' ) );