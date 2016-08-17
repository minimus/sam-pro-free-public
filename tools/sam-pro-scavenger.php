<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 01.08.2015
 * Time: 12:57
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( ! class_exists( 'SamProScavenger' ) ) {
	class SamProScavenger {
		private $settings;

		public function __construct( $settings ) {
			$this->settings = $settings;
		}

		private function writeResult( $input, $table, $sql ) {
			if(0 === $input) return;

			global $wpdb;
			$eTable = $wpdb->prefix . "sampro_errors";

			$wpdb->insert(
				$eTable,
				array(
					'edate' => current_time('mysql'),
					'tname' => (($input === false) ? $table : "Scavenger"),
					'etype' => 1,
					'emsg' => (($input === false) ? $wpdb->last_error : __('Expired ads were scavenged...', SAM_PRO_DOMAIN)),
					'esql' => (($input === false) ? $sql : sprintf(_n('One expired ad was successfully scavenged.', '%s expired ads were successfully scavenged.', $input, SAM_PRO_DOMAIN), $input)),
					'solved' => 0
				),
				array('%s', '%s', '%d', '%s', '%s', '%d')
			);
		}

		public function scavenge() {
			global $wpdb;
			$aTable = $wpdb->prefix . 'sampro_ads';
			$pTable = $wpdb->prefix . 'sampro_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';

			if($this->settings['moveExpired']) {
				$sql = "UPDATE {$aTable} sa SET sa.trash = 1 WHERE sa.schedule AND sa.fdate < NOW();";
			}

			if(isset($this->settings['keepExpired']) && $this->settings['keepExpired'] < 5) {
				$interval = $this->settings['keepExpired'];

				// Scavenge Links
				$sql = "DELETE FROM {$paTable} WHERE aid IN (SELECT sa.aid FROM {$aTable} sa WHERE sa.schedule AND sa.fdate < DATE_SUB(NOW(), INTERVAL {$interval} MONTH));";
				$res = $wpdb->query($sql);
				self::writeResult($res, $aTable, $sql);
				// Scavenge Ads
				$sql = "DELETE FROM {$aTable} WHERE schedule AND fdate < DATE_SUB(NOW(), INTERVAL {$interval} MONTH);";
				$res = $wpdb->query($sql);
				self::writeResult($res, $aTable, $sql);
			}
		}
	}
}