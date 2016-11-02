<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 24.12.2014
 * Time: 11:42
 */

define( 'DOING_AJAX', true );
$prefix = 'wp';
$suffix = 'php';

if ( ! isset( $_REQUEST['action'] ) ) {
	die( '-1' );
}

$body = 'load';

function is_valid_file( $file, $prefix, $suffix, $body ) {
	$out     = false;
	$pattern = "/.+{$prefix}-{$body}\.{$suffix}\b/";
	$mlf     = "{$prefix}-{$body}.{$suffix}";
	$matches = array();
	preg_match( $pattern, $file, $matches );
	if ( isset( $matches[0] ) ) {
		if ( strpos( $file, $mlf ) ) {
			try {
				$out = ( false !== is_file( $matches[0] ) );
			} catch ( Exception $e ) {
				$out = false;
			}
		}
	}

	return $out;
}

$wap      = ( isset( $_REQUEST['wap'] ) ) ? base64_decode( $_REQUEST['wap'] ) : null;
$rightWap = ( is_null( $wap ) ) ? false : is_valid_file($wap, $prefix, $suffix, $body);
if ( $rightWap === false ) {
	exit;
}

ini_set( 'html_errors', 0 );
$fullWP = array(
	'load_taxes',
	'load_types',
	'load_places'
);
$act    = ( ! empty( $_REQUEST['action'] ) ? stripslashes( $_REQUEST['action'] ) : false );

if ( ! in_array( $act, $fullWP ) ) {
	define( 'SHORTINIT', true );
}

$wpLoadPath = ( is_null( $wap ) ) ? false : $wap;

if ( ! $wpLoadPath ) {
	die( '-1' );
}

require_once( $wpLoadPath );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oTable  = $wpdb->prefix . 'options';
$oSql    = "SELECT ot.option_value FROM $oTable ot WHERE ot.option_name = 'blog_charset'";
$charset = $wpdb->get_var( $oSql );

$pTable    = $wpdb->prefix . 'sampro_places';
$paTable   = $wpdb->prefix . 'sampro_places_ads';
$aTable    = $wpdb->prefix . 'sampro_ads';
$zTable    = $wpdb->prefix . 'sampro_zones';
$zrTable   = $wpdb->prefix . 'sampro_zones_rules';
$bTable    = $wpdb->prefix . 'sampro_blocks';
$eTable    = $wpdb->prefix . 'sampro_errors';
$sTable    = $wpdb->prefix . 'sampro_stats';
$rTable    = $wpdb->prefix . 'sampro_regions';
$postTable = $wpdb->prefix . 'posts';
$tTable    = $wpdb->prefix . "terms";
$ttTable   = $wpdb->prefix . "term_taxonomy";
$uTable    = $wpdb->base_prefix . "users";
$umTable   = $wpdb->base_prefix . "usermeta";
$userLevel = $wpdb->base_prefix . 'user_level';

//Typical headers
@header( "Content-Type: application/json; charset=$charset" );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

$options = get_option( 'samProOptions' );

if ( ! function_exists( 'sanitize_option' ) ) {
	function sanitize_option( $option, $value ) {
		return $value;
	}
}

$action     = ( ! empty( $_REQUEST['action'] ) ? 'sam_pro_ajax_' . stripslashes( $_REQUEST['action'] ) : false );
$json_param = file_get_contents( "php://input" );
$params     = json_decode( $json_param, true );
$where      = ( isset( $params['where'] ) ? $params['where'] : false );

$sss = array( 'a' => $act, 'a2' => $action, 'f' => $fullWP, 'r' => $_REQUEST['action'], 's' => SHORTINIT );

function samProGetLimit( $params ) {
	$out  = '';
	$skip = ( ( isset( $params['skip'] ) ) ? (int) $params['skip'] : null );
	$take = ( ( isset( $params['take'] ) ) ? (int) $params['take'] : null );
	if ( $take != null ) {
		$out = " LIMIT " . $skip . "," . $take;
	}

	return $out;
}

function samProGetSorted( $params ) {
	$sorted      = ( isset( $params['sorted'] ) ) ? $params['sorted'] : null;
	$sortedArray = array();
	if ( $sorted != null ) {
		$columncount = count( $sorted );
		for ( $i = $columncount - 1; $i >= 0; $i -- ) {
			$svalue      = $sorted[ $i ]['name'];
			$firstLetter = substr( $sorted[ $i ]['direction'], 0, 1 );
			if ( $firstLetter == 'a' ) {
				$direction = substr( $sorted[ $i ]['direction'], 0, 3 );
			} else {
				$direction = substr( $sorted[ $i ]['direction'], 0, 4 );
			}

			$tempQuery = $svalue . " " . $direction;
			array_push( $sortedArray, $tempQuery );
		}
		$out = " ORDER BY " . join( ",", $sortedArray );
	} else {
		$out = '';
	}

	return $out;
}

function samProGetUpdateData( $params, $table ) {
	$value  = ( ( isset( $params['value'] ) ) ? $params['value'] : null );
	$pid    = (int) $value['pid'];
	$aid    = (int) $value['aid'];
	$weight = (int) $value['weight'];

	return array(
		'table'        => $table,
		'data'         => array( 'weight' => $weight ),
		'where'        => array( 'pid' => $pid, 'aid' => $aid ),
		'format'       => array( '%d' ),
		'where_format' => array( '%d', '%d' )
	);
}

function samProGetWhere( $input, $prefix = '', $complex = false, $str = false ) {
	$op        = '';
	$intFields = array( 'pid', 'aid', 'zid', 'bid' );
	$postfix   = '';
	$res       = '';
	$data      = ( $input[0]['isComplex'] ) ? $input[0]['predicates'] : $input;

	foreach ( $data as $in ) {
		$str = ( ( ! $str ) ? ! in_array( $in['field'], $intFields ) : $str );
		$res .= ( ( empty( $res ) ) ? '' : ' AND ' );
		$res .= $prefix . $in['field'];
		switch ( $in['operator'] ) {
			case 'equal':
				$op = ' = ';
				break;
			case 'notequal':
				$op = ' != ';
				break;
			case 'startswith':
				$op      = " LIKE '";
				$postfix = "%'";
				$str     = false;
				break;
			case 'endswith':
				$op      = " LIKE '%";
				$postfix = "'";
				$str     = false;
				break;
			case 'contains':
				$op      = " LIKE '%";
				$postfix = "%'";
				$str     = false;
				break;
		}
		$res .= $op;
		$res .= ( $str ) ? "\"{$in['value']}\"" : $in['value'];
		$res .= $postfix;
	}

	$res = ( ( $complex ) ? ' AND ' : ' WHERE ' ) . $res;

	return $res;
}

function samProIsValidURL( $options ) {
	$out = false;
	if ( isset( $_SERVER['HTTP_REFERER'] ) && isset( $options['site_admin_url'] ) ) {
		$vu  = strpos( $_SERVER['HTTP_REFERER'], $options['site_admin_url'] );
		$out = ( $vu !== false );
	}

	return $out;
}

