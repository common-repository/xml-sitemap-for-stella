<?php 
/*
Plugin Name: XML Sitemap for Stella
Version: 1.1.0
Plugin URI: http://www.gomo.pt/
Description: Generates a XML Sitemap on your WordPress multi-language website when powered by Stella multi-language plugin.
Author: Luis Godinho
Author URI: http://twitter.com/luistinygod
License: GPL2
Text Domain: xml-sitemap-for-stella
Domain path: /languages

XML Sitemap for Stella by GOMO (GOMO SFS)
Copyright (C) 2013, GOMO - Luis Godinho (email : luis@gomo.pt )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>, or 
write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, 
Boston, MA  02110-1301  USA.

*/

/**
 * @package Main
 */

if ( !defined('DB_NAME') ) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

if ( !defined('GOMO_SFS_URL') )
	define( 'GOMO_SFS_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GOMO_SFS_PATH') )
	define( 'GOMO_SFS_PATH', plugin_dir_path( __FILE__ ) );

define( 'GOMO_SFS_VERSION', '1.1.0' );

/**
 * Include classes
 */
 
require( GOMO_SFS_PATH .'inc/class-sitemaps-multiple-langs.php' );

// Makes sure the plugin is defined before trying to use it
if( ! function_exists('is_plugin_active')) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}


/**
 * Run action and filters according to scenario
 */

if ( is_admin() ) {
	if( !is_plugin_active('stella-plugin/stella-plugin.php') && !is_plugin_active('stella-free/stella-plugin.php') ) {
		
		add_action( 'admin_init', 'gomo_sfs_deactivate' );
		add_action( 'admin_notices', 'gomo_sfs_admin_notice' );
		
		function gomo_sfs_deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			gomo_sfs_deactivation();
		}
		
		function gomo_sfs_admin_notice() {
			echo '<div class="updated"><p>'. esc_html__( 'XML Sitemap for Stella requires Stella plugin to be active in order to run properly.', 'xml-sitemap-for-stella' ).'</p></div>';
			if( isset( $_GET['activate'] ) )
				unset( $_GET['activate'] );
		}
	
	} else {
		add_action( 'plugins_loaded', 'gomo_sfs_admin', 0 );
		add_filter( 'plugin_action_links_'. plugin_basename( __FILE__) , 'gomo_sfs_plugin_action_links' );
		
		register_activation_hook(__FILE__, 'gomo_sfs_activation');
		register_deactivation_hook(__FILE__, 'gomo_sfs_deactivation');
	}
} else {
	add_action( 'plugins_loaded', 'gomo_sfs_frontend', 0 );
}

// Load plugin text domain
add_action( 'init', 'gomo_sfs_load_textdomain' );
function gomo_sfs_load_textdomain() {
	
	$domain = 'xml-sitemap-for-stella';
	//$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	// load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

}

/**
 * Activation / deactivation hooks
 */
function gomo_sfs_activation() {
	gomo_sfs_init();
	flush_rewrite_rules();
}

function gomo_sfs_deactivation() {
	flush_rewrite_rules();
	delete_option('gomo_sitemap_options');
}

// Init sitemaps rewrite rules and tags
add_action('init', 'gomo_sfs_init', 1);
function gomo_sfs_init() {
	add_rewrite_tag('%xmlsitemap%','([^/]+?)');
	add_rewrite_tag('%xmlsitemap_lang%','([^/]+?)');
	add_rewrite_tag('%xmlsitemap_page%','([0-9]+)?');
	// Add Rewrite rules
	add_rewrite_rule( 'sitemap-index\.xml$', 'index.php?xmlsitemap=1', 'top' );
	add_rewrite_rule( 'sitemap-([^/]+?)-([^/]+?)-([0-9]+)?\.xml$', 'index.php?xmlsitemap=$matches[1]&xmlsitemap_lang=$matches[2]&xmlsitemap_page=$matches[3]', 'top' );
}

function gomo_sfs_plugin_action_links( $actions ) {
	/* translators: This is the link on the plugins page, near the deactivate */
	$actions[] = '<a href="' . menu_page_url( 'sitemaps-multiple-languages', false ) . '">'. esc_html__( 'Settings', 'xml-sitemap-for-stella' ).'</a>';
	return $actions;
}


/**
 * Load frontend specific functions
 */
function gomo_sfs_frontend() {
	$gomo_sfs_sitemaps = new GOMO_Sitemaps_Multiple_Langs();
	add_action( 'stella_parameters', 'gomo_sfs_fetch_stella', 1, 2 );
	
}

function gomo_sfs_fetch_stella( $langs, $use_hosts ) {
	
	// set array of langs for sitemaps
	$sitemap_langs = array( $langs['default']['prefix'] );
	$hosts = array( $langs['default']['prefix'] => $langs['default']['host'] );
	if( !empty( $langs['others'] ) ) {
		foreach( $langs['others'] as $prefix=>$item ) {
			array_push($sitemap_langs, $prefix );
			$hosts[$prefix] = $item['host'];
		}
	}
	
	do_action('gomo_sitemap_start', $langs['default']['prefix'], $sitemap_langs, $use_hosts, $hosts);
	
}


/**
 * Load admin specific functions
 */
function gomo_sfs_admin() {
	require( GOMO_SFS_PATH .'inc/class-sfs-admin.php' );
}




?>