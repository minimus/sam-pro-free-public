<?php
/*
Plugin Name: SAM Pro (Free Edition)
Plugin URI: http://uncle-sam.info/
Description: Flexible advertisements management system of the WordPress blog. Visit <a href="http://uncle-sam.info/">plugin Home Site</a> for more details.
Version: 1.9.7.69
Author: minimus
Author URI: http://blogcoding.ru
Text Domain: sam-pro-free
Domain Path: /langs
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $samProObject, $SAM_PRO_Query;

define( 'SAM_PRO_MAIN_FILE', __FILE__ );
define( 'SAM_PRO_FREE', true );

include_once( 'sam-pro-core.php' );

if ( is_admin() ) {
	include_once( 'sam-pro-admin.php' );
	$samProObject = new SamProAdmin();
}
else {
	include_once( 'sam-pro-front.php' );
	$samProObject = new SamProFront();
}

include_once( 'sam-pro-widgets.php' );
if(class_exists( 'sam_pro_place_widget' ))
	add_action('widgets_init', create_function('', 'return register_widget("sam_pro_place_widget");'));
if(class_exists( 'sam_pro_ad_widget' ))
	add_action('widgets_init', create_function('', 'return register_widget("sam_pro_ad_widget");'));
if(class_exists( 'sam_pro_zone_widget' ))
	add_action('widgets_init', create_function('', 'return register_widget("sam_pro_zone_widget");'));
if(class_exists( 'sam_pro_block_widget' ))
	add_action('widgets_init', create_function('', 'return register_widget("sam_pro_block_widget");'));

if( ! function_exists('samProDrawAd') ) {
	function samProDrawAd( $id, $args, $useTags ) {
		global $samProObject;

		if ( is_object( $samProObject ) && !is_admin() ) {
			$ad = $samProObject->buildAd($id, $args, $useTags);
			echo $ad;
		}
	}
}

if( ! function_exists( 'samProDrawPlace' ) ) {
	function samProDrawPlace( $id, $args, $useTags) {
		global $samProObject;

		if( is_object($samProObject) && !is_admin() ) {
			$ad = $samProObject->buildPlace($id, $args, $useTags);
			echo $ad;
		}
	}
}

if( ! function_exists( 'samProDrawZone' ) ) {
	function samProDrawZone( $id, $args, $useTags ) {
		global $samProObject;

		if( is_object($samProObject) && !is_admin() ) {
			$ad = $samProObject->buildZone($id, $args, $useTags);
			echo $ad;
		}
	}
}

if( ! function_exists( 'samProDrawBlock' ) ) {
	function samProDrawBlock( $id, $args ) {
		global $samProObject;

		if( is_object($samProObject) && !is_admin() ) {
			$ad = $samProObject->buildBlock($id, $args);
			echo $ad;
		}
	}
}

function is_sam_pro( $version = null ) {
	if(is_null($version)) return 0;
	else {
		if(0 === (int)$version || (is_string($version) && 'free' === strtolower($version))) return true;
		else return false;
	}
}