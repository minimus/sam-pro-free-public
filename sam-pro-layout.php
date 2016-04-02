<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 12.07.2015
 * Time: 17:10
 */

function samProDecript($input, $spKey) {
	$txt = base64_decode($input);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = substr($txt, 0, $iv_size);
	$txt = substr($txt, $iv_size);
	$key = pack('H*', $spKey);
	$plaintext = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $txt, MCRYPT_MODE_CBC, $iv);
	$clauses = unserialize($plaintext);

	return $clauses;
}

define( 'DOING_AJAX', true );

if ( ! isset( $_REQUEST['action'] ) ) {
	die( '-1' );
}

ini_set( 'html_errors', 0 );
define( 'SHORTINIT', true );

$wap = (isset($_REQUEST['wap'])) ? base64_decode($_REQUEST['wap']) : null;
$wpLoadPath = (is_null($wap)) ? false : $wap;

if(!$wpLoadPath) die('-1');

require_once( $wpLoadPath );
require_once( ABSPATH . WPINC . '/formatting.php' );
require_once( ABSPATH . WPINC . '/link-template.php' );

/** @see wp_plugin_directory_constants() */
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}
if ( ! defined( 'WP_PLUGIN_URL' ) ) {
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
}

if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );
}
if ( ! defined( 'WPMU_PLUGIN_URL' ) ) {
	define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL . '/mu-plugins' );
}
global $wp_plugin_paths;
$wp_plugin_paths = array();

if ( ! defined( 'SAM_PRO_URL' ) ) {
	define( 'SAM_PRO_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'SAM_PRO_IMG_URL' ) ) {
	define( 'SAM_PRO_IMG_URL', SAM_PRO_URL . 'images/' );
}

global $wpdb;

$oTable  = $wpdb->prefix . 'options';
$oSql    = "SELECT ot.option_value FROM {$oTable} ot WHERE ot.option_name = 'blog_charset'";
$charset = $wpdb->get_var( $oSql );

$sTable = $wpdb->prefix . 'sampro_stats';
$paTable = $wpdb->prefix . 'sampro_places_ads';

//Typical headers
@header( "Content-Type: application/json; charset={$charset}" );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

define('SAM_PRO_OPTIONS_NAME', 'samProOptions');
$options = get_option(SAM_PRO_OPTIONS_NAME);
$spKey = $options['spkey'];

if( ! function_exists('sanitize_option') ) {
	function sanitize_option( $option, $value ) {
		return $value;
	}
}

$action = ! empty( $_POST['action'] ) ? 'sam_ajax_' . stripslashes( $_POST['action'] ) : false;

$allowed_actions = array(
	'sam_ajax_load_ads',
	'sam_ajax_sam_hits',
	'sam_ajax_sam_click'
);

if( $action && in_array( $action, $allowed_actions ) ) {
	switch($action) {
		case 'sam_ajax_load_ads':
			if ( ( isset( $_POST['ads'] ) && is_array( $_POST['ads'] ) ) && isset( $_POST['data'] ) ) {
				$clauses = samProDecript($_POST['data'], $spKey);
				$places = $_POST['ads'];
				$ads = array();
				$ad = null;
				include_once('sam-pro-place.php');
				foreach($places as $value) {
					$pid   = $value['pid'];
					$aid      = $value['aid'];
					$codes     = $value['codes'];
					$eid = $value['eid'];

					if($aid == 0) {
						$ad = new SamProPlace($pid, null, $codes, false, $clauses, true);
					}
					array_push($ads, array(
						'ad' => $ad->ad,
						'aid' => $ad->aid,
						'pid' => $ad->pid,
						'cid' => $ad->cid,
						'eid' => $eid,
						'sql' => $ad->sql
					));
				}
				$out = $ads;
			}
			else $out = 'Bad input data!';
			break;
		case 'sam_ajax_sam_click':
			if(isset($_POST['aid']) && isset($_POST['pid'])) {
				$aid = (int)$_POST['aid'];
				$pid = (int)$_POST['pid'];
				$values = "(CURDATE(), {$pid}, {$aid}, 1)";
				$sql = "INSERT INTO {$sTable}(edate, pid, aid, hits) VALUES (CURDATE(), {$pid}, {$aid}, 1) ON DUPLICATE KEY UPDATE clicks = clicks + 1;";
				$res = $wpdb->query($sql);
				if(false === $res) $out = 'Something went wrong...';
				else $out = array('data' => $res);
			}
			else $out = 'Bad input data!';
			break;
		case 'sam_ajax_sam_hits':
			$hits = $_POST['hits'];
			$values = '';
			$vals = array();
			$stats = 0;
			$links = 0;
			$sql0 = $sql1 = '';
			if(!empty($hits) && is_array($hits)) {
				foreach($hits as $hit) {
					$values .= (((empty($values)) ? '' : ', ') . "(CURDATE(), {$hit['pid']}, {$hit['aid']}, 1)");
					if(isset($vals[$hit['pid']]))	array_push($vals[(int)$hit['pid']], (int)$hit['aid']);
					else $vals[$hit['pid']] = array($hit['aid']);
				}
				if(!empty($values)) {
					$sql = "INSERT INTO {$sTable}(edate, pid, aid, hits) VALUES {$values} ON DUPLICATE KEY UPDATE hits = hits + 1;";
					$stats = $wpdb->query($sql);
				}
				if(!empty($vals)) {
					foreach($vals as $key => $val) {
						$aidsSet = (1 == count($val)) ? '= ' . $val[0] : 'IN (' . implode(',', $val) . ')';
						$sql = "UPDATE {$paTable} SET hits = hits + 1 WHERE pid = {$key} AND aid {$aidsSet};";
						$links += $wpdb->query($sql);
					}
				}

				$out = array('vals' => $vals, 'stats' => $stats, 'links' => $links);
			}
			else $out = "Bad input data";
			break;
		default: $out = 'Bad request.';
	}
	if(is_array($out)) wp_send_json_success($out);
	else wp_send_json_error( $out );
}
else {
	$out = '';
	wp_send_json_error( $out );
}