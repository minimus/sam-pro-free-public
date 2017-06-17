<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 24.06.2015
 * Time: 5:07
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProBlock' ) ) {
	class SamProBlock {
		private $bid;
		private $crawler;
		private $args;
		private $clauses;
		private $settings;
		private $ajax;

		public $ad;

		public function __construct( $id, $args, $crawler = false, $clauses = null, $ajax = false ) {
			$this->bid     = $id;
			$this->args    = $args;
			$this->crawler = $crawler;
			$this->clauses = $clauses;
			$this->ajax    = $ajax;

			$this->ad = self::buildBlock();
		}

		private function getType( $value ) {
			$types = array( 'place', 'ad', 'zone' );
			$ti    = explode( '_', $value );
			$type  = $ti[0];

			return $types[ $type ];
		}

		private function getId( $value ) {
			$ti = explode( '_', $value );

			return $ti[1];
		}

		private function buildBlock() {
			if ( empty( $this->bid ) ) {
				return '';
			}

			global $wpdb;
			$bTable = $wpdb->prefix . 'sampro_blocks';
			$out    = '';

			$sql   = "SELECT * FROM {$bTable} sb WHERE sb.bid = %d;";
			$block = $wpdb->get_row( $wpdb->prepare( $sql, $this->bid ), ARRAY_A );

			if ( ! empty( $block ) ) {
				$ads        = unserialize( $block['b_data'] );
				$rows       = (int) $block['b_rows'];
				$cols       = (int) $block['b_columns'];
				$bStyle     = str_replace( array( "\r", "\n" ), '', $block['b_style'] );
				$rStyle     = str_replace( array( "\r", "\n" ), '', $block['l_style'] );
				$iStyle     = str_replace( array( "\r", "\n" ), '', $block['i_style'] );
				$blockStyle = ( ! empty( $bStyle ) ) ? "style='{$bStyle}'" : "style='display:flex;flex-direction:column;justify-content:center;'";
				$rowStyle   = ( ! empty( $rStyle ) ) ? $rStyle : 'display:flex;flex-direction:row;flex-wrap:wrap;justify-content:center;margin:0;padding:0;';
				$itemStyle  = ( ! empty( $iStyle ) ) ? "style='display:inline-block; {$iStyle}'" : "style='display:inline-block;'";

				for ( $i = 1; $i <= $rows; $i ++ ) {
					$line = '';
					for ( $j = 1; $j <= $cols; $j ++ ) {
						$id = self::getId( $ads[ $i ][ $j ] );
						switch ( self::getType( $ads[ $i ][ $j ] ) ) {
							case 'place':
								include_once( apply_filters( 'sam_pro_place_module', 'sam-pro-place.php' ) );
								$ad   = new SamProPlace( $id, null, false, $this->crawler, $this->clauses, $this->ajax );
								$item = $ad->ad;
								break;
							case 'ad':
								include_once( 'sam-pro-ad.php' );
								$ad   = new SamProAd( $id, null, false, $this->crawler );
								$item = $ad->ad;
								break;
							case 'zone':
								include_once( 'sam-pro-zone.php' );
								$ad   = new SamProZone( $id, null, false, $this->crawler, $this->clauses, $this->ajax );
								$item = $ad->ad;
								break;
							default:
								$item = '';
								break;
						}
						if ( ! empty( $item ) ) {
							$line .= "<div class='sam-pro-block-item' {$itemStyle}>{$item}</div>";
						}
					}
					if ( ! empty( $line ) ) {
						$out .= "<div class='sam-pro-block-line' style='{$rowStyle}'>{$line}</div>";
					}
				}
				if ( ! empty( $out ) ) {
					$out = "<div class='sam-pro-block' {$blockStyle}>{$out}</div>";
				}
			} else {
				$out = '';
			}

			return $out;
		}
	}
}