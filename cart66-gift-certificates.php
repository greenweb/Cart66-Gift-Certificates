<?php
  /*
  Plugin Name: Cart66 Gift Certificates
  Plugin URI: http://beforesite.com/wordpress-plugins/cart66-gift-certificates
  Description: Adds Gift Certificate functionality to Cart66 - Requires Cart66 1.5.0.6 or greater
  Version: 1
  Author: Rew Rixom
  Author URI: http://greenvilleweb.com/
  */
  
  /**
   * Copyright (c) November 17, 2012  Andrew Rixom and Beforesite LLC. All rights reserved.
   *
   * Released under the GPL license
   * http://www.opensource.org/licenses/gpl-license.php
   *
   * This is an add-on for WordPress
   * http://wordpress.org/
   *
   * **********************************************************************
   * This program is free software; you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation; either version 2 of the License, or
   * (at your option) any later version.
   *
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   * GNU General Public License for more details.
   * **********************************************************************
   */
  
/**
 * Register Globals
 * */
$cgc_plugin_loc = plugin_dir_url( __FILE__ );
$cgc_plugname = "Cart66 Gift Certificates";
$cgc_plug_shortname = "_cgc_";
$cgc_the_web_url = get_bloginfo('url');
if ( preg_match( '/^https/', $cgc_plugin_loc ) && !preg_match( '/^https/', get_bloginfo('url') ) ){
  $cgc_plugin_loc = preg_replace( '/^https/', 'http', $cgc_plugin_loc );
}

/**
 * Define Globals
 * */
define( 'CGC_FRONT_URL',  $cgc_plugin_loc );
define( 'CGC_URL',        plugin_dir_url(__FILE__)  );
define( 'CGC_PATH',       plugin_dir_path(__FILE__) );
define( 'CGC_BASENAME',   plugin_basename(__FILE__) );
define( 'CGC_WEB_URL',    $cgc_the_web_url );
define( 'CGC_NAME',       $cgc_plugname );
define( 'CGC_S_NAME',     $cgc_plug_shortname );
define( 'CGC_VERSION',    '1' );
define( 'CGC_PREFIX' ,    'cgc_' );
define( 'CGC_C66_PREFIX', 'cart66_' );
define( 'CGC_LANG',       'cgc' );

$c66_status_options = cgc_getValue('status_options');
if ($c66_status_options) {
  $c66_opts = explode(',', $c66_status_options);
  $c66_status = trim($c66_opts[0]);
}else{
  $c66_status = 'new';
}
define( 'CGC_STATUS',  $c66_status );
/* activation and deactivation */
include 'lib'.DIRECTORY_SEPARATOR.'cgc-engine-class.php';
include 'lib'.DIRECTORY_SEPARATOR.'cgc-admin-class.php';

// instantiate the class
$cgc_engine = new cgcEngine();
$cgc_admin  = new cgcAdmin();

add_action('cart66_after_order_saved', array('cgcEngine','cgc_add_to_db_table'), 10, 2); 

/**
 * Run on activation 
 * */
register_activation_hook( __FILE__, 'cgc_activate' );

function cgc_activate()
{
 install_cgc_tables();
  // preload the default options in to the database
  $defaults =  cgcAdmin::cgc_options_array();
  foreach ($defaults as $default_array) {
    extract($default_array);
    add_option( $id, $std);
  } // end first loop
}
/**
 * Run on deactivation 
 * */
register_deactivation_hook(__FILE__, 'cgc_deactivate');
function cgc_deactivate()
{
  if (get_option(CGC_S_NAME."delete_settings") != true) return; // don't delete
  // remove the plugin's default options from the database
  $defaults = cgcAdmin::cgc_options_array();
  foreach ($defaults as $default_array) {
    extract($default_array);
    delete_option( $id );
  } // end first loop
}

//End cgc_check_table_existance
function install_cgc_tables()
{
  // NB Always set wpdb globally!
  global $wpdb;
  $db_prefix  = CGC_PREFIX;
  $cgc_data_tb    = $wpdb->prefix . $db_prefix . "data";
  // Table structure for cgc data
  $cgc_data_table = 
   "CREATE TABLE IF NOT EXISTS $cgc_data_tb (
     ID             int(18) NOT NULL AUTO_INCREMENT,
     order_id       int(18) NOT NULL,
     product_id     int(18) NOT NULL,
     quantity       int(18) NOT NULL,
     coupon         varchar(255) NOT NULL,
     coupon_amount  decimal(12,2) unsigned NOT NULL default 0,
     order_status   varchar(255) NOT NULL,
     status         varchar(255) NOT NULL,
     chron_added    tinyint(1) not null default 0,
     promo_added    tinyint(1) not null default 0,
     PRIMARY KEY (ID)
   ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
  /* Run the DB check */
  $cgc_db_check = cgc_check_table_existance($cgc_data_tb);
  if (!$cgc_db_check) {
    $wpdb->query($cgc_data_table);
  } 
} //end install_cgc_tables

//lets check if the database has our table:
function cgc_check_table_existance($new_table)
{
 //NB Always set wpdb globally!
 global $wpdb;
  foreach ($wpdb->get_col("SHOW TABLES",0) as $table ){
    if ($table == $new_table){
      return true;
    }
  }
 return false;
}
function cgc_getValue($key, $entities=false) {
    global $wpdb;
    $settingsTable = $wpdb->prefix . CGC_C66_PREFIX . 'cart_settings';
    $value = $wpdb->get_var("SELECT `value` from $settingsTable where `key`='$key'");
    
    if(!empty($value) && $entities) {
      $value = htmlentities($value);
    }
    
    return empty($value) ? false : $value;
}