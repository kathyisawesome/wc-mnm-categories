<?php
/*
* Plugin Name: WooCommerce Mix and Match: Categories
* Plugin URI: https://woocommerce.com/products/woocommerce-mix-and-match-products/
* Description: Add products as mix and match options by product category.
* Version: 1.0.0-rc-2
* Author: Kathy Darling
* Author URI: http://kathyisawesome.com/
*
* Text Domain: wc-mnm-categories
* Domain Path: /languages/
*
* Requires at least: 5.1.0
* Tested up to: 5.3.0
*
* WC requires at least: 3.6.0
* WC tested up to: 3.8.0
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
	 */
	const VERSION = '1.0.0-rc-2';

	/**
	 * Min required MNM version.
	 *
	 * @var string
	 */
	const REQ_MNM_VERSION = '1.6.0';

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
		if ( ! function_exists( 'WC_Mix_and_Match' ) || version_compare( WC_Mix_and_Match()->version, self::REQ_MNM_VERSION ) < 0 ) {
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
		add_filter( 'wc_mnm_display_empty_container_error', array( __CLASS__, 'suppress_container_error' ), 10, 2 );

		/*
		 * Product.
		 */
		add_filter( 'woocommerce_product_get_contents', array( __CLASS__, 'get_category_contents' ), 10, 2 );
	
		/*
		 * Display.
		 */
		add_action( 'woocommerce_before_mnm_items', array( __CLASS__, 'first_category_caption' ), -10 );
		add_action( 'woocommerce_mnm_child_item_details', array( __CLASS__, 'change_category_caption' ), -10, 2 );
		
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

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : self::VERSION;

		// WooCommerce product admin page.
		if ( 'product' === $screen_id ) {

			wp_enqueue_script( 'wc_mnm_categories_writepanel', self::plugin_url() . '/assets/js/wc-mnm-categories-metabox'  . $suffix . '.js', array( 'jquery', 'wc-enhanced-select' ), $version );

		}

	}

	/**
	 * Add a notice if versions not met.
	 */
	public static function version_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Mix & Match: Categories is inactive.</strong> The %sWooCommerce Mix and Match plugin%s must be active and at least version %s for Categories mini-extension to function. Please upgrade or activate WooCommerce Mix and Match.', 'wc-mnm-categories' ), '<a href="https://woocommerce.com/products/woocommerce-mix-and-match-products/">', '</a>', self::REQ_MNM_VERSION ) . '</p></div>';
	}

	/**
	 * Adds the writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function additional_container_option( $post_id, $mnm_product_object ) {

		woocommerce_wp_radio( 
			array(
				'id'      => 'mnm_use_category',
				'class'   => 'select short mnm_use_category',
				'label'   => __( 'Use product category', 'wc-mnm-categories' ),
				'value'	  => $mnm_product_object->get_meta( '_mnm_use_category' ) === 'yes' ? 'yes' : 'no',
				'options' => array( 
					'no'  => __( 'Use default', 'wc-mnm-categories' ),
					'yes' => __( 'Use Product Categories for contents', 'wc-mnm-categories' )
				)
			)
		);

	}

	/**
	 * Adds the writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function add_container_category_contents_option( $post_id, $mnm_product_object ) { ?>

		<p  class="form-field mnm_product_cat_field">
			<label for="mnm_allowed_contents"><?php _e( 'Mix and Match product categories', 'wc-mnm-categories' ); ?></label>

			<?php

			// Generate some data for the select2 input.
			$selected_cats = self::get_categories( $mnm_product_object );

			?>

			<select id="mnm_product_cat" class="wc-category-search" name="mnm_product_cat[]" multiple="multiple" style="width: 400px;" data-sortable="sortable" data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'wc-mnm-categories' ); ?>" data-action="woocommerce_json_search_categories" data-allow_clear="true">
			<?php
				foreach ( $selected_cats as $slug ) {

					$current_cat      = get_term_by( 'slug', $slug, 'product_cat' );

					if ( $current_cat && ! is_wp_error( $current_cat ) ) {
						echo '<option value="' . esc_attr( $current_cat->slug ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $current_cat->name ) . '</option>';
					}
				}
			?>
			</select>
		</p>

		<?php
	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  WC_Product_Mix_and_Match  $product
	 */
	public static function process_meta( $product ) {
		if( isset( $_POST[ 'mnm_use_category' ] ) && $_POST[ 'mnm_use_category' ] === 'yes' ) {
			$product->update_meta_data( '_mnm_use_category', 'yes' );
		} else {
			$product->delete_meta_data( '_mnm_use_category' );
		}

		if( isset( $_POST[ 'mnm_product_cat' ] ) ) {

			$meta = array_map( 'sanitize_title',  $_POST[ 'mnm_product_cat' ] );

			$product->update_meta_data( '_mnm_product_cat', $meta );

		} elseif ( 'yes' === $product->get_meta( '_mnm_use_category' ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'Please select at least one category to use for this Mix and Match product.', 'wc-mnm-categories' ) );
		}
	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  bool $display_error
	 * @param  WC_Product_Mix_and_Match  $product
	 * @return  bool
	 */
	public static function suppress_container_error( $display_error, $product ) {
		if( isset( $_POST[ 'mnm_use_category' ] ) && $_POST[ 'mnm_use_category' ] === 'yes' ) {
			$display_error = false;
		}

		return $display_error;
	}



	/*-----------------------------------------------------------------------------------*/
	/* Front End Display */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Get the product IDs from a product category
	 *
	 * @param  array $contents an array of product IDs
	 * @param  obj $container_product WC_Product_Mix_and_Match
	 * @return array 
	 */
	public static function get_category_contents( $contents, $container_product ) {

		if( ! is_admin() && self::use_categories( $container_product ) ) {

			$categories = self::get_categories( $container_product );

			// Short circuit the contents as we'll get products instead later.
			if( self::use_multi_cat( $container_product ) ) {
				$contents = array();
				add_filter( 'woocommerce_mnm_get_children', array( __CLASS__, 'get_category_children' ), 10, 2 );

			} else if( count( $categories ) === 1 ) {

				$term = array_shift( $categories );

				if( is_int( $term ) ) {
					$term_obj = get_term_by( 'id', $term, 'product_cat' );
					if( $term_obj && ! is_wp_error( $term_obj ) ) {
						$term = $term_obj->slug;
					}
				}

				$cat_contents = self::get_cat_contents( $term );

				// Currently contents array is ID => some data... so flip the results.
				$contents = array_flip( $cat_contents );

			}

		}

		return $contents;

	}

	/**
	 * Get the product IDs from a product category
	 *
	 * @param  array $children an array of products
	 * @param  obj $container_product WC_Product_Mix_and_Match
	 * @return array 
	 */
	public static function get_category_children( $children, $container_product ) {

		if( ! is_admin() && self::use_categories( $container_product ) ) {

			$new_children = array();

			foreach( self::get_categories( $container_product ) as $cat_slug ) {

				//add_filter( 'woocommerce_mnm_get_child', array( __CLASS__, 'add_category_property' ), 10, 2 );

				$cat_contents = self::get_cat_contents( $cat_slug );

				foreach ( $cat_contents as $mnm_item_id ) {

					$child_product = $container_product->get_child( $mnm_item_id );
					$child_product->mnm_category = $cat_slug;

					$new_children[ $mnm_item_id ] = $child_product;
					
				}
			
			}

			$children = ! empty( $new_children ) ? $new_children : $children;

		}

		return $children;

	}


	/**
	 * The category caption
	 *
	 * @param  obj $container_product WC_Product_Mix_and_Match
	 */
	public static function first_category_caption( $container_product ) {

		if( self::use_multi_cat( $container_product ) ) {

			$categories = self::get_categories( $container_product );

			$category = get_term_by( 'slug', array_shift( $categories ), 'product_cat' );

			if( $category && ! is_wp_error( $category ) ) {
				
				echo '<div class="products categories-wrapper">';
				add_action( 'woocommerce_after_mnm_items', array( __CLASS__, 'close_wrapper' ), 200 );

				// Stash the current category.
				$container_product->current_cat = $category->slug;

				// Don't display the category count.
				add_filter( 'woocommerce_subcategory_count_html', '__return_null' );

				// Use the existing category title template.
				woocommerce_template_loop_category_title( $category );
			}

		}

	}

	/**
	 * Insert a category break if the category has changed.
	 *
	 * @param  obj $child_product WC_Product
	 * @param  obj $container_product WC_Product_Mix_and_Match
	 */
	public static function change_category_caption( $child_product, $container_product ) {

		if( self::use_multi_cat( $container_product ) ) {

			if( property_exists ( $container_product, 'current_cat' ) && $container_product->current_cat !== $child_product->mnm_category ) {
				
				$new_category = get_term_by( 'slug', $child_product->mnm_category, 'product_cat' );

				if( $new_category && ! is_wp_error( $new_category ) ) {

					wc_mnm_template_child_items_wrapper_close( $container_product );
					woocommerce_template_loop_category_title( $new_category );
					wc_mnm_template_child_items_wrapper_open( $container_product );

				}

				// Update the current state of the parent container.
				$container_product->current_cat = $child_product->mnm_category;

			}

		}

	}

	/**
	 * CLose the wrapping div
	 *
	 * @param  obj $container_product WC_Product_Mix_and_Match
	 */
	public static function close_wrapper( $container_product ) {
		echo '</div>';
	}

	/*-----------------------------------------------------------------------------------*/
	/* Helpers */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Does this product use categories?
	 *
	 * @param  WC_Product $product
	 * @return bool
	 */
	public static function use_categories( $product ) {
		return 'yes' ===  $product->get_meta( '_mnm_use_category' ) && ! empty( self::get_categories( $product ) );
	}

	/**
	 * Does this product use multiple categories?
	 *
	 * @param  WC_Product $product
	 * @return bool
	 */
	public static function use_multi_cat( $product ) {
		return 'yes' ===  $product->get_meta( '_mnm_use_category' ) && count( self::get_categories( $product ) ) > 1;
	}

	/**
	 * Get the categories, ensure they're an array.
	 *
	 * @param  WC_Product $product
	 * @return array
	 */
	public static function get_categories( $product ) {
		$categories = $product->get_meta( '_mnm_product_cat' );

		// Old data was stored as integer, convert to slug.
		if( is_integer( $categories ) ) {
			$term = get_term_by( 'id', $categories, 'product_cat' );
			$categories = $term && ! is_wp_error( $term ) ? $term->slug : array();
		}
		return (array) $categories ;
	}

	/**
	 * Get the categeory's contents.
	 *
	 * @param  string $cat the category slug
	 * @return array
	 */
	public static function get_cat_contents( $cat ) {
		$args = apply_filters( 'wc_mnm_categories_get_cat_content_args',
			array( 
				'type'     => WC_Mix_and_Match_Helpers::get_supported_product_types(),
				'category' => array( $cat ),
				'orderby'  => 'title',
				'order'    => 'ASC',
				'return'   => 'ids',
				'limit'    => -1
			)
		);
		return wc_get_products( $args );

	}
}
add_action( 'plugins_loaded', array( 'WC_MNM_Categories', 'init' ) );