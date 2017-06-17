<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 04.11.2016
 * Time: 10:58
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProFrontActions' ) ) {
	class SamProFrontActions {
		private $action;

		public function __construct( $action ) {
			$this->action = $action;
		}

		public function decrypt( $input, $spKey, $spiv ) {
			$txt       = base64_decode( $input );
			$key       = pack( 'H*', $spKey );
			$iv        = pack( 'H*', $spiv );
			$plaintext = openssl_decrypt( $txt, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv );
			$clauses   = unserialize( $plaintext );

			return $clauses;
		}

		public function doAction() {
			if ( is_null( $this->action ) ) {
				wp_die( 'Rejected. Action Handler' );
			}

			$charset = get_option( 'blog_charset' );
			header( "Content-Type: application/json; charset={$charset}" );

			global $wpdb;
			$sTable  = $wpdb->prefix . 'sampro_stats';
			$paTable = $wpdb->prefix . 'sampro_places_ads';

			$options = get_option( SAM_PRO_OPTIONS_NAME );
			$spKey   = $options['spkey'];
			$spiv    = $options['spiv'];

			$action = 'sam_ajax_' . $this->action;

			switch ( $action ) {
				case 'sam_ajax_sam_load_ads':
					if ( ( isset( $_POST['ads'] ) && is_array( $_POST['ads'] ) ) && isset( $_POST['data'] ) ) {
						$clauses = self::decrypt( $_POST['data'], $spKey, $spiv );
						$places  = $_POST['ads'];
						$ads     = array();
						$ad      = null;
						include_once( 'sam-pro-place.php' );
						foreach ( $places as $value ) {
							$pid   = (int) $value['pid'];
							$aid   = (int) $value['aid'];
							$codes = $value['codes'];
							$eid   = $value['eid'];

							if ( $aid == 0 ) {
								$ad = new SamProPlace( $pid, null, $codes, false, $clauses, true );
							}
							array_push( $ads, array(
								'ad'  => $ad->ad,
								'aid' => $ad->aid,
								'pid' => $ad->pid,
								'cid' => $ad->cid,
								'eid' => $eid
							) );
						}
						$out = $ads;
					} else {
						$out = 'Bad input data!';
					}
					break;
				case 'sam_ajax_sam_click':
					if ( isset( $_POST['aid'] ) && isset( $_POST['pid'] ) ) {
						$aid    = (int) $_POST['aid'];
						$pid    = (int) $_POST['pid'];
						$values = "(CURDATE(), {$pid}, {$aid}, 1)";
						$sql    = "INSERT INTO {$sTable}(edate, pid, aid, hits) VALUES (CURDATE(), {$pid}, {$aid}, 1) ON DUPLICATE KEY UPDATE clicks = clicks + 1;";
						$res    = $wpdb->query( $sql );
						if ( false === $res ) {
							$out = 'Something went wrong...';
						} else {
							$out = array( 'data' => $res );
						}
					} else {
						$out = 'Bad input data!';
					}
					break;
				case 'sam_ajax_sam_hits':
					$hits   = $_POST['hits'];
					$values = '';
					$vals   = array();
					$stats  = 0;
					$links  = 0;
					if ( ! empty( $hits ) && is_array( $hits ) ) {
						foreach ( $hits as $hit ) {
							$values .= ( ( ( empty( $values ) ) ? '' : ', ' ) . "(CURDATE(), {$hit['pid']}, {$hit['aid']}, 1)" );
							if ( isset( $vals[ $hit['pid'] ] ) ) {
								array_push( $vals[ (int) $hit['pid'] ], (int) $hit['aid'] );
							} else {
								$vals[ $hit['pid'] ] = array( $hit['aid'] );
							}
						}
						if ( ! empty( $values ) ) {
							$sql   = "INSERT INTO {$sTable}(edate, pid, aid, hits) VALUES {$values} ON DUPLICATE KEY UPDATE hits = hits + 1;";
							$stats = $wpdb->query( $sql );
						}
						if ( ! empty( $vals ) ) {
							foreach ( $vals as $key => $val ) {
								$aidsSet = ( 1 == count( $val ) ) ? '= ' . $val[0] : 'IN (' . implode( ',', $val ) . ')';
								$sql     = "UPDATE {$paTable} SET hits = hits + 1 WHERE pid = {$key} AND aid {$aidsSet};";
								$links   += $wpdb->query( $sql );
							}
						}

						$out = array( 'vals' => $vals, 'stats' => $stats, 'links' => $links );
					} else {
						$out = "Bad input data";
					}
					break;
				default:
					$out = 'Bad request.';
			}

			if ( is_array( $out ) ) {
				wp_send_json_success( $out );
			} else {
				wp_send_json_error( $out );
			}
		}
	}
}