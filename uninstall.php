<?php

	if (!defined('WP_UNINSTALL_PLUGIN')) {
	    die;
	}
	 
	delete_option( 'wsi_field_dirpre' );
	delete_option( 'wsi_field_perpage' );
	delete_option( 'wsi_field_imgsize' );
	 
	// for site options in Multisite

	delete_site_option('wsi_field_dirpre' );
	delete_site_option('wsi_field_perpage' );
	delete_site_option('wsi_field_imgsize' );
 