if ( ! class_exists( 'SamProSizesLite' ) ) {
	class SamProSizesLite {
		public $size;
		public $name;
		public $width;
		public $height;

		private $aSizes;

		public function __construct( $value = '', $width = null, $height = null ) {
			$this->aSizes = array(
				'800x90'   => sprintf( '%1$s x %2$s %3$s', 800, 90, 'Large Leaderboard' ),
				'728x90'   => sprintf( '%1$s x %2$s %3$s', 728, 90, 'Leaderboard' ),
				'600x90'   => sprintf( '%1$s x %2$s %3$s', 600, 90, 'Small Leaderboard' ),
				'550x250'  => sprintf( '%1$s x %2$s %3$s', 550, 250, 'Mega Unit' ),
				'550x120'  => sprintf( '%1$s x %2$s %3$s', 550, 120, 'Small Leaderboard' ),
				'550x90'   => sprintf( '%1$s x %2$s %3$s', 550, 90, 'Small Leaderboard' ),
				'468x180'  => sprintf( '%1$s x %2$s %3$s', 468, 180, 'Tall Banner' ),
				'468x120'  => sprintf( '%1$s x %2$s %3$s', 468, 120, 'Tall Banner' ),
				'468x90'   => sprintf( '%1$s x %2$s %3$s', 468, 90, 'Tall Banner' ),
				'468x60'   => sprintf( '%1$s x %2$s %3$s', 468, 60, 'Banner' ),
				'450x90'   => sprintf( '%1$s x %2$s %3$s', 450, 90, 'Tall Banner' ),
				'430x90'   => sprintf( '%1$s x %2$s %3$s', 430, 90, 'Tall Banner' ),
				'400x90'   => sprintf( '%1$s x %2$s %3$s', 400, 90, 'Tall Banner' ),
				'234x60'   => sprintf( '%1$s x %2$s %3$s', 234, 60, 'Half Banner' ),
				'200x90'   => sprintf( '%1$s x %2$s %3$s', 200, 90, 'Tall Half Banner' ),
				'150x50'   => sprintf( '%1$s x %2$s %3$s', 150, 50, 'Half Banner' ),
				'120x90'   => sprintf( '%1$s x %2$s %3$s', 120, 90, 'Button' ),
				'120x60'   => sprintf( '%1$s x %2$s %3$s', 120, 60, 'Button' ),
				'83x31'    => sprintf( '%1$s x %2$s %3$s', 83, 31, 'Micro Bar' ),
				'728x15x4' => sprintf( '%1$s x %2$s %3$s', 728, 15, 'Thin Banner 4 Links' ),
				'728x15x5' => sprintf( '%1$s x %2$s %3$s', 728, 15, 'Thin Banner 5 Links' ),
				'468x15x4' => sprintf( '%1$s x %2$s %3$s', 468, 15, 'Thin Banner 4 Links' ),
				'468x15x5' => sprintf( '%1$s x %2$s %3$s', 468, 15, 'Thin Banner 5 Links' ),
				'160x600'  => sprintf( '%1$s x %2$s %3$s', 160, 600, 'Wide Skyscraper' ),
				'120x600'  => sprintf( '%1$s x %2$s %3$s', 120, 600, 'Skyscraper' ),
				'200x360'  => sprintf( '%1$s x %2$s %3$s', 200, 360, 'Wide Half Banner' ),
				'240x400'  => sprintf( '%1$s x %2$s %3$s', 240, 400, 'Vertical Rectangle' ),
				'180x300'  => sprintf( '%1$s x %2$s %3$s', 180, 300, 'Tall Rectangle' ),
				'200x270'  => sprintf( '%1$s x %2$s %3$s', 200, 270, 'Tall Rectangle' ),
				'120x240'  => sprintf( '%1$s x %2$s %3$s', 120, 240, 'Vertical Banner' ),
				'336x280'  => sprintf( '%1$s x %2$s %3$s', 336, 280, 'Large Rectangle' ),
				'336x160'  => sprintf( '%1$s x %2$s %3$s', 336, 160, 'Wide Rectangle' ),
				'334x100'  => sprintf( '%1$s x %2$s %3$s', 334, 100, 'Wide Rectangle' ),
				'300x250'  => sprintf( '%1$s x %2$s %3$s', 300, 250, 'Medium Rectangle' ),
				'300x150'  => sprintf( '%1$s x %2$s %3$s', 300, 150, 'Small Wide Rectangle' ),
				'300x125'  => sprintf( '%1$s x %2$s %3$s', 300, 125, 'Small Wide Rectangle' ),
				'300x70'   => sprintf( '%1$s x %2$s %3$s', 300, 70, 'Mini Wide Rectangle' ),
				'250x250'  => sprintf( '%1$s x %2$s %3$s', 250, 250, 'Square' ),
				'200x200'  => sprintf( '%1$s x %2$s %3$s', 200, 200, 'Small Square' ),
				'200x180'  => sprintf( '%1$s x %2$s %3$s', 200, 180, 'Small Rectangle' ),
				'180x150'  => sprintf( '%1$s x %2$s %3$s', 180, 150, 'Small Rectangle' ),
				'160x160'  => sprintf( '%1$s x %2$s %3$s', 160, 160, 'Small Square' ),
				'125x125'  => sprintf( '%1$s x %2$s %3$s', 125, 125, 'Button' ),
				'200x90x4' => sprintf( '%1$s x %2$s %3$s', 200, 90, 'Tall Half Banner 4 Links' ),
				'200x90x5' => sprintf( '%1$s x %2$s %3$s', 200, 90, 'Tall Half Banner 5 Links' ),
				'180x90x4' => sprintf( '%1$s x %2$s %3$s', 180, 90, 'Half Banner 4 Links' ),
				'180x90x5' => sprintf( '%1$s x %2$s %3$s', 180, 90, 'Half Banner 5 Links' ),
				'160x90x4' => sprintf( '%1$s x %2$s %3$s', 160, 90, 'Tall Button 4 Links' ),
				'160x90x5' => sprintf( '%1$s x %2$s %3$s', 160, 90, 'Tall Button 5 Links' ),
				'120x90x4' => sprintf( '%1$s x %2$s %3$s', 120, 90, 'Button 4 Links' ),
				'120x90x5' => sprintf( '%1$s x %2$s %3$s', 120, 90, 'Button 5 Links' )
			);

			self::setSize( $value, $width, $height );
		}

		public function setSize( $value = '', $width = null, $height = null ) {
			if ( $value == '' ) {
				$this->size   = '468x60';
				$this->name   = 'Banner';
				$this->width  = 468;
				$this->height = 60;
			} elseif ( $value == 'custom' ) {
				$this->size   = $value;
				$this->name   = 'Custom size';
				$this->width  = $width;
				$this->height = $height;
			} else {
				$aSize        = explode( "x", $value );
				$this->size   = $value;
				$this->name   = $this->aSizes[ $value ];
				$this->width  = $aSize[0];
				$this->height = $aSize[1];
			}
		}
	}
}

