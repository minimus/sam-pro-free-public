<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 25.04.2015
 * Time: 5:42
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists( 'SamProUpdater' ) ) {
	class SamProUpdater {
		private $dbVer;
		private $settings;

		public function __construct( $dbVersion, $options ) {
			$this->dbVer = $dbVersion;
			$this->settings = $options;
		}

		private function writeErrorLog( $table, $result, $error, $sql = '' ) {
			global $wpdb;
			$eTable = $wpdb->prefix . 'sampro_errors';
			$data = array(
				'edate' => current_time('mysql'),
				'tname' => $table,
				'etype' => (($result === true) ? 1 : 0),
				'emsg' => ((empty($error)) ? (($result === true) ? __('Updated...', SAM_PRO_DOMAIN) : __('An error occurred during updating process...', SAM_PRO_DOMAIN)) : $error),
				'esql' => $sql,
				'solved' => 0
			);
			$format = array('%s', '%s', '%d', '%s', '%s', '%d');
			$wpdb->insert($eTable, $data, $format);
		}

		public function check( $tablesDefinition ) {
			global $wpdb;

			$eTable  = $wpdb->prefix . 'sampro_errors';
			$tables = array(
				$wpdb->prefix . 'sampro_ads',
				$wpdb->prefix . 'sampro_places',
				$wpdb->prefix . 'sampro_places_ads',
				$wpdb->prefix . 'sampro_zones',
				$wpdb->prefix . 'sampro_zones_rules',
				$wpdb->prefix . 'sampro_blocks',
				$wpdb->prefix . 'sampro_stats'
			);

			if($wpdb->get_var("SHOW TABLES LIKE '$eTable'") != $eTable) self::createTable($eTable);
			else {
				$ts = self::getTableStruct($eTable);
				$sql = self::checkTableStruct($eTable, $ts, $tablesDefinition[$eTable]);
				if(!empty($sql)) $wpdb->query($sql);
			}

			foreach( $tables as $table ) {
				if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
					$result = self::createTable( $table );
					//self::writeErrorLog( $table, $result, ( ( false === $result ) ? $wpdb->last_error : '' ), __('Something went wrong...', SAM_PRO_DOMAIN) );
				} else {
					$ts  = self::getTableStruct( $table );
					$sql = self::checkTableStruct( $table, $ts, $tablesDefinition[ $table ] );
					if ( ! empty( $sql ) ) {
						$result = $wpdb->query( $sql );
						self::writeErrorLog( $table, $result, ( ( false === $result ) ? $wpdb->last_error : '' ), $sql );
					}
				}
			}
		}

		public function getTableStruct( $table, $debug = false ) {
			global $wpdb;
			$ts = array();
			$tableStruct = '';

			$ct = $wpdb->get_results("DESCRIBE $table;", ARRAY_A);
			foreach($ct as $val) {
				$ts[$val['Field']] = array(
					'Type' => $val['Type'],
					'Null' => $val['Null'],
					'Key' => $val['Key'],
					'Default' => $val['Default'],
					'Extra' => $val['Extra']
				);
			}

			if($debug) {
				$tableStruct = "array(\n";
				foreach($ts as $key => $field) {
					$tableStruct .= "'{$key}' => array(";
					$tableStruct .= "'Type' => '{$field['Type']}', ";
					$tableStruct .= "'Null' => '{$field['Null']}', ";
					$tableStruct .= "'Key' => '{$field['Key']}', ";
					$tableStruct .= "'Default' => '{$field['Default']}', ";
					$tableStruct .= "'Extra' => '{$field['Extra']}'";
					$tableStruct .= "),\n";
				}
				$tableStruct .= ");";
			}

			if($debug) return $tableStruct;
			else return $ts;
		}

		public function checkTableStruct( $table, $struct, $default ) {
			global $wpdb, $charset_collate;
			$dbv = $this->dbVer;
			$add = '';
			$modify = '';
			$out = '';
			$change = '';

			foreach($default as $key => $val) {
				if(empty($struct[$key]))
					$add .= ((empty($add)) ? '' : ', ')
					        . $key . ' ' . $val['Type']
					        . (($val['Null'] == 'NO') ? ' NOT NULL' : '')
					        . ((empty($val['Default'])) ? '' : ' DEFAULT ' . (($val['Extra'] == 'str') ? "'{$val['Default']}'" : $val['Default']));
				elseif($struct[$key]['Type'] != $val['Type'])
					$modify .= ((empty($modify)) ? '' : ', ')
					           . 'MODIFY ' . $key . ' ' . $val['Type']
					           . (($val['Null'] == 'NO') ? ' NOT NULL' : '');
			}
			$add = (!empty($add)) ? "ADD ($add)" : '';

			if(!empty($change) && !empty($add)) $add = ', ' . $add;
			if((!empty($add) || !empty($change)) && !empty($modify)) $modify = ', ' . $modify;

			if(!empty($add) || !empty($modify) || !empty($change))
				$out = "ALTER TABLE $table $change $add $modify;";

			return $out;
		}

		public function createTable( $table ) {
			global $wpdb;

			$eTable  = $wpdb->prefix . 'sampro_errors';
			$pTable  = $wpdb->prefix . 'sampro_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$aTable  = $wpdb->prefix . 'sampro_ads';
			$zTable  = $wpdb->prefix . 'sampro_zones';
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';
			$bTable  = $wpdb->prefix . 'sampro_blocks';
			$sTable  = $wpdb->prefix . 'sampro_stats';

			$charset = $wpdb->get_charset_collate();

			switch($table) {
				case $eTable:
					$sql = "CREATE TABLE {$eTable} (
  eid int(11) NOT NULL AUTO_INCREMENT,
  edate datetime DEFAULT NULL,
  tname varchar(30) DEFAULT NULL,
  etype int(1) NOT NULL DEFAULT 0,
  emsg varchar(255) DEFAULT NULL,
  esql text DEFAULT NULL,
  solved tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (eid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Error Log';";
					break;
				case $pTable:
					$sql = "CREATE TABLE {$pTable} (
  pid int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  aid int(11) UNSIGNED NOT NULL DEFAULT 0,
  title varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  sale tinyint(1) DEFAULT 0,
  sale_mode tinyint(1) UNSIGNED DEFAULT 0,
  price decimal(15, 2) UNSIGNED DEFAULT 0.00,
  sdate datetime DEFAULT NULL,
  fdate datetime DEFAULT NULL,
  code_before varchar(255) DEFAULT NULL,
  code_after varchar(255) DEFAULT NULL,
  asize varchar(255) DEFAULT NULL,
  width int(11) UNSIGNED DEFAULT NULL,
  height int(11) UNSIGNED DEFAULT NULL,
  img varchar(255) DEFAULT NULL,
  link varchar(255) DEFAULT NULL,
  alt varchar(255) DEFAULT NULL,
  rel tinyint(4) UNSIGNED DEFAULT 0,
  acode text DEFAULT NULL,
  inline tinyint(1) DEFAULT 0,
  php tinyint(1) DEFAULT 0,
  ad_server tinyint(1) DEFAULT 0,
  dfp varchar(255) DEFAULT NULL,
  amode tinyint(3) UNSIGNED DEFAULT 0,
  hits int(11) UNSIGNED DEFAULT 0,
  clicks tinyint(1) DEFAULT 0,
  trash tinyint(1) DEFAULT 0,
  PRIMARY KEY (pid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Ads Places table';";
					break;
				case $paTable:
					$sql = "CREATE TABLE {$paTable} (
  pid int(11) UNSIGNED NOT NULL,
  aid int(11) UNSIGNED NOT NULL,
  weight tinyint(3) UNSIGNED DEFAULT 10,
  hits int(11) UNSIGNED DEFAULT 0,
  trash tinyint(1) DEFAULT 0,
  PRIMARY KEY (pid, aid),
  CONSTRAINT FK_{$wpdb->prefix}places_ads FOREIGN KEY (pid)
  REFERENCES {$pTable} (pid) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Ads Places contained ads';";
					break;
				case $aTable:
					$sql = "CREATE TABLE {$aTable} (
  aid int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  title varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  moderated tinyint(4) UNSIGNED DEFAULT 0,
  img varchar(255) DEFAULT NULL,
  link varchar(255) DEFAULT NULL,
  alt varchar(255) DEFAULT NULL,
  adaptivity tinyint(4) UNSIGNED DEFAULT 0,
  swf tinyint(1) UNSIGNED DEFAULT 0,
  swf_vars text DEFAULT NULL,
  swf_params text DEFAULT NULL,
  swf_attrs text DEFAULT NULL,
  swf_fallback text DEFAULT NULL,
  acode text DEFAULT NULL,
  php tinyint(3) UNSIGNED DEFAULT 0,
  html5 tinyint(1) UNSIGNED DEFAULT 0,
  inline tinyint(1) DEFAULT 0,
  amode tinyint(3) UNSIGNED DEFAULT 0,
  hits tinyint(3) UNSIGNED DEFAULT 0,
  clicks tinyint(3) UNSIGNED DEFAULT 0,
  rel tinyint(4) UNSIGNED DEFAULT 0,
  asize varchar(255) DEFAULT NULL,
  width int(11) UNSIGNED DEFAULT 0,
  height int(11) UNSIGNED DEFAULT 0,
  price decimal(15, 2) UNSIGNED DEFAULT 0.00,
  ptype tinyint(3) UNSIGNED DEFAULT 0,
  ptypes int(11) UNSIGNED DEFAULT 0,
  eposts tinyint(1) UNSIGNED DEFAULT 0,
  xposts tinyint(1) UNSIGNED DEFAULT 0,
  posts text DEFAULT NULL,
  ecats tinyint(1) UNSIGNED DEFAULT 0,
  xcats tinyint(1) UNSIGNED DEFAULT 0,
  cats text DEFAULT NULL,
  etags tinyint(1) UNSIGNED DEFAULT 0,
  xtags tinyint(1) UNSIGNED DEFAULT 0,
  tags text DEFAULT NULL,
  eauthors tinyint(1) UNSIGNED DEFAULT 0,
  xauthors tinyint(1) UNSIGNED DEFAULT 0,
  authors text DEFAULT NULL,
  etax tinyint(1) UNSIGNED DEFAULT 0,
  xtax tinyint(1) UNSIGNED DEFAULT 0,
  taxes text DEFAULT NULL,
  etypes tinyint(1) UNSIGNED DEFAULT 0,
  xtypes tinyint(1) UNSIGNED DEFAULT 0,
  types text DEFAULT NULL,
  schedule tinyint(1) DEFAULT 0,
  sdate datetime DEFAULT NULL COMMENT 'Ad Start Date',
  fdate datetime DEFAULT NULL COMMENT 'Ad Finish Date',
  limit_hits tinyint(1) UNSIGNED DEFAULT 0,
  hits_limit int(11) UNSIGNED DEFAULT 0,
  hits_period tinyint(4) UNSIGNED DEFAULT 0,
  limit_clicks tinyint(1) UNSIGNED DEFAULT 0,
  clicks_limit int(11) UNSIGNED DEFAULT 0,
  clicks_period tinyint(4) UNSIGNED DEFAULT 0,
  users tinyint(1) UNSIGNED DEFAULT 0,
  users_unreg tinyint(1) UNSIGNED DEFAULT 0,
  users_reg tinyint(1) UNSIGNED DEFAULT 0,
  xusers tinyint(1) UNSIGNED DEFAULT 0,
  xvusers text DEFAULT NULL,
  advertiser tinyint(1) UNSIGNED DEFAULT 1,
  geo tinyint(1) UNSIGNED DEFAULT 0,
  xgeo tinyint(1) UNSIGNED DEFAULT 0,
  geo_country text DEFAULT NULL,
  geo_region text DEFAULT NULL,
  geo_city text DEFAULT NULL,
  owner varchar(50) DEFAULT NULL,
  owner_name varchar(50) DEFAULT NULL,
  owner_mail varchar(50) DEFAULT NULL,
  ppm decimal(10, 2) UNSIGNED DEFAULT 0.00,
  ppc decimal(10, 2) UNSIGNED DEFAULT 0.00,
  ppi decimal(10, 2) UNSIGNED DEFAULT 0.00,
  trash tinyint(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (aid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Ads';";
					break;
				case $zTable:
					$sql = "CREATE TABLE {$zTable} (
  zid int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  title varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  single_id int(11) UNSIGNED DEFAULT NULL,
  arc_id int(11) UNSIGNED DEFAULT NULL,
  trash tinyint(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (zid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Zones';";
					break;
				case $zrTable:
					$sql = "CREATE TABLE {$zrTable} (
  zid int(10) UNSIGNED NOT NULL,
  slug varchar(150) NOT NULL DEFAULT '',
  single tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  tax varchar(255) DEFAULT NULL,
  name varchar(255) DEFAULT NULL,
  term_slug varchar(255) DEFAULT NULL,
  pid int(11) UNSIGNED DEFAULT 0,
  priority int(4) UNSIGNED DEFAULT 1,
  PRIMARY KEY (zid, slug, single),
  CONSTRAINT FK_{$wpdb->prefix}zones_rules FOREIGN KEY (zid)
  REFERENCES {$zTable} (zid) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Rules for Zones';";
					break;
				case $bTable:
					$sql = "CREATE TABLE {$bTable} (
  bid int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  title varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  b_rows int(5) UNSIGNED DEFAULT 1,
  b_columns int(5) UNSIGNED DEFAULT 1,
  b_data text DEFAULT NULL,
  b_style text DEFAULT NULL,
  i_style text DEFAULT NULL,
  trash tinyint(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (bid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Blocks';";
					break;
				case $sTable:
					$sql = "CREATE TABLE {$sTable} (
  edate date NOT NULL,
  pid int(11) UNSIGNED NOT NULL,
  aid int(11) UNSIGNED NOT NULL,
  hits int(11) UNSIGNED DEFAULT 0,
  clicks int(11) UNSIGNED DEFAULT 0,
  PRIMARY KEY (edate, pid, aid)
)
ENGINE = INNODB
{$charset}
COMMENT = 'SAM Pro Simple Statistical Data';";
					break;
				default:
					$sql = '';
					break;
			}

			if(!empty($sql)) {
				//include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				//dbDelta($sql);
				$res = $wpdb->query($sql);
				self::writeErrorLog( $table, $res, ( ( false === $res ) ? $wpdb->last_error : sprintf(__("Table %s successfully created...", SAM_PRO_DOMAIN), $table )), $sql );
			}
			else $res = false;

			return $res;
		}
	}
}