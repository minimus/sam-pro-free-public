<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 29.06.2015
 * Time: 10:06
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProZone' ) ) {
	class SamProZone {
		private $zid;
		private $crawler;
		private $args;
		private $clauses;
		private $settings;
		private $ajax;
		private $force;
		private $tags;

		public $ad;
		public $img = null;
		public $link = null;
		public $width = 0;
		public $height = 0;

		public function __construct( $id, $args, $useTags, $crawler = false, $clauses = null, $ajax = false, $force = false ) {
			global $SAM_PRO_Query;

			$this->zid     = $id;
			$this->crawler = $crawler;
			$this->args    = $args;
			$this->tags    = $useTags;
			$this->clauses = ( is_null( $clauses ) ) ? $SAM_PRO_Query : $clauses;
			$this->ajax    = $ajax;
			$this->force   = $force;

			$this->ad = self::buildZone();
		}

		private function buildZone() {
			global $wpdb;
			$zTable  = $wpdb->prefix . 'sampro_zones';
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';

			$single = $this->clauses['single'];
			$where  = ( ( $single ) ? ' AND szr.single = 1 ' : ' AND szr.single = 0 ' ) . $this->clauses['zone'];
			$pid    = ( $single ) ? 'sz.single_id' : 'sz.arc_id';

			/*$sql = "(SELECT szr.pid FROM {$zrTable} szr WHERE szr.zid = %d {$where} ORDER BY szr.priority)
UNION
(SELECT {$pid} FROM {$zTable} sz WHERE sz.zid = %d)
LIMIT 1;";*/

			$sql    = "SELECT uzr.pid FROM
((SELECT szr.pid, szr.priority FROM {$zrTable} szr
  WHERE szr.zid = %d {$where})
UNION
(SELECT {$pid} AS pid, 10000 AS priority FROM {$zTable} sz WHERE sz.zid = %d)
ORDER BY priority) uzr LIMIT 1;";
			$result = $wpdb->get_var( $wpdb->prepare( $sql, $this->zid, $this->zid ) );
			if ( ! is_null( $result ) ) {
				include_once( apply_filters( 'sam_pro_place_module', 'sam-pro-place.php' ) );
				$ad  = new SamProPlace( (int) $result, $this->args, $this->tags, $this->crawler, $this->clauses, $this->ajax, $this->force );
				$out = $ad->ad;
				$this->link   = $ad->link;
				$this->img    = $ad->img;
				$this->width  = $ad->width;
				$this->height = $ad->height;
			} else {
				$out = '';
			}

			return $out;
		}
	}
}