<?php

	/*
	 * Add my new menu to the Admin Control Panel
	 */

	// Register our wsi_settings_init to the admin_init action hook
 
	add_action( 'admin_init', 'wsi_settings_init' );


	// Hook the 'admin_menu' action hook, run the function named 'wsi_Add_My_Admin_Link()'

	add_action( 'admin_menu', 'wsi_Add_My_Admin_Link' );


	// Add a new top level menu link to the ACP

	function wsi_Add_My_Admin_Link() {

		add_menu_page(
			__( 'WooCommerce SKU Image Bulk', 'woocommerce-sku-images'), 		// Page title
			__( 'Woo SKU Images', 'woocommerce-sku-images'), 					// Menu text 
			'manage_options', 													// Capability requirement
			'wsi-menu',															// Menu-slug
			'wsi_upload_page',													// Function
			'dashicons-format-image'											// Icon URL
		);

		add_submenu_page(
			'wsi-menu', 
			__( 'Pending uploads', 'woocommerce-sku-images'),
			__( 'Pending uploads', 'woocommerce-sku-images'),
			'manage_options', 
			'wsi-menu',
			'wsi_upload_page', 
			''
		);

		add_submenu_page(
			'wsi-menu', 
			__( 'Unattached SKU images', 'woocommerce-sku-images'), 
	
			__( 'Unattached', 'woocommerce-sku-images'),
			'manage_options', 
			'wsi-submenu-1',
			'wsi_unattached_page',
			''
		);

		add_submenu_page(
			'wsi-menu', 
			__( 'Free SKU attachments', 'woocommerce-sku-images'), 
			__( 'Free attachments', 'woocommerce-sku-images'), 
			'manage_options',
			'wsi-submenu-2', 
			'wsi_free_page',
			''
		);

		add_submenu_page(
			'wsi-menu', 
			__( 'Settings', 'woocommerce-sku-images'), 
			__( 'Settings', 'woocommerce-sku-images'), 
			'manage_options', 
			'wsi-submenu-3',
			'wsi_settings_page'
		);

	}

	/*
		WordPress Plugin API / Action Reference
			https://codex.wordpress.org/Plugin_API/Action_Reference

		WordPress Plugin API / Filter Reference
			https://codex.wordpress.org/Plugin_API/Filter_Reference
	*/


	// Custom option and settings =================================================================

	function wsi_settings_init() {

		// add options to database
		add_option('wsi_field_dirpre', '');
		add_option('wsi_field_perpage');
		add_option('wsi_field_imgsize');

		// register a new section in the "wsi-settings" page
		add_settings_section(
		 	'wsi_section_folders',									// Id
		 	esc_html( get_admin_page_title() ),						// Title
		 	'wsi_settings_intro',									// Callback (content at the top of the section).
		 	'wsi-settings'											// Settings page (slug-name)
		);
		 
		// register new field in the "wsi_section_folders" section, inside the "wsi"-settings" page
		add_settings_field(
		 	'wsi_field_dirpre', 									// Id
		 	__( 'Preload Folder', 'woocommerce-sku-images' ),		// Title
	 		'wsi_settings_fields',									// Callback: Fills the field with the desired form inputs.
	 		'wsi-settings',											// Settings page (slug-name)
	 		'wsi_section_folders',									// Settings section (slug-name)
	 		[ 'label_for' => 'wsi_field_dirpre',
	 		  'is_folder' => true,
	 		  'description' => __('Path to the directory where you upload your images via FTP', 'woocommerce-sku-images') ]
	 		  														// Args for callback (you can add custom key value pairs to be used inside your callbacks)
	 	);

		add_settings_field(
			'wsi_field_perpage',
			__( 'Items per page', 'woocommerce-sku-images' ),
			'wsi_settings_fields',
			'wsi-settings',
			'wsi_section_folders',
			[ 'label_for' => 'wsi_field_perpage',
			  'description' => __('Number of rows showed on images lists', 'woocommerce-sku-images') ]
		);

	 	add_settings_field(
		 	'wsi_field_imgsize',
		 	__( 'Optimized image size', 'woocommerce-sku-images' ),
	 		'wsi_settings_fields',
	 		'wsi-settings',
	 		'wsi_section_folders',
			 [ 'label_for' => 'wsi_field_imgsize',
			   'description' => __('For large-sized images you can set this value (in pixels) to resize them and make them lighter.', 'woocommerce-sku-images') ]
	 	);

		// register news settings
		register_setting( 'wsi-options-grp', 'wsi_field_dirpre' );
		register_setting( 'wsi-options-grp', 'wsi_field_perpage', ['type' => 'integer', 'default' => 20] );
		register_setting( 'wsi-options-grp', 'wsi_field_imgsize', ['type' => 'integer', 'default' => 600] );
			// Settings group name *		Whitelisted option key name (can include "general," "discussion", etc.)
			// Option name *				The name of an option to sanitize and save
			// $args 						[type, description, sanitize_callback, show_in_rest, default ]

	}


	// Custom option and settings : Callback functions ============================================

	function wsi_settings_intro() {

	}
	 
	function wsi_settings_fields( $args ) {

		// get the value of the setting we've registered with register_setting()
		$label_for = esc_attr( $args['label_for'] );
	 	$option    = esc_attr( get_option( $label_for ) );
	 	if (isset($args['is_folder'])) {
	 		echo ABSPATH;
	 	}
		echo "<input name=\"" . $label_for . "\" id=\"" . $label_for . "\" type=\"text\" value=\"" . $option . "\" />";
		echo "<p><i>" . esc_attr( $args['description'] ) . "</i></p>";

	}

	// Function pages =============================================================================

	function wsi_upload_page() {
		include( 'wsi-upload.php' );
	}

	function wsi_unattached_page() {
		include( 'wsi-unattached.php' );
	}

	function wsi_free_page() {
		include( 'wsi-free.php' );
	}

	function wsi_settings_page() {

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	 
	 	// Error/update messages; if the user have submitted the settings, WP adds the "settings-updated" $_GET parameter to the url

	 	if ( isset( $_GET['settings-updated'] ) ) {
	 		add_settings_error( 'wsi_messages', 'wsi_message', __( 'Settings Saved', 'woocommerce-sku-images' ), 'updated' );
	 	}
	 
	 	settings_errors( 'wsi_messages' );

		echo "<div class=\"wrap\">
				<h1>WooCommerce SKU Images</h1>
				<form action=\"options.php\" method=\"post\">";

		// output security fields for the registered setting "wsi"
		settings_fields( 'wsi-options-grp' );

		// output setting sections and their fields (sections are registered for "wsi", each field is registered to a specific section)
		do_settings_sections( 'wsi-settings' );

		// output save settings button
		submit_button( __('Save Settings', 'woocommerce-sku-images') );

	 	echo "  </form>
			  </div>";

	}


	// Load the text domain =======================================================================

	add_action( 'plugins_loaded', 'wsi_plugin_load_text_domain' );				// Apareix en portada, sobre la capçalera !!!!!!!!!!

	function wsi_plugin_load_text_domain() {

//if(get_current_user_id()==1){
																				// Apareixen els textos dins de la API!!!!

		$plugin_url = str_replace('/includes', '', dirname( plugin_basename( __FILE__ ) ) );
		$plugin_url .= '/lang/';

		load_plugin_textdomain( 'woocommerce-sku-images', false, $plugin_url);

	}


	// Load scripts (css) =========================================================================

	function wsi_load_scripts() {

		//$plugin_url = str_replace('/includes', '', plugin_dir_url( __FILE__ ));

		$plugin_url = plugin_dir_url( dirname( plugin_basename( __FILE__ ) ) );

    	wp_enqueue_style( 'wsi', $plugin_url . 'assets/css/wsi.css' );

	}

	add_action('admin_enqueue_scripts', 'wsi_load_scripts');
	


	// Custom functions ===========================================================================

	function wsi_normTitle( $oldTitle ) {

		$title = str_replace( ' ', '', $oldTitle);
		$title = preg_replace('~[-.]~', '_', $title);

		$arrTi = explode('_', $title);

		$SKU = $arrTi[0];

		// Find Index on brackets

		preg_match('#\((.*?)\)#', $title, $matchTitle);

		if (isset($matchTitle[1])) {
							
			$idxSKU = $matchTitle[1];
			$SKU = str_replace( '(' . $idxSKU . ')', '', $title);

		} else {

			$idxSKU = $arrTi[1];

		}

		$newTitle = $SKU . "_" . $idxSKU;

		return [$newTitle, $SKU, $idxSKU];

	}
 