$allowed_actions = array(
	'sam_pro_ajax_load_places',
	'sam_pro_ajax_load_places_ads',
	'sam_pro_ajax_load_places_ads_item',
	'sam_pro_ajax_load_ads',
	'sam_pro_ajax_load_zones',
	'sam_pro_ajax_load_blocks',
	'sam_pro_ajax_load_error_log',
	'sam_pro_ajax_update_place_ad',
	'sam_pro_ajax_trash_place',
	'sam_pro_ajax_trash_ad',
	'sam_pro_ajax_trash_zone',
	'sam_pro_ajax_trash_block',
	'sam_pro_ajax_moderate_ad',
	'sam_pro_ajax_trash_place_ad',
	'sam_pro_ajax_remove_place',
	'sam_pro_ajax_remove_ad',
	'sam_pro_ajax_remove_zone',
	'sam_pro_ajax_remove_block',
	'sam_pro_ajax_remove_error',
	'sam_pro_ajax_lookup_ads',
	'sam_pro_ajax_load_posts',
	'sam_pro_ajax_load_cats',
	'sam_pro_ajax_load_tags',
	'sam_pro_ajax_load_authors',
	'sam_pro_ajax_load_taxes',
	'sam_pro_ajax_load_types',
	'sam_pro_ajax_load_users',
	'sam_pro_ajax_list_ads',
	'sam_pro_ajax_link_ads',
	'sam_pro_ajax_unlink_ad',
	'sam_pro_ajax_unlink_ad_item',
	'sam_pro_ajax_load_ad_objects',
	'sam_pro_ajax_clear_error_log',
	'sam_pro_ajax_solve_error',
	'sam_pro_ajax_load_zone_rules',
	'sam_pro_ajax_update_zone_rule',
	'sam_pro_ajax_remove_zone_rule',
	'sam_pro_ajax_add_zone_rules',
	'sam_pro_ajax_load_zone_single_rules',
	'sam_pro_ajax_update_zone_single_rule',
	'sam_pro_ajax_remove_zone_single_rule',
	'sam_pro_ajax_add_zone_single_rules',
	'sam_pro_ajax_load_regions',
	'sam_pro_ajax_load_ad_chart_data',
	'sam_pro_ajax_load_chart_data',
	'sam_pro_ajax_load_chart_data_all',
	'sam_pro_ajax_load_grid_data',
	'sam_pro_ajax_load_grid_data_all',
	'sam_pro_ajax_load_grid_child_data',
	'sam_pro_ajax_load_grid_child_data_all',
	'sam_pro_ajax_load_pie_data',
	'sam_pro_ajax_load_pie_data_all',
	'sam_pro_ajax_load_pie_data_2',
	'sam_pro_ajax_load_pie_data_2_all',
	'sam_pro_ajax_list_adverts',
	'sam_pro_ajax_list_adverts_ads'
);

