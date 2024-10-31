<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
	Plugin Name: Products Clearance Sale for WooCommerce
	Plugin URI: http://androidbubble.com/blog/wordpress/plugins/products-clearance-sale-for-woocommerce
	Description: This is a great plugin to handle the clearance sales every season or new year.
	Version: 1.0.4
	Author: Fahad Mahmood 
	Author URI: https://www.androidbubbles.com
	Text Domain: pcsfw
	Domain Path: /languages
	License: GPL2
	
	This WordPress Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. This free software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/ 

	
        
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	
	$pcsfw_activated = false;
	$pcsfw_all_plugins = get_plugins();
	$pcsfw_plugins_activated = apply_filters( 'active_plugins', get_option( 'active_plugins' ));
	
	if(is_multisite()){			
		
		$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
		
		$pcsfw_plugins_activated = array_keys($active_sitewide_plugins);		
		
	}
		
	if(array_key_exists('woocommerce/woocommerce.php', $pcsfw_all_plugins) && in_array('woocommerce/woocommerce.php', $pcsfw_plugins_activated)){
		$pcsfw_activated = true;
	}
	
	if(!$pcsfw_activated){ 
		__('Products Clearance Sale for WooCommerce is not activated.', 'pcsfw'); 
		return; 
	}else{ 
		
	}
	
	define( 'PCSFW_PLUGIN_SLUG', dirname( plugin_basename( __FILE__ ) ) );
	define( 'PCSFW_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'PCSFW_PLUGIN_DIR_URL_ABSOLUTE_PATH', realpath( plugin_dir_path( __FILE__ ) ) );
	define( 'PCSFW_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'PCSFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	define( 'PCSFW_LIVE_MODE', true );
	define( 'PCSFW_MIN_PRODUCTS', 1 );
	define( 'PCSFW_MAX_PRODUCTS', 100 );
	
	include_once('inc/functions.php');
		