if ( in_array( $action, $allowed_actions ) && samProIsValidURL( $options ) ) {
	$limit = samProGetLimit( $params );
	$sort  = samProGetSorted( $params );
	switch ( $action ) {
		case 'sam_pro_ajax_load_places':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'pt.' ) : '' );
			$concat   = "IF(pt.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\") AS stat";
			$sql      = "SELECT pt.pid, pt.title, pt.description, pt.asize AS psize, {$concat}, trash FROM {$pTable} pt{$sqlWhere}{$sort}{$limit};";
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			$apSize   = new SamProSizesLite( '', 0, 0 );
			foreach ( $rows as $key => $value ) {
				$apSize->setSize( $value['psize'], 0, 0 );
				$rows[ $key ]['psize'] = $apSize->name;
			}
			$sql   = "SELECT COUNT(*) FROM {$pTable} pt{$sqlWhere};";
			$count = $wpdb->get_var( $sql );
			$out   = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_load_places_ads':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'wspa.' ) : '' );
			$concat   = "CONCAT(IF((wsa.trash OR (wsa.moderated = 0) OR (wspa.weight = 0)), \"<i class='icon-eye-off'></i>\", \"<i class='icon-eye'></i>\"),
  IF(wsa.moderated = 0, \"<i class='icon-traffic-cone'></i>\", \"<i class='icon-ok'></i>\"),
  IF(wsa.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\")) AS trash";
			$sql      = "SELECT wspa.pid, wspa.aid, wsa.title, wsa.description, wspa.weight, {$concat} FROM {$paTable} wspa INNER JOIN {$aTable} wsa ON wspa.aid = wsa.aid {$sqlWhere}{$sort}{$limit};";
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			$out      = array( 'count' => count( $rows ), 'result' => $rows );
			break;
		case 'sam_pro_ajax_load_places_ads_item':
			$pid = ( isset( $_GET['pid'] ) ) ? (integer) $_GET['pid'] : 0;
			if ( $pid > 0 ) {
				$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'wspa.', true ) : '' );
				$concat   = "CONCAT(IF((wsa.trash OR (wsa.moderated = 0) OR (wspa.weight = 0)), \"<i class='icon-eye-off'></i>\", \"<i class='icon-eye'></i>\"),
  IF(wsa.moderated = 0, \"<i class='icon-traffic-cone'></i>\", \"<i class='icon-ok'></i>\"),
  IF(wsa.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\")) AS trash";
				$sql      = "SELECT wspa.pid, wspa.aid, wsa.title, wsa.description, wspa.weight, {$concat} FROM {$paTable} wspa INNER JOIN {$aTable} wsa ON wspa.aid = wsa.aid WHERE wspa.pid = {$pid}{$sqlWhere}{$sort}{$limit};";
				$rows     = $wpdb->get_results( $sql, ARRAY_A );
				$sql      = "SELECT COUNT(*) FROM {$paTable} wspa WHERE wspa.pid = {$pid};";
				$count    = $wpdb->get_var( $sql );
				$out      = array( 'count' => $count, 'result' => $rows );
			} else {
				$out = array( 'count' => 0, 'result' => array() );
			}
			break;
		case 'sam_pro_ajax_load_ads':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'wat.' ) : '' );
			$concat   = "CONCAT(IF((wat.trash OR (wat.moderated = 0)), \"<i class='icon-eye-off'></i>\", \"<i class='icon-eye'></i>\"),
  IF(wat.moderated = 0, \"<i class='icon-traffic-cone'></i>\", \"<i class='icon-ok'></i>\"),
  IF(wat.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\")) AS stat";
			$sql      = "SELECT wat.aid, wat.title, wat.description, wat.asize, wat.moderated, {$concat}, wat.trash FROM {$aTable} wat{$sqlWhere}{$sort}{$limit};";
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $rows as $key => $value ) {
				$rows[ $key ]['moderated'] = (boolean) $rows[ $key ]['moderated'];
			}
			$sql   = "SELECT COUNT(*) FROM {$aTable} wat{$sqlWhere};";
			$count = $wpdb->get_var( $sql );
			$out   = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_load_zones':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'zt.' ) : '' );
			$concat   = "IF(zt.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\") AS stat";
			$sql      = "SELECT zt.zid, zt.title, zt.description, {$concat}, zt.trash FROM {$zTable} zt{$sqlWhere}{$sort}{$limit};";
			$sss      = $sql;
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			$sql      = "SELECT COUNT(*) FROM {$zTable} zt{$sqlWhere};";
			$count    = $wpdb->get_var( $sql );
			$out      = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_load_blocks':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'bt.' ) : '' );
			$concat   = "IF(bt.b_rows IS NOT NULL AND bt.b_columns IS NOT NULL, CONCAT(bt.b_rows, \" x \", bt.b_columns), '') AS size,";
			$concat .= "IF(bt.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\") AS stat";
			$sql   = "SELECT bt.bid, bt.title, bt.description, {$concat}, bt.trash FROM {$bTable} bt{$sqlWhere}{$sort}{$limit};";
			$sss   = $sql;
			$rows  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(*) FROM {$bTable} bt{$sqlWhere};";
			$count = $wpdb->get_var( $sql );
			$out   = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_load_error_log':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'et.' ) : '' );
			$concat   = "IF(et.etype = 0, IF(et.solved = 0, \"<i class='icon-warning-empty'></i>\", \"<i class='icon-shield'></i>\"), \"<i class='icon-ok'></i>\") AS stype";
			$sql      = "SELECT et.eid, et.edate, et.tname, et.emsg, et.etype, {$concat}, et.esql, et.solved FROM {$eTable} et{$sqlWhere}{$sort}{$limit};";
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			$sql      = "SELECT COUNT(*) FROM {$eTable} et{$sqlWhere};";
			$count    = $wpdb->get_var( $sql );
			$out      = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_lookup_ads':
			$sql  = "SELECT aid, title FROM {$aTable};";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$out  = array( 'count' => count( $rows ), 'result' => $rows );
			break;
		case 'sam_pro_ajax_update_place_ad':
			$update = samProGetUpdateData( $params, $paTable );
			$res    = $wpdb->update( $update['table'], $update['data'], $update['where'], $update['format'], $update['where_format'] );
			$out    = array( "Updated" => array( "weight" => $update['data']['weight'] ) );
			break;
		case 'sam_pro_ajax_trash_place':
			if ( isset( $_POST['pid'] ) && isset( $_POST['trash'] ) ) {
				$pid   = (int) $_POST['pid'];
				$trash = (int) $_POST['trash'];
				$res   = $wpdb->update(
					$pTable,
					array( 'trash' => $trash ),
					array( 'pid' => $pid ),
					array( '%d' ),
					array( '%d' )
				);
				$out   = array( 'updated' => array( 'pid' => $pid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_trash_ad':
			if ( isset( $_POST['aid'] ) && isset( $_POST['trash'] ) ) {
				$aid   = (int) $_POST['aid'];
				$trash = (int) $_POST['trash'];
				$res   = $wpdb->update(
					$aTable,
					array( 'trash' => $trash ),
					array( 'aid' => $aid ),
					array( '%d' ),
					array( '%d' )
				);
				$out   = array( 'updated' => array( 'aid' => $aid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_trash_zone':
			if ( isset( $_POST['zid'] ) && isset( $_POST['trash'] ) ) {
				$zid   = (int) $_POST['zid'];
				$trash = (int) $_POST['trash'];
				$res   = $wpdb->update(
					$zTable,
					array( 'trash' => $trash ),
					array( 'zid' => $zid ),
					array( '%d' ),
					array( '%d' )
				);
				$out   = array( 'updated' => array( 'zid' => $zid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_trash_block':
			if ( isset( $_POST['bid'] ) && isset( $_POST['trash'] ) ) {
				$bid   = (int) $_POST['bid'];
				$trash = (int) $_POST['trash'];
				$res   = $wpdb->update(
					$bTable,
					array( 'trash' => $trash ),
					array( 'bid' => $bid ),
					array( '%d' ),
					array( '%d' )
				);
				$out   = array( 'updated' => array( 'bid' => $bid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_moderate_ad':
			if ( isset( $_POST['aid'] ) && isset( $_POST['mod'] ) ) {
				$aid = (int) $_POST['aid'];
				$mod = (int) $_POST['mod'];
				$res = $wpdb->update(
					$aTable,
					array( 'moderated' => $mod ),
					array( 'aid' => $aid ),
					array( '%d' ),
					array( '%d' )
				);
				$out = array( 'updated' => array( 'aid' => $aid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_trash_place_ad':
			if ( isset( $_POST['pid'] ) && isset( $_POST['trash'] ) && isset( $_POST['aid'] ) ) {
				$pid   = (int) $_POST['pid'];
				$aid   = (int) $_POST['aid'];
				$trash = (int) $_POST['trash'];
				$res   = $wpdb->update(
					$paTable,
					array( 'trash' => $trash ),
					array( 'pid' => $pid, 'aid' => $aid ),
					array( '%d' ),
					array( '%d', '%d' )
				);
				$out   = array( 'updated' => array( 'pid' => $pid, 'aid' => $aid ) );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_place':
			$act = $params['action'];
			if ( $act == 'remove' ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$pTable} WHERE {$col} = {$value};";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_ad':
			$act = $params['action'];
			if ( $act == 'remove' ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$aTable} WHERE {$col} = {$value};";
				$wpdb->query( $sql );

				$sql = "DELETE FROM {$paTable} WHERE {$col} = {$value};";
				$wpdb->query( $sql );

				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_zone':
			$act = $params['action'];
			if ( $act == 'remove' ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$zTable} WHERE {$col} = {$value};";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_block':
			$act = $params['action'];
			if ( $act == 'remove' ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$bTable} WHERE {$col} = {$value};";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_error':
			$act = $params['action'];
			if ( $act == 'remove' ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$eTable} WHERE {$col} = {$value};";

				$out = array( "Deleted" => $value );
				$wpdb->query( $sql );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_posts':
			$custs = ( isset( $_REQUEST['cstr'] ) ) ? $_REQUEST['cstr'] : '';
			$sPost = ( isset( $_REQUEST['sp'] ) ) ? urldecode( $_REQUEST['sp'] ) : 'Post';
			$sPage = ( isset( $_REQUEST['spg'] ) ) ? urldecode( $_REQUEST['spg'] ) : 'Page';

			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'wp.', true ) : '' );

			$sql = "SELECT
                wp.id,
                wp.post_title AS title,
                wp.post_type AS type
              FROM
                $postTable wp
              WHERE
                wp.post_status = 'publish' AND
                FIND_IN_SET(wp.post_type, 'post,page{$custs}')
              {$sqlWhere}{$sort}{$limit};";

			$posts = $wpdb->get_results( $sql, ARRAY_A );

			$k = 0;
			foreach ( $posts as &$val ) {
				switch ( $val['type'] ) {
					case 'post':
						$val['type'] = $sPost;
						break;
					case 'page':
						$val['type'] = $sPage;
						break;
					default:
						$val['type'] = $sPost . ': ' . $val['type'];
						break;
				}
			}

			$sql   = "SELECT COUNT(*) FROM {$postTable} wp WHERE wp.post_status = 'publish' AND FIND_IN_SET(wp.post_type, 'post,page{$custs}');";
			$count = $wpdb->get_var( $sql );
			$out   = array(
				'count'  => $count,
				'result' => $posts
			);
			break;
		case 'sam_pro_ajax_load_cats':
			$sql = "SELECT wt.term_id AS id, wt.name AS title, wt.slug
              FROM $tTable wt
              INNER JOIN $ttTable wtt
                ON wt.term_id = wtt.term_id
              WHERE wtt.taxonomy = 'category'{$sort}{$limit};";

			$cats  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(*) FROM {$tTable} wt INNER JOIN {$ttTable} wtt ON wt.term_id = wtt.term_id WHERE wtt.taxonomy = 'category';";
			$count = $wpdb->get_var( $sql );
			$out   = array(
				'count'  => $count,
				'result' => $cats
			);
			break;
		case 'sam_pro_ajax_load_tags':
			$sql = "SELECT wt.term_id AS id, wt.name AS title, wt.slug
              FROM $tTable wt
              INNER JOIN $ttTable wtt
                ON wt.term_id = wtt.term_id
              WHERE wtt.taxonomy = 'post_tag'{$sort}{$limit};";

			$tags  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(*) FROM {$tTable} wt INNER JOIN {$ttTable} wtt ON wt.term_id = wtt.term_id WHERE wtt.taxonomy = 'post_tag';";
			$count = $wpdb->get_var( $sql );
			$out   = array(
				'count'  => $count,
				'result' => $tags
			);
			break;
		case 'sam_pro_ajax_load_authors':
			$sql   = "SELECT wu.id, wu.display_name AS title, wu.user_nicename AS slug
              FROM {$uTable} wu
              INNER JOIN {$umTable} wum ON wu.id = wum.user_id
              WHERE wum.meta_key = '{$userLevel}' AND wum.meta_value > 1{$sort}{$limit};";
			$auth  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(*) FROM {$uTable} wu INNER JOIN {$umTable} wum ON wu.id = wum.user_id WHERE wum.meta_key = '{$userLevel}' AND wum.meta_value > 1;";
			$count = $wpdb->get_var( $sql );
			$out   = array(
				'count'  => $count,
				'result' => $auth
			);
			break;
		case 'sam_pro_ajax_load_taxes':
			global $wp_taxonomies;
			$sql = "SELECT wt.term_id AS id, wt.name AS title, wt.slug, wtt.taxonomy
              FROM $tTable wt
              INNER JOIN $ttTable wtt
              ON wt.term_id = wtt.term_id
              WHERE NOT FIND_IN_SET(wtt.taxonomy, 'category,post_tag,nav_menu,link_category,post_format'){$sort}{$limit};";

			$cTax  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(*) FROM $tTable wt INNER JOIN $ttTable wtt ON wt.term_id = wtt.term_id WHERE NOT FIND_IN_SET(wtt.taxonomy, 'category,post_tag,nav_menu,link_category,post_format');";
			$count = $wpdb->get_var( $sql );
			foreach ( $cTax as &$val ) {
				if ( isset( $wp_taxonomies[ $val['taxonomy'] ] ) ) {
					$val['ctax_name'] = urldecode( $wp_taxonomies[ $val['taxonomy'] ]->labels->name );
				} else {
					$val['ctax_name'] = '';
				}
			}
			$out = array(
				'count'  => $count,
				'result' => $cTax
			);
			break;
		case 'sam_pro_ajax_load_types':
			$args       = array( 'public' => true, '_builtin' => false );
			$output     = 'objects';
			$operator   = 'and';
			$post_types = get_post_types( $args, $output, $operator );
			$customs    = array();

			foreach ( $post_types as $post_type ) {
				array_push( $customs, array( 'title' => $post_type->labels->name, 'slug' => $post_type->name ) );
			}
			$out = array(
				'count'  => count( $customs ),
				'result' => $customs
			);
			break;
		case 'sam_pro_ajax_load_users':
			$roleSubscriber    = ( isset( $_REQUEST['subscriber'] ) ) ? urldecode( $_REQUEST['subscriber'] ) : 'Subscriber';
			$roleContributor   = ( isset( $_REQUEST['contributor'] ) ) ? urldecode( $_REQUEST['contributor'] ) : 'Contributor';
			$roleAuthor        = ( isset( $_REQUEST['author'] ) ) ? urldecode( $_REQUEST['author'] ) : 'Author';
			$roleEditor        = ( isset( $_REQUEST['editor'] ) ) ? urldecode( $_REQUEST['editor'] ) : 'Editor';
			$roleAdministrator = ( isset( $_REQUEST["admin"] ) ) ? urldecode( $_REQUEST["admin"] ) : 'Administrator';
			$roleSuperAdmin    = ( isset( $_REQUEST['sadmin'] ) ) ? urldecode( $_REQUEST['sadmin'] ) : 'Super Admin';
			$sql               = "SELECT
                wu.id,
                wu.display_name AS title,
                wu.user_nicename AS slug,
                (CASE wum.meta_value
                  WHEN 0 THEN '$roleSubscriber'
                  WHEN 1 THEN '$roleContributor'
                  WHEN 2 THEN '$roleAuthor'
                  ELSE
                    IF(wum.meta_value > 2 AND wum.meta_value <= 7, '$roleEditor',
                      IF(wum.meta_value > 7 AND wum.meta_value <= 10, '$roleAdministrator',
                        IF(wum.meta_value > 10, '$roleSuperAdmin', NULL)
                      )
                    )
                END) AS role
              FROM $uTable wu
              INNER JOIN $umTable wum
                ON wu.id = wum.user_id AND wum.meta_key = '$userLevel'
              {$sort}{$limit};";
			$users             = $wpdb->get_results( $sql, ARRAY_A );
			$sql               = "SELECT COUNT(*) FROM $uTable wu INNER JOIN $umTable wum ON wu.id = wum.user_id AND wum.meta_key = '$userLevel';";
			$count             = $wpdb->get_var( $sql );
			$out               = array(
				'count'  => $count,
				'result' => $users
			);
			break;
		case 'sam_pro_ajax_list_ads':
			$pid = ( isset( $_REQUEST['pid'] ) ) ? (int) $_REQUEST['pid'] : - 1;
			if ( $pid != - 1 ) {
				$sql   = "SELECT aid, title, description, asize FROM {$aTable} WHERE aid NOT IN(SELECT aid FROM {$paTable} WHERE pid = {$pid}){$sort}{$limit};";
				$ads   = $wpdb->get_results( $sql );
				$sql   = "SELECT COUNT(*) FROM {$aTable} WHERE aid NOT IN(SELECT aid FROM {$paTable} WHERE pid = {$pid});";
				$count = $wpdb->get_var( $sql );
				$out   = array(
					'count'  => $count,
					'result' => $ads,
				);
			} else {
				$out = array( 'error' => true );
			}
			break;
		case 'sam_pro_ajax_link_ads':
			$sid    = ( isset( $_REQUEST['sid'] ) ) ? $_REQUEST['sid'] : '';
			$pid    = ( isset( $_REQUEST['pid'] ) ) ? (integer) $_REQUEST['pid'] : 0;
			$values = '';
			if ( $sid != '' && $pid != 0 ) {
				$aValues = explode( ',', $sid );
				foreach ( $aValues as $val ) {
					$values .= ( ( ( $values == '' ) ? '' : ',' ) . "({$pid},{$val})" );
				}
				$sql      = "INSERT INTO {$paTable} (pid, aid) VALUES {$values};";
				$affected = $wpdb->query( $sql );
				$out      = array(
					'sid'  => $sid,
					'done' => $affected
				);
				$sql      = "UPDATE {$paTable} spa SET spa.hits = 0 WHERE spa.pid = {$pid};";
				$wpdb->query( $sql );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_unlink_ad':
			$aid = ( isset( $_REQUEST['aid'] ) ) ? (integer) $_REQUEST['aid'] : 0;
			$pid = ( isset( $_REQUEST['pid'] ) ) ? (integer) $_REQUEST['pid'] : 0;
			if ( $aid > 0 && $pid > 0 ) {
				$sql   = "DELETE FROM {$paTable} WHERE pid = {$pid} AND aid = {$aid};";
				$count = $wpdb->query( $sql );
				$out   = array(
					'success' => true,
					'count'   => $count
				);
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_unlink_ad_item':
			//$aid = (isset($_REQUEST['aid'])) ? (integer)$_REQUEST['aid'] : 0;
			$pid = ( isset( $_REQUEST['pid'] ) ) ? (integer) $_REQUEST['pid'] : 0;
			$act = $params['action'];
			if ( $act == 'remove' && $pid > 0 ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$paTable} WHERE pid = {$pid} AND {$col} = {$value};";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_ad_objects':
			$strPlaces = ( isset( $_REQUEST['pl'] ) ) ? $_REQUEST['pl'] : 'Places';
			$strAds    = ( isset( $_REQUEST['ads'] ) ) ? $_REQUEST['ads'] : 'Single Ads';
			$sql       = "(SELECT '{$strPlaces}' AS cat, CONCAT(0, '_', pid) AS ival, title FROM {$pTable}) UNION (SELECT '{$strAds}' AS cat, CONCAT(1, '_', aid) AS ival, title FROM {$aTable});";
			$ads       = $wpdb->get_results( $sql );
			$count     = count( $ads );
			$out       = $ads;
			break;
		case 'sam_pro_ajax_clear_error_log':
			$sql = "DELETE FROM {$eTable};";
			$res = $wpdb->query( $sql );
			if ( $res === false ) {
				$out = array( 'success' => false );
			} else {
				$out = array( 'success' => true, 'deleted' => $res );
			}
			break;
		case 'sam_pro_ajax_solve_error':
			if ( isset( $_POST['eid'] ) && is_numeric( $_POST['eid'] ) ) {
				$eid = (integer) $_POST['eid'];
				$res = $wpdb->update(
					$eTable,
					array( 'solved' => 1 ),
					array( 'eid' => $eid ),
					array( '%d' ),
					array( '%d' )
				);
				$out = array( 'updated' => array( 'eid' => $eid ), 'success' => true );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_zone_rules':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			if ( $zid > 0 ) {
				$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'szr.', true ) : '' );
				$sql      = "SELECT szr.zid, szr.slug, szr.single, szr.tax, szr.name, szr.term_slug, szr.pid, szr.priority
FROM {$zrTable} szr WHERE szr.single = 0 AND szr.zid = {$zid}{$sqlWhere}{$sort}{$limit};";
				$rows     = $wpdb->get_results( $sql, ARRAY_A );
				$sql      = "SELECT COUNT(*) FROM {$zrTable} szr WHERE szr.single = 0 AND szr.zid = {$zid};";
				$count    = $wpdb->get_var( $sql );

				$out = array( 'count' => $count, 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_update_zone_rule':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			if ( $zid > 0 ) {
				$value = ( ( isset( $params['value'] ) ) ? $params['value'] : null );
				if ( ! is_null( $value ) ) {
					$slug     = $value['slug'];
					$priority = (int) $value['priority'];
					$pid      = (int) $value['pid'];

					$wpdb->update(
						$zrTable,
						array( 'priority' => $priority, 'pid' => $pid ),
						array( 'zid' => $zid, 'slug' => $slug, 'single' => 0 ),
						array( '%d', '%d' ),
						array( '%d', '%s', '%d' )
					);
					$out = array( 'Updated' => array( 'pid' => $pid, 'priority' => $priority ) );
				} else {
					$out = array( 'success' => false );
				}
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_zone_rule':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			$act = $params['action'];
			if ( $act == 'remove' && $zid > 0 ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$zrTable} WHERE zid = {$zid} AND {$col} = '{$value}' AND single = 0;";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_add_zone_rules':
			$zid    = ( isset( $_REQUEST['zid'] ) ) ? (int) $_REQUEST['zid'] : 0;
			$rules  = ( isset( $_REQUEST['rules'] ) ) ? $_REQUEST['rules'] : null;
			$values = '';
			if ( ! is_null( $rules ) && is_array( $rules ) && $zid > 0 ) {
				foreach ( $rules as $val ) {
					$values .= ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zid}, '{$val['slug']}', 0, '{$val['tax']}', '{$val['name']}', '{$val['term_slug']}', {$val['priority']})";
				}
				$sql  = "INSERT IGNORE INTO {$zrTable} (zid, slug, single, tax, name, term_slug, priority) VALUES {$values};";
				$rows = $wpdb->query( $sql );

				$out = array( 'rows' => $rows );
			} else {
				$out = array( 'success' => false, 'zid' => $zid, );
			}
			break;
		case 'sam_pro_ajax_load_zone_single_rules':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			if ( $zid > 0 ) {
				$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'szr.', true ) : '' );
				$sql      = "SELECT szr.zid, szr.slug, szr.single, szr.tax, szr.name, szr.term_slug, szr.pid, szr.priority
FROM {$zrTable} szr WHERE szr.single = 1 AND szr.zid = {$zid}{$sqlWhere}{$sort}{$limit};";
				$rows     = $wpdb->get_results( $sql, ARRAY_A );
				$sql      = "SELECT COUNT(*) FROM {$zrTable} szr WHERE szr.single = 1 AND szr.zid = {$zid};";
				$count    = $wpdb->get_var( $sql );

				$out = array( 'count' => $count, 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_update_zone_single_rule':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			if ( $zid > 0 ) {
				$value = ( ( isset( $params['value'] ) ) ? $params['value'] : null );
				if ( ! is_null( $value ) ) {
					$slug     = $value['slug'];
					$priority = (int) $value['priority'];
					$pid      = (int) $value['pid'];

					$wpdb->update(
						$zrTable,
						array( 'priority' => $priority, 'pid' => $pid ),
						array( 'zid' => $zid, 'slug' => $slug, 'single' => 1 ),
						array( '%d', '%d' ),
						array( '%d', '%s', '%d' )
					);
					$out = array( 'Updated' => array( 'pid' => $pid, 'priority' => $priority ) );
				} else {
					$out = array( 'success' => false );
				}
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_remove_zone_single_rule':
			$zid = ( isset( $_GET['item'] ) ) ? (int) $_GET['item'] : 0;
			$act = $params['action'];
			if ( $act == 'remove' && $zid > 0 ) {
				$value = $params['key'];
				$col   = $params['keyColumn'];
				$sql   = "DELETE FROM {$zrTable} WHERE zid = {$zid} AND {$col} = '{$value}' AND single = 1;";

				$wpdb->query( $sql );
				$out = array( "Deleted" => $value );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_add_zone_single_rules':
			$zid    = ( isset( $_REQUEST['zid'] ) ) ? (int) $_REQUEST['zid'] : 0;
			$rules  = ( isset( $_REQUEST['rules'] ) ) ? $_REQUEST['rules'] : null;
			$values = '';
			if ( ! is_null( $rules ) && is_array( $rules ) && $zid > 0 ) {
				foreach ( $rules as $val ) {
					$values .= ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zid}, '{$val['slug']}', 1, '{$val['tax']}', '{$val['name']}', '{$val['term_slug']}', {$val['priority']})";
				}
				$sql  = "INSERT IGNORE INTO {$zrTable} (zid, slug, single, tax, name, term_slug, priority) VALUES {$values};";
				$rows = $wpdb->query( $sql );

				$out = array( 'rows' => $rows );
			} else {
				$out = array( 'success' => false, 'zid' => $zid, );
			}
			break;
		case 'sam_pro_ajax_load_regions':
			if ( isset( $params['co'] ) ) {
				$valid = true;
				$aco   = explode( ',', $params['co'] );
				foreach ( $aco as $iso ) {
					$valid &= ( strlen( $iso ) == 2 );
				}
				if ( $valid ) {
					$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'sr.', true ) : '' );
					$sql      = "SELECT  sr.iso_1, sr.country, sr.region_name, CONCAT(sr.iso_1, '-', sr.iso_2) AS iso_2, sr.region_type
  FROM {$rTable} sr WHERE FIND_IN_SET(sr.iso_1, '{$params['co']}'){$sqlWhere}{$limit};";
					$rows     = $wpdb->get_results( $sql, ARRAY_A );
					$sql      = "SELECT  COUNT(*) FROM {$rTable} sr WHERE FIND_IN_SET(sr.iso_1, '{$params['co']}') {$sqlWhere};";
					$count    = $wpdb->get_var( $sql );

					$out = array( 'count' => $count, 'result' => $rows );
				} else {
					$out = array( 'success' => false );
				}
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_ad_chart_data':
			if ( isset( $_REQUEST['start'] ) && isset( $_REQUEST['end'] ) && isset( $_REQUEST['aid'] ) && isset( $_REQUEST['pid'] ) && isset( $_REQUEST['period'] ) ) {
				$start  = $_REQUEST['start'];
				$end    = $_REQUEST['end'];
				$aid    = (int) $_REQUEST['aid'];
				$pid    = (int) $_REQUEST['pid'];
				$period = (int) $_REQUEST['period'];

				if ( $period < 20 && $period >= 0 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-%dT00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND ss.pid = {$pid} AND ss.aid = {$aid}
  GROUP BY mdate;";
				} elseif ( $period == 20 || $period == 21 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-01T00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND ss.pid = {$pid} AND ss.aid = {$aid}
  GROUP BY mdate;";
				} else {
					$sql = '';
				}
				if ( ! empty( $sql ) ) {
					$rows = $wpdb->get_results( $sql, ARRAY_A );
				} else {
					$rows = array();
				}
				$out = $rows;
			} else {
				$out = array();
			}
			break;
		case 'sam_pro_ajax_load_chart_data':
			if ( isset( $params['start'] ) && isset( $params['end'] ) && isset( $params['owner'] ) && isset( $params['item'] ) && isset( $params['period'] ) ) {
				$start  = $params['start'];
				$end    = $params['end'];
				$owner  = $params['owner'];
				$item   = (int) $params['item'];
				$period = (int) $params['period'];

				$ownerData   = ( $owner === 'all' ) ? '' : " INNER JOIN {$aTable} sa ON ss.aid = sa.aid";
				$ownerClause = ( $owner === 'all' ) ? '' : " AND sa.owner = '{$owner}'";

				if ( $period < 20 && $period >= 0 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-%dT00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale{$ownerClause}
  GROUP BY mdate;";
				} elseif ( $period == 20 || $period == 21 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-01T00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale{$ownerClause}
  GROUP BY mdate;";
				} else {
					$sql = '';
				}
				if ( ! empty( $sql ) ) {
					$rows = $wpdb->get_results( $sql, ARRAY_A );
				} else {
					$rows = array();
				}
				$out = $rows;
			} else {
				$out = array();
			}
			break;
		case 'sam_pro_ajax_load_chart_data_all':
			if ( isset( $params['start'] ) && isset( $params['end'] ) && isset( $params['owner'] ) && isset( $params['item'] ) && isset( $params['period'] ) ) {
				$start  = $params['start'];
				$end    = $params['end'];
				$owner  = $params['owner'];
				$item   = (int) $params['item'];
				$period = (int) $params['period'];

				if ( $period < 20 && $period >= 0 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-%dT00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}'
  GROUP BY mdate;";
				} elseif ( $period == 20 || $period == 21 ) {
					$sql = "SELECT DATE_FORMAT(ss.edate, '%Y-%m-01T00:00:01') AS mdate, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}'
  GROUP BY mdate;";
				}
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = $rows;
			} else {
				$out = array();
			}
			break;
		case 'sam_pro_ajax_load_grid_data':
			if ( isset( $params['start'] ) && isset( $params['end'] ) && isset( $params['owner'] ) && isset( $params['item'] ) && isset( $params['period'] ) ) {
				$start  = $params['start'];
				$end    = $params['end'];
				$owner  = $params['owner'];
				$item   = (int) $params['item'];
				$period = (int) $params['period'];

				$ownerData   = ( $owner === 'all' ) ? '' : " INNER JOIN {$aTable} sa ON ss.aid = sa.aid";
				$ownerClause = ( $owner === 'all' ) ? '' : " AND sa.owner = '{$owner}'";

				$sql  = "SELECT sss.pid, sss.title, sss.hits, sss.clicks, sss.income,
  CAST(IF(sss.hits = 0, 0, (sss.income/sss.hits)*1000) AS DECIMAL(11,2)) AS cpm,
  CAST(IF(sss.hits = 0, 0, (sss.clicks/sss.hits)*100) AS DECIMAL(7,3)) AS ctr,
  CAST(IF(sss.clicks = 0, 0, sss.income/sss.clicks) AS DECIMAL(11,2)) AS cpc
  FROM
  (SELECT sms.pid, sms.title, SUM(sms.hits) AS hits, SUM(sms.clicks) AS clicks, SUM(sms.price) AS income
    FROM
    ((SELECT
      ss.pid, ss.aid, DATE_FORMAT(ss.edate, '%m') AS mdate, sp.title, IFNULL(SUM(ss.hits), 0) AS hits, IFNULL(SUM(ss.clicks), 0) AS clicks, IFNULL(sp.price, 0) AS price
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
      WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale AND sp.sale_mode = 1 AND ss.aid <> 0{$ownerClause}
      GROUP BY mdate, ss.pid, ss.aid)
    UNION
    (SELECT
      ss.pid, ss.aid, DATE_FORMAT(ss.edate, '%m') AS mdate, sp.title, IFNULL(SUM(ss.hits), 0) AS hits, IFNULL(SUM(ss.clicks), 0) AS clicks, IFNULL(sp.price, 0) AS price
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
      WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale AND sp.sale_mode = 0{$ownerClause}
      GROUP BY mdate, ss.pid)) sms GROUP BY sms.pid) sss
  ORDER BY sss.hits DESC{$limit};";
				$rows = $wpdb->get_results( $sql, ARRAY_A );

				$sql   = "SELECT COUNT(DISTINCT sp.pid)
  FROM {$sTable} ss INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale{$ownerClause};";
				$count = $wpdb->get_var( $sql );
				$out   = array( 'count' => $count, 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_grid_data_all':
			if ( isset( $params['start'] ) && isset( $params['end'] ) && isset( $params['owner'] ) && isset( $params['item'] ) && isset( $params['period'] ) ) {
				$start  = $params['start'];
				$end    = $params['end'];
				$owner  = $params['owner'];
				$item   = (int) $params['item'];
				$period = (int) $params['period'];

				$sql  = "SELECT ss.pid, sp.title, sp.description,
  IF(sp.sale, \"<i class='icon-credit-card'></i>\", \"<i class='icon-leaf'></i>\") AS sold,
  SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  INNER JOIN {$pTable} sp ON ss.pid = sp.pid
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}'
  GROUP BY ss.pid{$limit};";
				$rows = $wpdb->get_results( $sql, ARRAY_A );

				$sql   = "SELECT COUNT(DISTINCT ss.pid) FROM {$sTable} ss WHERE ss.edate BETWEEN '{$start}' AND '{$end}';";
				$count = $wpdb->get_var( $sql );
				$out   = array( 'count' => $count, 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_grid_child_data':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'sss.' ) : '' );
			if ( isset( $_GET['start'] ) && isset( $_GET['end'] ) && isset( $_GET['owner'] ) && isset( $_GET['period'] ) ) {
				$start  = $_GET['start'];
				$end    = $_GET['end'];
				$owner  = $_GET['owner'];
				$period = (int) $_GET['period'];

				$ownerData   = ( $owner === 'all' ) ? '' : " INNER JOIN {$aTable} sa ON spa.aid = sa.aid";
				$ownerClause = ( $owner === 'all' ) ? '' : " AND sa.owner = '{$owner}'";

				$sql  = "SELECT sss.pid, sss.aid, sss.title, sss.hits, sss.clicks, sss.income,
  CAST(IF(sss.hits = 0, 0, (sss.income/sss.hits)*1000) AS DECIMAL(11,2)) AS cpm,
  CAST(IF(sss.hits = 0, 0, (sss.clicks/sss.hits)*100) AS DECIMAL(7,3)) AS ctr,
  CAST(IF(sss.clicks = 0, 0, sss.income/sss.clicks) AS DECIMAL(11,2)) AS cpc
  FROM
  (SELECT sms.pid, sms.aid, sms.title, SUM(sms.hits) AS hits, SUM(sms.clicks) AS clicks, SUM(sms.income) AS income
  FROM
  (SELECT DATE_FORMAT(ss.edate, '%m') AS mdate, ss.pid, ss.aid, sa.title,
    SUM(ss.hits) AS hits,
    SUM(ss.clicks) AS clicks,
    sp.price AS income
    FROM {$sTable} ss
    INNER JOIN {$pTable} sp ON ss.pid = sp.pid
    INNER JOIN {$aTable} sa ON ss.aid = sa.aid
    WHERE  ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale AND sp.sale_mode = 1{$ownerClause}
    GROUP BY mdate, ss.pid, ss.aid
  UNION
  SELECT DATE_FORMAT(ss.edate, '%m') AS mdate, ss.pid, ss.aid, sa.title,
    SUM(ss.hits) AS hits,
    SUM(ss.clicks) AS clicks,
    CAST((sp.price / (SELECT COUNT(*) FROM {$paTable} spa{$ownerData} WHERE spa.pid = ss.pid{$ownerClause})) AS DECIMAL(11,2)) AS income
    FROM {$sTable} ss
    INNER JOIN {$pTable} sp ON ss.pid = sp.pid
    INNER JOIN {$aTable} sa ON ss.aid = sa.aid
    WHERE  ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale AND sp.sale_mode = 0{$ownerClause}
    GROUP BY mdate, ss.pid, ss.aid) sms GROUP BY sms.pid, sms.aid
		ORDER BY sms.aid) sss {$sqlWhere} ORDER BY sss.hits DESC;";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = array( 'count' => count( $rows ), 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_grid_child_data_all':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'ss.', true ) : '' );
			if ( isset( $_GET['start'] ) && isset( $_GET['end'] ) && isset( $_GET['owner'] ) && isset( $_GET['period'] ) ) {
				$start  = $_GET['start'];
				$end    = $_GET['end'];
				$owner  = $_GET['owner'];
				$period = (int) $_GET['period'];

				$sql  = "SELECT ss.pid, ss.aid, sa.title, sa.description, SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks FROM {$sTable} ss
  INNER JOIN {$aTable} sa ON ss.aid = sa.aid
  WHERE ss.aid > 0{$sqlWhere} AND ss.edate BETWEEN '{$start}' AND '{$end}'
  GROUP BY ss.pid, ss.aid";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = array( 'count' => count( $rows ), 'result' => $rows );
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_pie_data':
			if ( isset( $_REQUEST['start'] ) && isset( $_REQUEST['end'] ) && isset( $_REQUEST['owner'] ) && isset( $_REQUEST['item'] ) && isset( $_REQUEST['period'] ) ) {
				$start  = $_REQUEST['start'];
				$end    = $_REQUEST['end'];
				$owner  = $_REQUEST['owner'];
				$item   = (int) $_REQUEST['item'];
				$period = (int) $_REQUEST['period'];

				$ownerData   = ( $owner === 'all' ) ? '' : " INNER JOIN {$aTable} sa ON ss.aid = sa.aid";
				$ownerClause = ( $owner === 'all' ) ? '' : " AND sa.owner = '{$owner}'";

				$sql  = "SELECT sp.title, IFNULL(SUM(ss.hits), 0) AS hits
  FROM {$sTable} ss INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND sp.sale{$ownerClause}
  GROUP BY ss.pid";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = $rows;
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_pie_data_all':
			if ( isset( $_REQUEST['start'] ) && isset( $_REQUEST['end'] ) && isset( $_REQUEST['owner'] ) && isset( $_REQUEST['item'] ) && isset( $_REQUEST['period'] ) ) {
				$start  = $_REQUEST['start'];
				$end    = $_REQUEST['end'];
				$owner  = $_REQUEST['owner'];
				$item   = (int) $_REQUEST['item'];
				$period = (int) $_REQUEST['period'];

				$sql  = "SELECT sp.title, IFNULL(SUM(ss.hits), 0) AS hits
  FROM {$sTable} ss INNER JOIN {$pTable} sp ON ss.pid = sp.pid
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}'
  GROUP BY ss.pid";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = $rows;
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_pie_data_2':
			if ( isset( $_REQUEST['start'] ) && isset( $_REQUEST['end'] ) && isset( $_REQUEST['owner'] ) && isset( $_REQUEST['item'] ) && isset( $_REQUEST['period'] ) ) {
				$start  = $_REQUEST['start'];
				$end    = $_REQUEST['end'];
				$owner  = $_REQUEST['owner'];
				$item   = (int) $_REQUEST['item'];
				$period = (int) $_REQUEST['period'];

				$ownerData   = ( $owner === 'all' ) ? '' : " INNER JOIN {$aTable} sa ON ss.aid = sa.aid";
				$ownerClause = ( $owner === 'all' ) ? '' : " AND sa.owner = '{$owner}'";

				$sql  = "SELECT sp.title, IFNULL(SUM(ss.clicks), 0) AS clicks
  FROM {$sTable} ss INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND ss.clicks AND sp.sale{$ownerClause}
  GROUP BY ss.pid";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = $rows;
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_load_pie_data_2_all':
			if ( isset( $_REQUEST['start'] ) && isset( $_REQUEST['end'] ) && isset( $_REQUEST['owner'] ) && isset( $_REQUEST['item'] ) && isset( $_REQUEST['period'] ) ) {
				$start  = $_REQUEST['start'];
				$end    = $_REQUEST['end'];
				$owner  = $_REQUEST['owner'];
				$item   = (int) $_REQUEST['item'];
				$period = (int) $_REQUEST['period'];

				$sql  = "SELECT sp.title, IFNULL(SUM(ss.clicks), 0) AS clicks
  FROM {$sTable} ss INNER JOIN {$pTable} sp ON ss.pid = sp.pid
  WHERE ss.edate BETWEEN '{$start}' AND '{$end}' AND ss.clicks
  GROUP BY ss.pid";
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				$out  = $rows;
			} else {
				$out = array( 'success' => false );
			}
			break;
		case 'sam_pro_ajax_list_adverts':
			$sql   = "SELECT DISTINCT sa.owner, sa.owner_name FROM {$aTable} sa WHERE sa.owner IS NOT NULL AND sa.owner <> ''{$sort}{$limit};";
			$rows  = $wpdb->get_results( $sql, ARRAY_A );
			$sql   = "SELECT COUNT(DISTINCT sa.owner) FROM {$aTable} sa WHERE sa.owner IS NOT NULL AND sa.owner <> '';";
			$count = $wpdb->get_var( $sql );
			$out   = array( 'count' => $count, 'result' => $rows );
			break;
		case 'sam_pro_ajax_list_adverts_ads':
			$sqlWhere = ( ( $where ) ? samProGetWhere( $where, 'sa.', false, true ) : '' );
			$concat   = "CONCAT(IF((sa.trash OR (sa.moderated = 0) OR (spa.weight = 0)), \"<i class='icon-eye-off'></i>\", \"<i class='icon-eye'></i>\"),
  IF(sa.moderated = 0, \"<i class='icon-traffic-cone'></i>\", \"<i class='icon-ok'></i>\"),
  IF(sa.trash, \"<i class='icon-trash'></i>\", \"<i class='icon-leaf'></i>\")) AS status";
			$sql      = "SELECT DISTINCT sa.owner, sa.aid, sa.title, sa.description, sa.asize, {$concat} FROM {$aTable} sa INNER JOIN {$paTable} spa{$sqlWhere}{$sort};";
			$rows     = $wpdb->get_results( $sql, ARRAY_A );
			$out      = array( 'count' => count( $rows ), 'result' => $rows );
			break;
		default:
			$out = array( 'success' => false );
	}
	echo json_encode( $out );
	wp_die();
} else {
	$out = array( "status" => "error", "message" => "Error" );
}
wp_send_json_error( $out );