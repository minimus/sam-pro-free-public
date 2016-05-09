<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 21.12.2014
 * Time: 6:28
 */
global $samProAddonsList;
if ( class_exists( 'SamProCore' ) && ! class_exists( 'SamProAdmin' ) ) {
	class SamProAdmin extends SamProCore {
		private $rules;

		public $settingsTabs;
		public $settingsPage;
		public $placesPage;
		public $adsPage;
		public $zonesPage;
		public $adEditor;
		public $placeEditor;
		public $zoneEditor;
		public $blocksPage;
		public $blockEditor;
		public $errorLogPage;
		public $toolsPage;
		public $statsPage;
		public $advertisersList;
		public $currentPage;
		public $adsObjects;
		public $level;

		public function __construct() {
			parent::__construct();

			global $sam_pro_tables_definition, $samProAddonsList;

			if ( function_exists( 'load_plugin_textdomain' ) ) {
				load_plugin_textdomain( SAM_PRO_DOMAIN, false, basename( SAM_PRO_PATH ) . '/langs/' );
			}

			$samProAddonsList = array();

			$options = parent::getSettings( false );
			if ( ! empty( $options['access'] ) ) {
				$access = $options['access'];
			} else {
				$access = 'manage_options';
			}

			define( 'SAM_PRO_ACCESS', $access );
			self::setTablesDefinition();

			register_activation_hook( SAM_PRO_MAIN_FILE, array( &$this, 'onActivate' ) );
			register_deactivation_hook( SAM_PRO_MAIN_FILE, array( &$this, 'onDeactivate' ) );
			register_uninstall_hook( SAM_PRO_MAIN_FILE, array( __CLASS__, 'onUninstall' ) );

			$ea_url = admin_url('admin.php') . '?page=sam-pro-ad-editor';
			$ep_url = admin_url('admin.php') . '?page=sam-pro-place-editor';
			if(isset($_SERVER['HTTP_REFERER'])) {
				$mua = strpos($_SERVER['HTTP_REFERER'], $ea_url);
				$mup = strpos($_SERVER['HTTP_REFERER'], $ep_url);
				$mu = ($mua !== false || $mup !== false);
				if($mu) {
					add_filter( 'upload_dir', array( &$this, 'uploadDir' ), 9999 );
				}
			}

			$this->adsObjects = self::getAdsData();

			add_action('wp_loaded', array(&$this, 'onWpLoaded'));
			add_action( 'admin_menu', array( &$this, 'regAdminPage' ) );
			add_action('init', array(&$this, 'addButtons'));

			add_action('admin_init', array(&$this, 'checkCachePlugins'));
			add_action('admin_init', array(&$this, 'checkBbpForum'));
			add_action('admin_init', array(&$this, 'checkWPtouch'));
			add_action( 'admin_init', array( &$this, 'initSettings' ), 12 );

			add_action('add_meta_boxes', array(&$this, 'addMetaBoxes'));
			add_action('save_post', array(&$this, 'savePost'));

			include_once('sam-pro-updater.php');
			$updater = new SamProUpdater($this->samVersions['db'], $options);
			$updater->check($sam_pro_tables_definition);
		}

		public function uploadDir( $param ) {
			$samProDir = 'sam-pro-images';
			$param['path'] = trailingslashit( $param['basedir'] ) . $samProDir;
			$param['url'] = trailingslashit( $param['baseurl'] ) . $samProDir;
			$param['subdir'] = '/' . $samProDir;

			return $param;
		}

		public function onActivate() {
			$settings = parent::getSettings( true );
			update_option( SAM_PRO_OPTIONS_NAME, $settings );
			self::upgradeDB();
		}

		public function onDeactivate() {
			global $wpdb;

			$tables   = implode( ',', array(
				$wpdb->prefix . "sampro_errors",
				$wpdb->prefix . "sampro_places_ads",
				$wpdb->prefix . "sampro_places",
				$wpdb->prefix . "sampro_ads",
				$wpdb->prefix . "sampro_blocks",
				$wpdb->prefix . "sampro_stats"
			) );
			$settings = parent::getSettings();

			if ( $settings['deleteOptions'] == 1 ) {
				delete_option( SAM_PRO_OPTIONS_NAME );
				delete_option( 'sam_pro_version' );
				//delete_option('sam_pro_db_version');
			}
			if ( $settings['deleteDB'] == 1 ) {
				$sql = "DROP TABLE IF EXISTS {$tables}";
				$wpdb->query( $sql );
				delete_option( 'sam_pro_db_version' );
			}
			if ( $settings['deleteFolder'] == 1 ) {
				if ( is_dir( SAM_PRO_AD_IMG ) ) {
					rmdir( SAM_PRO_AD_IMG );
				}
			}
		}

		public static function onUninstall() {
			global $wpdb;
			$tables = implode( ',', array(
				$wpdb->prefix . "sampro_places_ads",
				$wpdb->prefix . "sampro_places",
				$wpdb->prefix . "sampro_ads",
				$wpdb->prefix . "sampro_blocks",
				$wpdb->prefix . "sampro_errors",
				//$wpdb->prefix . "sampro_stats"
			) );

			$sql = "DROP TABLE IF EXISTS {$tables}";
			$wpdb->query( $sql );

			delete_option( SAM_PRO_OPTIONS_NAME );
			delete_option( 'sam_pro_version' );
			delete_option( 'sam_pro_db_version' );

			if ( is_dir( SAM_PRO_AD_IMG ) ) {
				rmdir( SAM_PRO_AD_IMG );
			}
		}

		public function onWpLoaded() {
			//$this->rules = self::getRulesList();
		}

		public function checkCachePlugins() {
			$w3tc = 'w3-total-cache/w3-total-cache.php';
			$wpsc = 'wp-super-cache/wp-cache.php';
			define('SAM_PRO_WPSC', is_plugin_active($wpsc));
			define('SAM_PRO_W3TC', is_plugin_active($w3tc));
		}

		public function checkBbpForum() {
			$force = ( empty( $this->samOptions ) );
			$settings = parent::getSettings( $force );
			$bbp = 'bbpress/bbpress.php';
			define('SAM_PRO_BBP', is_plugin_active($bbp));
			$settings['bbpActive'] = ( SAM_PRO_BBP ) ? 1 : 0;
			if( ! SAM_PRO_BBP ) $settings['bbpEnabled'] = 0;
			update_option( SAM_PRO_OPTIONS_NAME, $settings );
		}

		public function checkWPtouch() {
			$touch = 'wptouch/wptouch.php';
			define('SAM_PRO_WPTOUCH', is_plugin_active($touch));
		}

		public function hideBbpOptions() {
			$options = parent::getSettings();
			return !( SAM_PRO_BBP && $options['bbpEnabled']);
		}

		public function hideWptOptions() {
			$options = parent::getSettings();
			$samWPT = ((defined('SAM_PRO_WPTOUCH')) ? SAM_PRO_WPTOUCH : false);
			return !($samWPT && $options['wptouchEnabled']);
		}

		private function getWarningString( $mode = '' ) {
			if(empty($mode)) return '';

			global $wp_version;
			$options = parent::getSettings();
			$classDef = false;

			switch($mode) {
				case 'cache':
					if(SAM_PRO_W3TC) $text = __('Active W3 Total Cache plugin detected.', SAM_PRO_DOMAIN);
					elseif(SAM_PRO_WPSC) $text = __('Active WP Super Cache plugin detected.', SAM_PRO_DOMAIN);
					else $text = '';
					$classDef = ($options['adShow'] == 'php');
					break;
				case 'forum':
					if(SAM_PRO_BBP) $text = __('Active bbPress Forum plugin detected.', SAM_PRO_DOMAIN);
					else $text = '';
					$classDef = (!$options['bbpEnabled']);
					break;
				case 'mobile':
					$text = (SAM_PRO_WPTOUCH) ? __('Active WPtouch (Free Edition) plugin detected.', SAM_PRO_DOMAIN) : '';
					$classDef = !$options['wptouchEnabled'];
					break;
				default: $text = '';
			}

			$class = ($classDef) ? 'sam-pro-warning' : 'sam-pro-info';

			return ((!empty($text)) ? "<div class='{$class}'><p>{$text}</p></div>" : '');
		}

		public function setTablesDefinition() {
			global $wpdb, $sam_pro_tables_definition;

			$eTable  = $wpdb->prefix . 'sampro_errors';
			$pTable  = $wpdb->prefix . 'sampro_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$aTable  = $wpdb->prefix . 'sampro_ads';
			$zTable  = $wpdb->prefix . 'sampro_zones';
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';
			$bTable  = $wpdb->prefix . 'sampro_blocks';
			$sTable  = $wpdb->prefix . 'sampro_stats';

			$sam_pro_tables_definition = array();

			$sam_pro_tables_definition[$eTable] = array(
				'eid' => array('Type' => 'int(11)', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => 'auto_increment'),
				'edate' => array('Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'tname' => array('Type' => 'varchar(30)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'etype' => array('Type' => 'int(1)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'emsg' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'esql' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'solved' => array('Type' => 'tinyint(1)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			);

			$sam_pro_tables_definition[$aTable] = array(
				'aid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => 'auto_increment'),
				'title' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'description' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'moderated' => array('Type' => 'tinyint(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'img' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'link' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'alt' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'swf' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'swf_vars' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'swf_params' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'swf_fallback' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'swf_attrs' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'acode' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'php' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'inline' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'amode' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'hits' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'clicks' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'rel' => array('Type' => 'tinyint(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'asize' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'width' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'height' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'price' => array('Type' => 'decimal(15,2) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0.00', 'Extra' => ''),
				'ptype' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'ptypes' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'eposts' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xposts' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'posts' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'ecats' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xcats' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'cats' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'etags' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xtags' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'tags' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'eauthors' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xauthors' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'authors' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'etax' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xtax' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'taxes' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'etypes' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xtypes' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'types' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'schedule' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'sdate' => array('Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'fdate' => array('Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'limit_hits' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'hits_limit' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'hits_period' => array('Type' => 'tinyint(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'limit_clicks' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'clicks_limit' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'clicks_period' => array('Type' => 'tinyint(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'users' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'users_unreg' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'users_reg' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xusers' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xvusers' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'advertiser' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '1', 'Extra' => ''),
				'geo' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'xgeo' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'geo_country' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'geo_region' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'geo_city' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'owner' => array('Type' => 'varchar(50)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'owner_name' => array('Type' => 'varchar(50)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'owner_mail' => array('Type' => 'varchar(50)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'ppm' => array('Type' => 'decimal(10,2) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0.00', 'Extra' => ''),
				'ppc' => array('Type' => 'decimal(10,2) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0.00', 'Extra' => ''),
				'ppi' => array('Type' => 'decimal(10,2) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0.00', 'Extra' => ''),
				'trash' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			);

			$sam_pro_tables_definition[$pTable] = array(
				'pid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => 'auto_increment'),
				'aid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'title' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'description' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'sale' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'sale_mode' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'price' => array('Type' => 'decimal(15,2) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0.00', 'Extra' => ''),
				'sdate' => array('Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'fdate' => array('Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'code_before' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'code_after' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'asize' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'width' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'height' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'img' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'link' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'alt' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'rel' => array('Type' => 'tinyint(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'acode' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'inline' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'php' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'ad_server' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'dfp' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'amode' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'hits' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'clicks' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'trash' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => '')
			);

			$sam_pro_tables_definition[$paTable] = array(
				'pid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'aid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'weight' => array('Type' => 'tinyint(3) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '10', 'Extra' => ''),
				'hits' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'trash' => array('Type' => 'tinyint(1)', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => '')
			);

			$sam_pro_tables_definition[$zTable] = array(
				'zid' => array('Type' => 'int(10) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => 'auto_increment'),
				'title' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'description' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'single_id' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'arc_id' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'trash' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			);

			$sam_pro_tables_definition[$zrTable] = array(
				'zid' => array('Type' => 'int(10) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'slug' => array('Type' => 'varchar(150)', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'single' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '0', 'Extra' => ''),
				'tax' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'name' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'term_slug' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'pid' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'priority' => array('Type' => 'int(4) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '1', 'Extra' => '')
			);

			$sam_pro_tables_definition[$bTable] = array(
				'bid' => array('Type' => 'int(10) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => 'auto_increment'),
				'title' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'description' => array('Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'b_rows' => array('Type' => 'int(5) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '1', 'Extra' => ''),
				'b_columns' => array('Type' => 'int(5) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '1', 'Extra' => ''),
				'b_data' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'b_style' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'i_style' => array('Type' => 'text', 'Null' => 'YES', 'Key' => '', 'Default' => '', 'Extra' => ''),
				'trash' => array('Type' => 'tinyint(1) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => '')
			);

			$sam_pro_tables_definition[$sTable] = array(
				'edate' => array('Type' => 'date', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'pid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'aid' => array('Type' => 'int(11) unsigned', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => '', 'Extra' => ''),
				'hits' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => ''),
				'clicks' => array('Type' => 'int(11) unsigned', 'Null' => 'YES', 'Key' => '', 'Default' => '0', 'Extra' => '')
			);
		}

		private function upgradeDB() {
			include_once('sam-pro-updater.php');
			global $sam_pro_tables_definition;
			$options = parent::getSettings();

			$updater = new SamProUpdater($this->samVersions['db'], $options);
			$updater->check($sam_pro_tables_definition);
		}

		public function getAdsData() {
			global $wpdb;
			$pTable = $wpdb->prefix . 'sampro_places';
			$aTable = $wpdb->prefix . 'sampro_ads';
			$zTable = $wpdb->prefix . 'sampro_zones';
			$bTable = $wpdb->prefix . 'sampro_blocks';

			$sql = "SELECT CONCAT(0, '_', pid) AS ival, title FROM {$pTable} WHERE trash = 0;";
			$places = $wpdb->get_results($sql, ARRAY_A);
			$sql = "SELECT CONCAT(1, '_', aid) AS ival, title FROM {$aTable} WHERE trash = 0;";
			$ads = $wpdb->get_results($sql, ARRAY_A);
			$sql = "SELECT CONCAT(2, '_', zid) AS ival, title FROM {$zTable} WHERE trash = 0;";
			$zones = $wpdb->get_results($sql, ARRAY_A);
			$sql = "SELECT CONCAT(3, '_', bid) AS ival, title FROM {$bTable} WHERE trash = 0;";
			$blocks = $wpdb->get_results($sql, ARRAY_A);

			return array(
				'places' => array(
					'title' => __('Places', SAM_PRO_DOMAIN),
					'data' => $places
				),
				'ads' => array(
					'title' => __('Ads', SAM_PRO_DOMAIN),
					'data' => $ads
				),
				'zones' => array(
					'title' => __('Zones', SAM_PRO_DOMAIN),
					'data' => $zones
				),
				'blocks' => array(
					'title' => __('Blocks', SAM_PRO_DOMAIN),
					'data' => $blocks
				)
			);
		}

		private function getRulesList( $single = false ) {
			global $wpdb, $wp_taxonomies;
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';
			$uTable = $wpdb->base_prefix . "users";
			$umTable = $wpdb->base_prefix . "usermeta";
			$userLevel = $wpdb->base_prefix . 'user_level';
			$tTable = $wpdb->prefix . "terms";
			$ttTable = $wpdb->prefix . "term_taxonomy";

			$all = __('All', SAM_PRO_DOMAIN);
			$ctt = __('Custom Taxonomy Terms', SAM_PRO_DOMAIN);
			$cats = __('Categories', SAM_PRO_DOMAIN);
			$cat = __('Category', SAM_PRO_DOMAIN);
			$tags = __('Tags', SAM_PRO_DOMAIN);
			$tag = __('Tag', SAM_PRO_DOMAIN);
			$authors = __('Authors', SAM_PRO_DOMAIN);
			$author = __('Author', SAM_PRO_DOMAIN);
			$notTax = array($ctt, $cats, $cat, $tags, $tag, $authors, $author);

			$sql = "(SELECT
	2 AS priority,
	'category_all-cats' AS slug,
  '{$cats}' AS tax,
  '{$all}' AS name,
  'all-cats' AS term_slug)
UNION
(SELECT
	1 AS priority,
	CONCAT(wtt.taxonomy, '_', wt.slug) AS slug,
  '{$cat}' AS tax,
  wt.name,
  wt.slug AS term_slug
FROM
  {$tTable} wt
INNER JOIN {$ttTable} wtt
  ON wt.term_id = wtt.term_id
WHERE wtt.taxonomy = 'category' AND wt.term_id <> 1
ORDER BY wt.name)
UNION
(SELECT
	2 AS priority,
	'post_tag_all-tags' AS slug,
  '{$tags}' AS tax,
  '{$all}' AS name,
  'all-tags' AS term_slug)
UNION
(SELECT
	1 AS priority,
	CONCAT(wtt.taxonomy, '_', wt.slug) AS slug,
  '{$tag}' AS tax,
  wt.name,
  wt.slug AS term_slug
FROM
  {$tTable} wt
INNER JOIN {$ttTable} wtt
  ON wt.term_id = wtt.term_id
WHERE wtt.taxonomy = 'post_tag' AND wt.term_id <> 1
ORDER BY wt.name)
UNION
(SELECT
	2 AS priority,
	'ctt_all-ctt' AS slug,
  '{$ctt}' AS tax,
  '{$all}' AS name,
  'all-ctt' AS term_slug)
UNION
(SELECT
	1 AS priority,
	CONCAT(wtt.taxonomy, '_', wt.slug) AS slug,
  wtt.taxonomy AS tax,
  wt.name,
  wt.slug AS rerm_slug
FROM
  {$tTable} wt
INNER JOIN {$ttTable} wtt
  ON wt.term_id = wtt.term_id
WHERE NOT FIND_IN_SET(wtt.taxonomy, 'category,post_tag,nav_menu,link_category,post_format') AND wt.term_id <> 1
ORDER BY wtt.taxonomy, wt.name)
UNION
(SELECT
	2 AS priority,
	'author_all-authors' AS slug,
  '{$authors}' AS tax,
  '{$all}' AS name,
  'all-authors' AS term_slug)
UNION
(SELECT
	1 AS priority,
	CONCAT('author_', wu.user_nicename) AS slug,
  '{$author}' AS tax,
  wu.display_name AS name,
  wu.user_nicename AS term_slug
FROM
  {$uTable} wu
INNER JOIN {$umTable} wum
  ON wu.ID = wum.user_id
WHERE
  wum.meta_key = '{$userLevel}' AND
  wum.meta_value > 1);";
			$taxes = $wpdb->get_results($sql, ARRAY_A);

			foreach($taxes as $key => $val) {
				if(!in_array($val['tax'], $notTax))
					$taxes[$key]['tax'] =
						(isset($wp_taxonomies[$val['tax']])) ?
							$val['ctax_name'] = urldecode($wp_taxonomies[$val['tax']]->labels->name) : $val['tax'];
			}

			$args      = array( 'public' => true, '_builtin' => false );
			$output    = 'objects';
			$operator  = 'and';
			$postTypes = get_post_types( $args, $output, $operator );
			array_push( $taxes, array(
				'priority' => 2,
				'slug' => 'cpt_all-cpt',
				'tax'  => __( 'Custom Post Types', SAM_PRO_DOMAIN ),
				'name' => $all,
				'term_slug' => 'all-cpt'
			) );
			foreach ( $postTypes as $custom ) {
				array_push( $taxes, array(
					'priority' => 1,
					'slug' => 'cpt_' . $custom->name,
					'tax'  => __( 'Custom Post Type', SAM_PRO_DOMAIN ),
					'name' => $custom->label,
					'term_slug' => $custom->name
				) );
			}

			$singleTaxes = $taxes;

			// Only Singular Pages Rules
			array_push($singleTaxes,
				array('priority' => 100, 'slug' => 'page_singular', 'tax' => __('Singular Pages', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_singular'));
			array_push($singleTaxes,
				array('priority' => 90, 'slug' => 'page_single', 'tax' => __('Single Post/Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_single'));
			array_push($singleTaxes,
				array('priority' => 3, 'slug' => 'page_page', 'tax' => __('Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_page'));
			array_push($singleTaxes,
				array('priority' => 3, 'slug' => 'page_attachment', 'tax' => __('Attachment', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_attachment'));

			// General Rules
			array_push($taxes,
				array('priority' => 100,	'slug' => 'page_home', 'tax' => __('Home/Front Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_home'));
			array_push($taxes,
				array('priority' => 100,	'slug' => 'page_search', 'tax' => __('Search Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_search'));
			array_push($taxes,
				array('priority' => 100,	'slug' => 'page_404', 'tax' => __('404 Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_404'));
			array_push($taxes,
				array('priority' => 100,	'slug' => 'page_archive', 'tax' => __('Archive Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_archive'));
			array_push($taxes,
				array('priority' => 90,	'slug' => 'page_all-date', 'tax' => __('Any Date Archive Page', SAM_PRO_DOMAIN),	'name' => $all,	'term_slug' => 'page_date'));

			return array('rules' => $taxes, 'single' => $singleTaxes);
		}

		private function getPlaces() {
			global $wpdb;
			$pTable = $wpdb->prefix . 'sampro_places';
			$sql = "SELECT sp.pid, sp.title FROM {$pTable} sp;";
			$places = $wpdb->get_results($sql, ARRAY_A);
			$out = array_merge(array(array('pid' => 0, 'title' => __('None', SAM_PRO_DOMAIN))), $places);

			return $out;
		}

		private function getGridsData() {
			global $wpdb, $wp_taxonomies;

			$tTable = $wpdb->prefix . "terms";
			$ttTable = $wpdb->prefix . "term_taxonomy";

			//Custom Post Types
			$args = array('public' => true, '_builtin' => false);
			$output = 'objects';
			$operator = 'and';
			$post_types = get_post_types($args, $output, $operator);
			$customs = array();
			$sCustoms = array();

			foreach($post_types as $post_type) {
				array_push($customs, array('title' => $post_type->labels->name, 'slug' => $post_type->name));
				array_push($sCustoms, $post_type->name);
			}
			$k = 0;
			foreach($customs as &$val) {
				$k++;
				$val['recid'] = $k;
			}
			if(!empty($sCustoms)) $custs = ',' . implode(',', $sCustoms);
			else $custs = '';

			// Custom Taxonomies Terms
			$sql = "SELECT wt.term_id AS id, wt.name, wt.slug, wtt.taxonomy
              FROM $tTable wt
              INNER JOIN $ttTable wtt
              ON wt.term_id = wtt.term_id
              WHERE NOT FIND_IN_SET(wtt.taxonomy, 'category,post_tag,nav_menu,link_category,post_format');";

			$cTax = $wpdb->get_results($sql, ARRAY_A);
			$k = 0;
			foreach($cTax as &$val) {
				if(isset($wp_taxonomies[$val['taxonomy']])) $val['ctax_name'] = urldecode($wp_taxonomies[$val['taxonomy']]->labels->name);
				else $val['ctax_name'] = '';
				//$k++;
				//$val['id'] = $k;
			}

			return array(
				'customs' => $customs,
				'custList' => $custs,
				'cTax' => $cTax
			);
		}

		private function getIntervals() {
			$months = array(
				__('January', SAM_PRO_DOMAIN),
				__('February', SAM_PRO_DOMAIN),
				__('Mart', SAM_PRO_DOMAIN),
				__('April', SAM_PRO_DOMAIN),
				__('May', SAM_PRO_DOMAIN),
				__('June', SAM_PRO_DOMAIN),
				__('July', SAM_PRO_DOMAIN),
				__('August', SAM_PRO_DOMAIN),
				__('September', SAM_PRO_DOMAIN),
				__('October', SAM_PRO_DOMAIN),
				__('November', SAM_PRO_DOMAIN),
				__('December', SAM_PRO_DOMAIN),
			);

			$out = array();
			$m = (int)date('m');
			$mName = $months[$m-1];

			$out[0] = array('start' => date('Y-m-01'), 'end' => date('Y-m-t'), 'title' => __('This month', SAM_PRO_DOMAIN)." ({$mName}, ". date('Y').')');
			for($i = 1; $i < $m; $i++) {
				$cm = $m - $i;
				$mName = $months[$cm-1];
				$date = new DateTime();
				$interval = new DateInterval("P{$i}M");
				$date->sub($interval);
				$out[$i] = array('start' => $date->format("Y-m-01"), 'end' => $date->format("Y-m-t"), 'title' => $mName.', '.$date->format('Y'));
			}
			$out[20] = array('start' => date('Y-01-01'), 'end' => date('Y-m-t'), 'title' => __('This year', SAM_PRO_DOMAIN).' ('.date('Y').')');
			$date = new DateTime();
			$interval = new DateInterval('P1Y');
			$date->sub($interval);
			$out[21] = array('start' => $date->format("Y-01-01"), 'end' => $date->format("Y-12-31"), 'title' => __('Previous year', SAM_PRO_DOMAIN).' ('.$date->format('Y').')');

			return $out;
		}

		private function getAdvertisersList() {
			global $wpdb;
			$uTable = $wpdb->prefix . 'users';
			$aTable = $wpdb->prefix . 'sampro_ads';

			$sql = "SELECT uu.owner, uu.owner_name, uu.owner_mail FROM
  (SELECT
    wu.user_login AS owner, wu.user_nicename AS owner_name, wu.user_email AS owner_mail
  FROM {$uTable} wu
  UNION
  SELECT
    sa.owner, sa.owner_name, sa.owner_mail
  FROM {$aTable} sa) uu
WHERE uu.owner IS NOT NULL AND uu.owner <> '' AND uu.owner_mail IS NOT NULL AND uu.owner_mail <> ''
GROUP BY uu.owner_mail
ORDER BY uu.owner;";
			$rows = $wpdb->get_results($sql, ARRAY_A);

			return $rows;
		}

		public function regAdminPage() {
			$menuPage = add_menu_page( __( 'Ads', SAM_PRO_DOMAIN ), __( 'Ads', SAM_PRO_DOMAIN ), SAM_PRO_ACCESS, 'sam-pro-ads', array(	&$this,	'adsList'	), 'dashicons-randomize' );
			$this->adsPage = add_submenu_page( 'sam-pro-ads', __('Manage Ads', SAM_PRO_DOMAIN), _x('Ads', 'Menu Bar', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-ads', array(&$this, 'adsList') );
			$this->adEditor = add_submenu_page( 'sam-pro-ads', __('Ad Editor', SAM_PRO_DOMAIN), __('Ad Editor', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-ad-editor', array(&$this, 'adsEditor') );
			$this->placesPage = add_submenu_page( 'sam-pro-ads', __('Manage Ads Places', SAM_PRO_DOMAIN), __('Places', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-places', array(&$this, 'placesList') );
			$this->placeEditor = add_submenu_page('sam-pro-ads', __('Place Editor', SAM_PRO_DOMAIN), __('Place Editor', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-place-editor', array(&$this, 'placesEditor'));
			$this->zonesPage = add_submenu_page( 'sam-pro-ads', __('Manage Ads Zones', SAM_PRO_DOMAIN), __('Zones', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-zones', array(&$this, 'zonesList') );
			$this->zoneEditor = add_submenu_page('sam-pro-ads', __('Zone Editor', SAM_PRO_DOMAIN), __('Zone Editor', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-zone-editor', array(&$this, 'zonesEditor'));
			$this->blocksPage = add_submenu_page( 'sam-pro-ads', __('Manage Ads Blocks', SAM_PRO_DOMAIN), __('Blocks', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-blocks', array(&$this, 'blocksList') );
			$this->blockEditor = add_submenu_page('sam-pro-ads', __('Block Editor', SAM_PRO_DOMAIN), __('Block Editor', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-block-editor', array(&$this, 'blocksEditor'));
			$this->advertisersList = add_submenu_page('sam-pro-ads', __('Advertisers', SAM_PRO_DOMAIN), __('Advertisers', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-advertisers', array(&$this, 'advList'));
			$this->statsPage = add_submenu_page('sam-pro-ads', __('Statistics', SAM_PRO_DOMAIN), __('Statistics', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-statistics', array(&$this, 'statistics'));
			$this->toolsPage = add_submenu_page('sam-pro-ads', __('Tools', SAM_PRO_DOMAIN), __('Tools', SAM_PRO_DOMAIN), 'manage_options', 'sam-pro-tools', array(&$this, 'tools'));
			do_action('sam_pro_admin_options_page_above_settings');
			$this->settingsPage = add_submenu_page( 'sam-pro-ads', __( 'SAM Pro Settings', SAM_PRO_DOMAIN ), __( 'Settings', SAM_PRO_DOMAIN ), 'manage_options', 'sam-pro-settings', array(	&$this,	'adminPage'	) );
			$this->errorLogPage = add_submenu_page( 'sam-pro-ads', __('Error Log', SAM_PRO_DOMAIN), __('Error Log', SAM_PRO_DOMAIN), SAM_PRO_ACCESS, 'sam-pro-error-log', array(&$this, 'errorLog') );
			do_action('sam_pro_admin_options_page');

			add_action('admin_enqueue_scripts', array(&$this, 'loadScripts'));
		}

		private function getJsStrings( $mode = 'std' ) {
			if($mode == 'std') return array(
				'id' => __('Place ID', SAM_PRO_DOMAIN),
				'adId' => __('Ad ID', SAM_PRO_DOMAIN),
				'zid' => __('Zone ID', SAM_PRO_DOMAIN),
				'bid' => __('Block ID', SAM_PRO_DOMAIN),
				'name' => __('Name', SAM_PRO_DOMAIN),
				'desc' => __('Description', SAM_PRO_DOMAIN),
				'size' => __('Size', SAM_PRO_DOMAIN),
				'status' => __('Status', SAM_PRO_DOMAIN),
				'weight' => __('Weight', SAM_PRO_DOMAIN),
				'moveToTrash' => __('Move to Trash', SAM_PRO_DOMAIN),
				'restoreFromTrash' => __('Restore from Trash', SAM_PRO_DOMAIN),
				'newPlace' => __('New Place', SAM_PRO_DOMAIN),
				'newAd' => __('New Ad', SAM_PRO_DOMAIN),
				'newZone' => __('New Zone', SAM_PRO_DOMAIN),
				'newBlock' => __('New Block', SAM_PRO_DOMAIN),
				'linkAds' => __('Link Ads', SAM_PRO_DOMAIN),
				'unlinkAd' => __('Unlink Ad', SAM_PRO_DOMAIN),
				'expand' => __('Expand', SAM_PRO_DOMAIN),
				'collapse' => __('Collapse', SAM_PRO_DOMAIN),
				'refresh' => __('Refresh', SAM_PRO_DOMAIN),
				'edit' => __('Edit', SAM_PRO_DOMAIN),
				'editAd' => __('Edit Ad', SAM_PRO_DOMAIN),
				'viewMode' => __('View Mode', SAM_PRO_DOMAIN),
				'approve' => __('Approve', SAM_PRO_DOMAIN),
				'cancelApproval' => __('Cancel Approval', SAM_PRO_DOMAIN)
			);
			else return array(
				'id' => __('Place ID', SAM_PRO_DOMAIN),
				'adId' => __('ID', SAM_PRO_DOMAIN),
				'zid' => __('Zone ID', SAM_PRO_DOMAIN),
				'bid' => __('Block ID', SAM_PRO_DOMAIN),
				'name' => __('Name', SAM_PRO_DOMAIN),
				'desc' => __('Description', SAM_PRO_DOMAIN),
				'size' => __('Size', SAM_PRO_DOMAIN),
				'status' => __('Status', SAM_PRO_DOMAIN),
				'weight' => __('Weight', SAM_PRO_DOMAIN),
				'type' => __('Type', SAM_PRO_DOMAIN),
				'slug' => __('Slug', SAM_PRO_DOMAIN),
				'termName' => __('Term Name', SAM_PRO_DOMAIN),
				'ctName' => __('Custom Taxonomy Name', SAM_PRO_DOMAIN),
				'ctt' => __('Custom Type Title', SAM_PRO_DOMAIN),
				'ctSlug' => __('Custom Type Slug', SAM_PRO_DOMAIN),
				'display' => __('Display Name', SAM_PRO_DOMAIN),
				'user'  => __('User Name', SAM_PRO_DOMAIN),
				'role' => __('Role', SAM_PRO_DOMAIN),
				'priority' => __('Priority', SAM_PRO_DOMAIN),
				'place' => __('Place', SAM_PRO_DOMAIN),
				'moveToTrash' => __('Move to Trash', SAM_PRO_DOMAIN),
				'restoreFromTrash' => __('Restore from Trash', SAM_PRO_DOMAIN),
				'newPlace' => __('New Place', SAM_PRO_DOMAIN),
				'newAd' => __('New Ad', SAM_PRO_DOMAIN),
				'newZone' => __('New Zone', SAM_PRO_DOMAIN),
				'newBlock' => __('New Block', SAM_PRO_DOMAIN),
				'linkAds' => __('Link Ads', SAM_PRO_DOMAIN),
				'unlinkAd' => __('Unlink Ad', SAM_PRO_DOMAIN),
				'expand' => __('Expand', SAM_PRO_DOMAIN),
				'collapse' => __('Collapse', SAM_PRO_DOMAIN),
				'refresh' => __('Refresh', SAM_PRO_DOMAIN),
				'edit' => __('Edit', SAM_PRO_DOMAIN),
				'editAd' => __('Edit Ad', SAM_PRO_DOMAIN),
				'viewMode' => __('View Mode', SAM_PRO_DOMAIN),
				'approve' => __('Approve', SAM_PRO_DOMAIN),
				'cancelApproval' => __('Cancel Approval', SAM_PRO_DOMAIN),
				'uploading' => __('Uploading', SAM_PRO_DOMAIN).' ...',
				'uploaded' => __('Uploaded.', SAM_PRO_DOMAIN),
				'file' => __('File', SAM_PRO_DOMAIN),
				'path' => SAM_PRO_AD_IMG,
				'url' => SAM_PRO_AD_URL,
				'posts' => __('Post', SAM_PRO_DOMAIN),
				'page' => __('Page', SAM_PRO_DOMAIN),
				'subscriber' => __('Subscriber', SAM_PRO_DOMAIN),
				'contributor' => __('Contributor', SAM_PRO_DOMAIN),
				'author' => __('Author', SAM_PRO_DOMAIN),
				'editor' => __('Editor', SAM_PRO_DOMAIN),
				'admin' => __('Administrator', SAM_PRO_DOMAIN),
				'superAdmin' => __('Super Admin', SAM_PRO_DOMAIN),
				'labels' => array('hits' => __('Hits', SAM_PRO_DOMAIN), 'clicks' => __('Clicks', SAM_PRO_DOMAIN)),
				'places' => __('Places', SAM_PRO_DOMAIN),
				'ads' => __('Single Ads', SAM_PRO_DOMAIN),
				'add' => __('Add', SAM_PRO_DOMAIN),
				'country' => __('Country', SAM_PRO_DOMAIN),
				'region' => __('Region', SAM_PRO_DOMAIN),
				'impressions' => __('Impressions', SAM_PRO_DOMAIN),
				'clicks' => __('Clicks', SAM_PRO_DOMAIN),
				'income' => __('Income', SAM_PRO_DOMAIN),
				'advertiser' => __('Advertiser', SAM_PRO_DOMAIN),
				'advertisers' => __('All advertisers', SAM_PRO_DOMAIN),
				'statistics' => __('Statistics', SAM_PRO_DOMAIN),
				'sendMail' => __('Send Mail', SAM_PRO_DOMAIN),
				'mail' => __('Mail', SAM_PRO_DOMAIN),
				'nick' => __('Nickname', SAM_PRO_DOMAIN)
			);
		}

		public function loadScripts( $hook ) {
			$settings = parent::getSettings();
			$wpLocale = str_replace('_', '-', get_locale());
			$locales = array('ru-RU');
			$locale = array('changeLocale' => in_array($wpLocale, $locales), 'locale' => $wpLocale);

			if( $hook == $this->settingsPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('normalize', SAM_PRO_URL . 'css/normalize.css');
				wp_enqueue_style('ion-slider', SAM_PRO_URL . 'css/ion.rangeSlider.css');
				wp_enqueue_style('ion-slider-nice', SAM_PRO_URL . 'css/ion.rangeSlider.skinNice.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-settings', SAM_PRO_URL . 'css/sam-pro-settings.css');
				do_action('sam_pro_admin_settings_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.editors.all.min.js', array('jquery'), '13.1.0.21');
				wp_enqueue_script('ion-slider', SAM_PRO_URL . 'js/ion.rangeSlider.min.js', array('jquery'), '2.0.2');

				wp_enqueue_script('sam-pro-settings', SAM_PRO_URL . 'js/sam.pro.settings.min.js', array('jquery', 'easing', 'ej-all'));
				wp_localize_script('sam-pro-settings', 'options', array(
					'roles' => array(
						__('Super Admin', SAM_PRO_DOMAIN),
						__('Administrator', SAM_PRO_DOMAIN),
						__('Editor', SAM_PRO_DOMAIN),
						__('Author', SAM_PRO_DOMAIN),
						__('Contributor', SAM_PRO_DOMAIN)
					),
					'values' => array('manage_network', 'manage_options', 'edit_others_posts', 'publish_posts', 'edit_posts'),
					'adTypes' => array(
						array('parentId' => 0, 'value' => 0, 'text' => __('Single Ad', SAM_PRO_DOMAIN)),
						array('parentId' => 1, 'value' => 1, 'text' => __('Ads Place', SAM_PRO_DOMAIN)),
						array('parentId' => 2, 'value' => 2, 'text' => __('Ads Zone', SAM_PRO_DOMAIN)),
						array('parentId' => 3, 'value' => 3, 'text' => __('Ads Block', SAM_PRO_DOMAIN))
					)
				));
				do_action('sam_pro_admin_settings_scripts');
			}
			elseif( $hook == $this->placesPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-places', SAM_PRO_URL . 'css/sam-pro-places.css');
				do_action('sam_pro_admin_places_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery','ej-all') );
					wp_enqueue_script('dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize'));
				}

				wp_enqueue_script('SimpleLib-tools', SAM_PRO_URL . 'js/simplelib.tools.js', array('jquery'), SAM_PRO_VERSION);
				wp_enqueue_script('sam-pro-places-list', SAM_PRO_URL . 'js/sam.pro.places.list.min.js', array('jquery'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-places-list', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'editorUrl' => admin_url('admin.php') . '?page=sam-pro-place-editor&item=',
					'adEditorUrl' => admin_url('admin.php') . '?page=sam-pro-ad-editor&item=',
					'itemsPerPage' => $settings['itemsPerPage'],
					'locale' => $locale,
					'strings' => self::getJsStrings(),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_places_scripts');
			}
			elseif( $hook == $this->adsPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-places', SAM_PRO_URL . 'css/sam-pro-places.css');
				do_action('sam_pro_admin_ads_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale'])
					wp_enqueue_script('ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all'));

				wp_enqueue_script('sam-pro-ads-list', SAM_PRO_URL . 'js/sam.pro.ads.list.min.js', array('jquery', 'ej-all'));
				wp_localize_script('sam-pro-ads-list', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'samProAjaxModerate' => apply_filters('sam_pro_front_ajax_moderate', SAM_PRO_URL . 'sam-pro-ajax-admin.php'),
					'editorUrl' => admin_url('admin.php') . '?page=sam-pro-ad-editor&item=',
					'itemsPerPage' => $settings['itemsPerPage'],
					'locale' => $locale,
					'strings' => self::getJsStrings(),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_ads_scripts');
			}
			elseif( $hook == $this->zonesPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-places', SAM_PRO_URL . 'css/sam-pro-places.css');
				do_action('sam_pro_admin_zones_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('sam-pro-zones-list', SAM_PRO_URL . 'js/sam.pro.zones.list.min.js', array('jquery', 'ej-all'));
				wp_localize_script('sam-pro-zones-list', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'editorUrl' => admin_url('admin.php') . '?page=sam-pro-zone-editor&item=',
					'itemsPerPage' => $settings['itemsPerPage'],
					'locale' => $locale,
					'strings' => self::getJsStrings(),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_zones_scripts');
			}
			elseif( $hook == $this->blocksPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-places', SAM_PRO_URL . 'css/sam-pro-places.css');
				do_action('sam_pro_admin_blocks_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('sam-pro-blocks-list', SAM_PRO_URL . 'js/sam.pro.blocks.list.min.js', array('jquery', 'ej-all'));
				wp_localize_script('sam-pro-blocks-list', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'editorUrl' => admin_url('admin.php') . '?page=sam-pro-block-editor&item=',
					'itemsPerPage' => $settings['itemsPerPage'],
					'locale' => $locale,
					'strings' => self::getJsStrings(),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_blocks_scripts');
			}
			elseif( $hook == $this->errorLogPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');

				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-places', SAM_PRO_URL . 'css/sam-pro-places.css');
				do_action('sam_pro_admin_error_log_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('sam-pro-error-log', SAM_PRO_URL . 'js/sam.pro.error.log.min.js', array('jquery', 'ej-all'));
				wp_localize_script('sam-pro-error-log', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'itemsPerPage' => $settings['itemsPerPage'],
					'locale' => $locale,
					'id' => __('ID', SAM_PRO_DOMAIN),
					'date' => __('Date', SAM_PRO_DOMAIN),
					'table' => __('Table', SAM_PRO_DOMAIN),
					'msg' => __('Message', SAM_PRO_DOMAIN),
					'sql' => __('SQL', SAM_PRO_DOMAIN),
					'etype' => array(__('Message Type', SAM_PRO_DOMAIN), __('Type', SAM_PRO_DOMAIN)),
					'close' => __('Close', SAM_PRO_DOMAIN),
					'title' => __('More Info', SAM_PRO_DOMAIN),
					'alts' => array(__('Error', SAM_PRO_DOMAIN), __('Info', SAM_PRO_DOMAIN)),
					'warning' => __('Warning', SAM_PRO_DOMAIN),
					'update' => __('Update Error', SAM_PRO_DOMAIN),
					'output' => __('Output Error', SAM_PRO_DOMAIN),
					'status' => __('Status', SAM_PRO_DOMAIN),
					'moreInfo' => __('More Info', SAM_PRO_DOMAIN),
					'setSolved' => __('Set Solved', SAM_PRO_DOMAIN),
					'clearLog' => __('Clear Error Log', SAM_PRO_DOMAIN),
					'cancelApproval' => __('Cancel Approval', SAM_PRO_DOMAIN),
					'viewMode' => __('View Mode', SAM_PRO_DOMAIN),
					'refresh' => __('Refresh', SAM_PRO_DOMAIN),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_error_log_scripts');
			}
			elseif( $hook == $this->adEditor ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-editor', SAM_PRO_URL . 'css/sam-pro-editor.css');
				do_action('sam_pro_admin_ad_editor_styles');

				if($settings['useSWF']) wp_enqueue_script('swfobject');
				wp_enqueue_script('jquery');
				wp_enqueue_media();
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.editors.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('SimpleLib-tools', SAM_PRO_URL . 'js/simplelib.tools.js', array('jquery'));
				wp_enqueue_script('sam-pro-ad-editor', SAM_PRO_URL . 'js/sam.pro.ad.editor.min.js', array('jquery', 'ej-all'));
				wp_localize_script('sam-pro-ad-editor', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'media' => array('title' => __('Select Banner Image', SAM_PRO_DOMAIN), 'button' => __('Select', SAM_PRO_DOMAIN)),
					'strings' => self::getJsStrings('editor'),
					'itemsPerPage' => $settings['itemsPerPage'],
					'data' => self::getGridsData(),
					'dtFormat' => get_option('date_format') . ' ' . get_option('time_format'),
					'locale' => $locale,
					'advertisers' => self::getAdvertisersList(),
					'buttonText' => array(
						'done' => __('Done', SAM_PRO_DOMAIN),
						'now' => __('Now', SAM_PRO_DOMAIN),
						'timeTitle' => __('Time', SAM_PRO_DOMAIN),
						'today' => __('Today', SAM_PRO_DOMAIN)
					),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_ad_editor_scripts');
			}
			elseif( $hook == $this->placeEditor ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-editor', SAM_PRO_URL . 'css/sam-pro-editor.css');
				wp_enqueue_style('sam-pro-icons', SAM_PRO_URL . 'css/sam-pro-icons.css');
				do_action('sam_pro_admin_place_editor_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_media();
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('validate', SAM_PRO_URL . 'js/jquery.validate.min.js', array('jquery'), '1.3.1');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.editors.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('SimpleLib-tools', SAM_PRO_URL . 'js/simplelib.tools.js', array('jquery'), SAM_PRO_VERSION);
				wp_enqueue_script('sam-pro-place-editor', SAM_PRO_URL . 'js/sam.pro.place.editor.min.js', array('jquery', 'ej-all'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-place-editor', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'media' => array('title' => __('Select Banner Image', SAM_PRO_DOMAIN), 'button' => __('Select', SAM_PRO_DOMAIN)),
					'item' => ((isset($_GET['item'])) ? $_GET['item'] : 0),
					'locale' => $locale,
					'strings' => self::getJsStrings('editor'),
					'adEditorUrl' => admin_url('admin.php') . '?page=sam-pro-ad-editor&item=',
					'itemsPerPage' => $settings['itemsPerPage'],
					'buttonText' => array(
						'done' => __('Done', SAM_PRO_DOMAIN),
						'now' => __('Now', SAM_PRO_DOMAIN),
						'timeTitle' => __('Time', SAM_PRO_DOMAIN),
						'today' => __('Today', SAM_PRO_DOMAIN)
					),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_place_editor_scripts');
			}
			elseif( $hook == $this->zoneEditor ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-editor', SAM_PRO_URL . 'css/sam-pro-editor.css');
				do_action('sam_pro_admin_zone_editor_styles');

				wp_enqueue_script('jquery');
				//wp_enqueue_media();
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('validate', SAM_PRO_URL . 'js/jquery.validate.min.js', array('jquery'), '1.3.1');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.editors.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('SimpleLib-tools', SAM_PRO_URL . 'js/simplelib.tools.js', array('jquery'), SAM_PRO_VERSION);
				wp_enqueue_script('sam-pro-zone-editor', SAM_PRO_URL . 'js/sam.pro.zone.editor.min.js', array('jquery', 'ej-all'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-zone-editor', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'item' => ((isset($_GET['item'])) ? $_GET['item'] : 0),
					'itemsPerPage' => $settings['itemsPerPage'],
					'rules' => self::getRulesList(),
					'places' => self::getPlaces(),
					'locale' => $locale,
					'strings' => self::getJsStrings('editor'),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_zone_editor_scripts');
			}
			elseif( $hook == $this->blockEditor ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-editor', SAM_PRO_URL . 'css/sam-pro-editor.css');
				do_action('sam_pro_admin_block_editor_styles');

				wp_enqueue_script('jquery');
				//wp_enqueue_media();
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('validate', SAM_PRO_URL . 'js/jquery.validate.min.js', array('jquery'), '1.3.1');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.editors.all.min.js', array('jquery'), '13.1.0.21');

				wp_enqueue_script('sam-pro-block-editor', SAM_PRO_URL . 'js/sam.pro.block.editor.min.js', array('jquery', 'ej-all'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-block-editor', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'strings' => array('places' => __('Places', SAM_PRO_DOMAIN), 'ads' => __('Single Ads', SAM_PRO_DOMAIN)),
					'item' => ((isset($_GET['item'])) ? $_GET['item'] : 0),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_block_editor_scripts');
			}
			elseif( $hook == $this->advertisersList ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-advertisers', SAM_PRO_URL . 'css/sam-pro-advertisers-list.css');
				do_action('sam_pro_admin_advertisers_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('validate', SAM_PRO_URL . 'js/jquery.validate.min.js', array('jquery'), '1.3.1');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.lists.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('sam-pro-adverts', SAM_PRO_URL . 'js/sam.pro.advertisers.list.min.js', array('jquery', 'ej-all'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-adverts', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'locale' => $locale,
					'strings' => self::getJsStrings('editor'),
					'itemsPerPage' => $settings['itemsPerPage'],
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_advertisers_scripts');
			}
			elseif( $hook == $this->statsPage ) {
				wp_enqueue_style('ej-css', SAM_PRO_URL . 'css/ej.web.all.min.css');
				wp_enqueue_style('sam-pro-font', SAM_PRO_URL . 'css/sam-pro-embedded.css');
				wp_enqueue_style('sam-pro-stats', SAM_PRO_URL . 'css/sam-pro-statistics.css');
				do_action('sam_pro_admin_stats_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');

				wp_enqueue_script('easing', SAM_PRO_URL . 'js/jquery.easing.1.3.js', array('jquery'), '1.3');
				wp_enqueue_script('globalize', SAM_PRO_URL . 'js/jquery.globalize.min.js', array('jquery'), '1.3');
				wp_enqueue_script('validate', SAM_PRO_URL . 'js/jquery.validate.min.js', array('jquery'), '1.3.1');
				wp_enqueue_script('jsrender', SAM_PRO_URL . 'js/jsrender.js', array('jquery'));
				wp_enqueue_script('ej-all', SAM_PRO_URL . 'js/ej.stats.all.min.js', array('jquery'), '13.1.0.21');
				if($locale['changeLocale']) {
					wp_enqueue_script( 'ej-locale', SAM_PRO_URL . "js/locales/ej.locale.{$locale['locale']}.js", array('jquery', 'ej-all') );
					wp_enqueue_script( 'dt-locale', SAM_PRO_URL . "js/locales/ej.culture.ru-RU.min.js", array('jquery', 'globalize') );
				}

				wp_enqueue_script('sam-pro-statistics', SAM_PRO_URL . 'js/sam.pro.statistics.min.js', array('jquery', 'ej-all'), SAM_PRO_VERSION);
				wp_localize_script('sam-pro-statistics', 'options', array(
					'samProAjax' => SAM_PRO_URL,
					'locale' => $locale,
					'strings' => self::getJsStrings('editor'),
					'itemsPerPage' => $settings['itemsPerPage'],
					'periods' => self::getIntervals(),
					'period' => ((isset($_POST['period'])) ? $_POST['period'] : 0),
					'owner' => ((isset($_POST['owner'])) ? $_POST['owner'] : 'all'),
					'item' => ((isset($_POST['item'])) ? $_POST['item'] : 0),
					'view' => ((isset($_POST['view'])) ? $_POST['view'] : 'sold'),
					'wap' => $this->wap
				));
				do_action('sam_pro_admin_stats_scripts');
			}
			elseif( $hook == $this->toolsPage ) {
				wp_enqueue_style('sam-pro-settings', SAM_PRO_URL . 'css/sam-pro-settings.css');
				do_action('sam_pro_admin_tools_styles');

				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-effects-core');
				wp_enqueue_script('jquery-effects-blind');
				do_action('sam_pro_admin_tools_scripts');
			}
			elseif( $hook == 'post.php' || $hook == 'post-new.php' ) {
				echo "<script type='text/javascript'>var samProOptions = {wap: '{$this->wap}'}</script>";
			}
		}

		public function addSettingsTab( $tab, $sections, $uri, $name, $class ) {
			$sections = apply_filters('sam_pro_settings_tab_sections_'.$tab, $sections);
			if(!empty($sections)) {
				for($i=0; $i < count($sections); $i++) {
					add_settings_section( $sections[ $i ][0], $sections[ $i ][1], $sections[ $i ][2], $sections[ $i ][3] );
					if($i === 0) $this->settingsTabs[ $sections[ $i ][ 0 ] ] = array(
						'start_tab' => true,
						'uri'       => $uri,
						'name'      => $name,
						'class'     => $class
					);
					if($i === (count($sections) - 1)) $this->settingsTabs[$sections[$i][0]]['finish_tab'] = true;
				}
			}
		}

		public function initSettings() {
			$current_user = wp_get_current_user();

			register_setting('samProOptions', SAM_PRO_OPTIONS_NAME);

			self::addSettingsTab('general_tab', array(
				array("sam_general_section", __("General Settings", SAM_PRO_DOMAIN), array(&$this, "drawGeneralSection"), 'sam-pro-settings'),
				array("sam_ext_section", __('Extended Options', SAM_PRO_DOMAIN), array(&$this, 'drawExtSection'), 'sam-pro-settings'),
				array("sam_layout_section", __("Admin Layout", SAM_PRO_DOMAIN), array(&$this, "drawLayoutSection"), 'sam-pro-settings'),
				array('sam_rules_section', __('Rules', SAM_PRO_DOMAIN), array(&$this, 'drawRulesSection'), 'sam-pro-settings'),
				array("sam_deactivate_section", __("Plugin Deactivating", SAM_PRO_DOMAIN), array(&$this, "drawDeactivateSection"), 'sam-pro-settings')
			), 'tabs-1', __('General', SAM_PRO_DOMAIN), 'icon-cog');
			self::addSettingsTab('embed_tab', array(
				array("sam_single_section", __("Content", SAM_PRO_DOMAIN), array(&$this, "drawSingleSection"), 'sam-pro-settings')
			), 'tabs-2', __('Embedding', SAM_PRO_DOMAIN), 'icon-magic');
			self::addSettingsTab('google_tab', array(
				array("sam_dfp_section", __("Google DFP Settings", SAM_PRO_DOMAIN), array(&$this, "drawDFPSection"), 'sam-pro-settings'),
				array('sam_adsense_section', __('Google AdSense', SAM_PRO_DOMAIN), array(&$this, 'drawAdsenseSection'), 'sam-pro-settings')
			), 'tabs-3', __('Google', SAM_PRO_DOMAIN), 'icon-globe');
			self::addSettingsTab('mailer_tab', array(
				array('sam_mailer_section', __('Mailing System', SAM_PRO_DOMAIN), array(&$this, 'drawMailerSection'), 'sam-pro-settings'),
				array('sam_mailer_data_section', __('Mail Data', SAM_PRO_DOMAIN), array(&$this, 'drawMailerDataSection'), 'sam-pro-settings'),
				array('sam_mailer_content_section', __('Mail Content', SAM_PRO_DOMAIN), array(&$this, 'drawMailerContentSection'), 'sam-pro-settings'),
				array('sam_mailer_preview_section', __('Preview', SAM_PRO_DOMAIN), array(&$this, 'drawPreviewSection'), 'sam-pro-settings')
			), 'tabs-4', __('Mailer', SAM_PRO_DOMAIN), 'icon-mail-alt');
			self::addSettingsTab('tools_tab', array(
				array("sam_statistic_section", __("Statistics Settings", SAM_PRO_DOMAIN), array(&$this, "drawStatisticsSection"), 'sam-pro-settings'),
				array('sam_scavenger_section', __('Scavenge', SAM_PRO_DOMAIN), array(&$this, 'drawScavengerSection'), 'sam-pro-settings'),
				array("sam_html_section", __("HTML Settings", SAM_PRO_DOMAIN), array(&$this, "drawHtmlSection"), "sam-pro-settings")
			), 'tabs-6', __('Tools', SAM_PRO_DOMAIN), 'icon-wrench');
			do_action('sam_pro_settings_tabs');

			add_settings_field('edition', 'Edition', array(&$this, 'drawHiddenOption'), 'sam-pro-settings', 'sam_general_section', array('hidden' => true));
			add_settings_field('adCycle', __("Views per Cycle", SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_general_section', array('description' => __('Number of hits of one ad for a full cycle of rotation (maximal activity).', SAM_PRO_DOMAIN)));
			add_settings_field('access', __('Minimum Level for the Menu Access', SAM_PRO_DOMAIN), array(&$this, 'drawJSliderOption'), 'sam-pro-settings', 'sam_general_section', array('description' => __('Who can use menu of plugin - Minimum User Level needed for access to menu of plugin. In any case only Super Admin and Administrator can use Settings Menu of SAM Plugin.', SAM_PRO_DOMAIN), 'options' => array('manage_network' => __('Super Admin', SAM_PRO_DOMAIN), 'manage_options' => __('Administrator', SAM_PRO_DOMAIN), 'edit_others_posts' => __('Editor', SAM_PRO_DOMAIN), 'publish_posts' => __('Author', SAM_PRO_DOMAIN), 'edit_posts' => __('Contributor', SAM_PRO_DOMAIN)), 'values' => array('manage_network', 'manage_options', 'edit_others_posts', 'publish_posts', 'edit_posts')));
			add_settings_field('adShow', __("Ad Output Mode", SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_general_section', array('description' => __('Standard (PHP) mode is more faster but is not compatible with caching plugins. If your blog use caching plugin (i.e WP Super Cache or W3 Total Cache) select "Caching Compatible (Javascript)" mode.', SAM_PRO_DOMAIN), 'options' => array('php' => __('Standard (PHP)', SAM_PRO_DOMAIN), 'js' => __('Caching Compatible (Javascript)', SAM_PRO_DOMAIN)), 'warning' => 'cache'));
			add_settings_field('adDisplay', __("Display Ad Source in", SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_general_section', array('description' => __('Target window (tab) for advertisement source.', SAM_PRO_DOMAIN), 'options' => array('blank' => __('New Window (Tab)', SAM_PRO_DOMAIN), 'self' => __('Current Window (Tab)', SAM_PRO_DOMAIN))));
			add_settings_field('bbpEnabled', __('Allow displaying ads on bbPress forum pages', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_general_section', array('label_for' => 'bbpEnabled', 'checkbox' => true, 'warning' => 'forum', 'enabled' => ( (defined('SAM_PRO_BBP')) ? SAM_PRO_BBP  : false )));
			add_settings_field('wptouchEnabled', __('Allow displaying ads in the header of WPtouch mobile theme', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_general_section', array('label_for' => 'wptouchEnabled', 'checkbox' => true, 'warning' => 'mobile', 'hide' => (defined('SAM_PRO_WPTOUCH')) ? !SAM_PRO_WPTOUCH : true));
			add_settings_field('spkey', __('Key', SAM_PRO_DOMAIN), array(&$this, 'drawHiddenOption'), 'sam-pro-settings', 'sam_general_section', array('hidden' => true));
			add_settings_field('site_admin_url', __('Admin URL', SAM_PRO_DOMAIN), array(&$this, 'drawHiddenOption'), 'sam-pro-settings', 'sam_general_section', array('hidden' => true));
			do_action('sam_pro_settings_fields_general_section');

			add_settings_field('bpAdsId', __("Ad Object before content", SAM_PRO_DOMAIN), array(&$this, 'drawSelectOptionEx'), 'sam-pro-settings', 'sam_single_section', array('description' => '', /*'group' => array('slave' => null, 'master' => false, 'title' => __('Ad Object', SAM_PRO_DOMAIN).':')*/));
			add_settings_field('beforePost', __("Allow embedding of Ad Object before post/page content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'beforePost', 'checkbox' => true));
			add_settings_field('bpExcerpt', __('Allow embedding of Ad Object before post/page or post/page excerpt in the loop', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bpExcerpt', 'checkbox' => true));
			add_settings_field('bbpBeforePost', __("Allow embedding of Ad Object before bbPress Forum topic content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bbpBeforePost', 'checkbox' => true, 'hide' => self::hideBbpOptions()));
			add_settings_field('bbpList', __("Allow embedding of Ad Object into bbPress Forum forums/topics lists", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bbpList', 'checkbox' => true, 'hide' => self::hideBbpOptions()));
			add_settings_field('bpUseCodes', __("Allow using predefined Ad Object HTML tags (before and after codes)", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bpUseCodes', 'checkbox' => true));
			add_settings_field('mpAdsId', __("Ad Object within the content", SAM_PRO_DOMAIN), array(&$this, 'drawSelectOptionEx'), 'sam-pro-settings', 'sam_single_section', array('description' => '', /*'group' => array('slave' => null, 'master' => false, 'title' => __('Ad Object', SAM_PRO_DOMAIN).':')*/));
			add_settings_field('middlePost', __("Allow embedding of Ad Object into the middle of post/page content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'middlePost', 'checkbox' => true));
			add_settings_field('bbpMiddlePost', __("Allow embedding of Ad Object into the middle of bbPress Forum topic content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bbpMiddlePost', 'checkbox' => true, 'hide' => self::hideBbpOptions()));
			add_settings_field('mpUseCodes', __("Allow using predefined Ad Object HTML tags (before and after codes)", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'mpUseCodes', 'checkbox' => true));
			add_settings_field('apAdsId', __("Ad Object after content", SAM_PRO_DOMAIN), array(&$this, 'drawSelectOptionEx'), 'sam-pro-settings', 'sam_single_section', array('description' => '', /*'group' => array('slave' => null, 'master' => false, 'title' => __('Ad Object', SAM_PRO_DOMAIN).':')*/));
			add_settings_field('afterPost', __("Allow embedding of Ad Object after post/page content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'afterPost', 'checkbox' => true));
			add_settings_field('bbpAfterPost', __("Allow embedding of Ad Object after bbPress Forum topic content", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'bbpAfterPost', 'checkbox' => true, 'hide' => self::hideBbpOptions()));
			add_settings_field('apUseCodes', __("Allow using predefined Ad Object HTML tags (before and after codes)", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'apUseCodes', 'checkbox' => true));
			add_settings_field('wptAdsId', __("Ad Object in the WPtouch header", SAM_PRO_DOMAIN), array(&$this, 'drawSelectOptionEx'), 'sam-pro-settings', 'sam_single_section', array('description' => '', 'hide' => self::hideWptOptions()));
			add_settings_field('wptAd', __("Allow embedding of Ad Object in the header of WPtouch mobile theme plugin", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_single_section', array('label_for' => 'wptAd', 'checkbox' => true, 'hide' => self::hideWptOptions()));
			do_action('sam_pro_settings_fields_single_section');

			add_settings_field('useSWF', __('I use (plan to use) my own flash (SWF) banners. In other words, allow loading the script "SWFObject" on the pages of the blog.', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_ext_section', array('label_for' => 'useSWF', 'checkbox' => true));
			add_settings_field('errorlog', __('Turn on/off the error log.', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_ext_section', array('label_for' => 'errorlog', 'checkbox' => true));
			add_settings_field('errorlogFS', __('Turn on/off the error log for Front Side.', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_ext_section', array('label_for' => 'errorlogFS', 'checkbox' => true));
			do_action('sam_pro_settings_fields_ext_section');

			add_settings_field('useDFP', __("Allow using Google DoubleClick for Publishers (DFP) rotator codes", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_dfp_section', array('label_for' => 'useDFP', 'checkbox' => true));
			add_settings_field('dfpMode', __('Google DFP Mode', SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_dfp_section', array('options' => array('gam' => __('GAM (Google Ad Manager)', SAM_PRO_DOMAIN), 'gpt' => __('GPT (Google Publisher Tag)', SAM_PRO_DOMAIN)), 'description' => __('Select DFP Tags Mode.', SAM_PRO_DOMAIN)));
			add_settings_field('dfpPub', __("Google DFP Pub Code (GAM)", SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_dfp_section', array('description' => __('Your Google DFP Pub code. i.e:', SAM_PRO_DOMAIN).' ca-pub-0000000000000000. '.__('Only for GAM mode.', SAM_PRO_DOMAIN), 'width' => '200px'));
			add_settings_field('dfpNetworkCode', __('Google DFP Network Code (GPT)', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_dfp_section', array('description' => __('Network Code of Your DFP Ad Network. Only for GPT mode.', SAM_PRO_DOMAIN), 'width' => '200px'));
			do_action('sam_pro_settings_fields_dfp_section');
			
			add_settings_field('adsensePub', __('Google AdSense Pub Code', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_adsense_section', array('description' => __('Your Google AdSense Pub Code.'), 'placeholder' => 'ca-pub-0000000000000000', 'width' => '100%'));
			add_settings_field('enablePageLevelAds', __("Allow using Google AdSense page-level (<span id='anchor-help'>anchor/overlay</span> or/and <span id='vignette-help'>vignette</span>) ads on mobile devices", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_adsense_section', array('label_for' => 'enablePageLevelAds', 'checkbox' => true, 'description' => __('Do not forget to adjust your Google AdSense settings on settings page (My Ads -> Page-level ads).', SAM_PRO_DOMAIN)));

			add_settings_field('detectBots', __("Allow Bots and Crawlers detection", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_statistic_section', array('label_for' => 'detectBots', 'checkbox' => true));
			add_settings_field('detectingMode', __("Accuracy of Bots and Crawlers Detection", SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_statistic_section', array('description' => __("If bot is detected hits of ads won't be counted. Use with caution! More exact detection requires more server resources.", SAM_PRO_DOMAIN), 'options' => array( 'inexact' => __('Inexact detection', SAM_PRO_DOMAIN), 'exact' => __('Exact detection', SAM_PRO_DOMAIN), 'more' => __('More exact detection', SAM_PRO_DOMAIN), 'js' => 'JS')));
			add_settings_field('stats', __('Allow to collect and to store statistical data', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_statistic_section', array('label_for' => 'stats', 'checkbox' => true));
			add_settings_field('keepStats', __('Keep Statistical Data', SAM_PRO_DOMAIN), array(&$this, 'drawSelectOption'), 'sam-pro-settings', 'sam_statistic_section', array('description' => __('Period of keeping statistical data (excluding current month).', SAM_PRO_DOMAIN), 'options' => array(0 => __('All Time', SAM_PRO_DOMAIN), 1 => __('One Month', SAM_PRO_DOMAIN), 3 => __('Three Months', SAM_PRO_DOMAIN), 6 => __('Six Months', SAM_PRO_DOMAIN), 12 => __('One Year', SAM_PRO_DOMAIN))));
			add_settings_field('currency', __("Display of Currency", SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_statistic_section', array('description' => __("Define display of currency. Auto - auto detection of currency from blog settings. USD, EUR - Forcing the display of currency to U.S. dollars or Euro.", SAM_PRO_DOMAIN), 'options' => array( 'auto' => __('Auto', SAM_PRO_DOMAIN), 'usd' => __('USD', SAM_PRO_DOMAIN), 'euro' => __('EUR', SAM_PRO_DOMAIN))));
			do_action('sam_pro_settings_fields_statistic_section');

			add_settings_field('moveExpired', __('Move expired ads to trash', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_scavenger_section', array('label_for' => 'moveExpired', 'checkbox' => true));
			add_settings_field('keepExpired', __('Permanently delete an expired ads', SAM_PRO_DOMAIN), array(&$this, 'drawSelectOption'), 'sam-pro-settings', 'sam_scavenger_section', array('options' => array(__('immediately', SAM_PRO_DOMAIN), __('after 1 month', SAM_PRO_DOMAIN), __('after 2 months', SAM_PRO_DOMAIN), __('after 3 months', SAM_PRO_DOMAIN), __('never', SAM_PRO_DOMAIN))));
			do_action('sam_pro_settings_fields_scavenger_section');

			add_settings_field('samClasses', __("Use Classes:", SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_html_section', array('description' => __('Select method of the classes naming.'), 'options' => array('default' => __('Default', SAM_PRO_DOMAIN), 'custom' => __('Custom', SAM_PRO_DOMAIN))));
			add_settings_field('containerClass', __('Custom SAM Container Class', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_html_section', array('width' => '200px'));
			add_settings_field('placeClass', __('Custom Place Class', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_html_section', array('width' => '200px'));
			add_settings_field('adClass', __('Custom Ad Class', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_html_section', array('width' => '200px', 'description' => __("Class names should consist of two parts (only letters and numbers) separated by symbol '-', ie 'bla-bla' or 'bye-bye'.", SAM_PRO_DOMAIN)));
			do_action('sam_pro_settings_fields_html_section');

			add_settings_field('itemsPerPage', __("Items per Page", SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_layout_section', array('description' => __('How many rows of the items list will be shown on one page of the data grid.', SAM_PRO_DOMAIN)));
			do_action('sam_pro_settings_fields_layout_section');

			add_settings_field('rule_id', __('Allow to use rule for posts/pages IDs', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_id', 'checkbox' => true));
			add_settings_field('rule_categories', __('Allow to use rule for categories', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_categories', 'checkbox' => true));
			add_settings_field('rule_tags', __('Allow to use rule for tags', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_tags', 'checkbox' => true));
			add_settings_field('rule_authors', __('Allow to use rule for authors', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_authors', 'checkbox' => true));
			add_settings_field('rule_taxes', __('Allow to use rule for Custom Taxonomies Terms', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_taxes', 'checkbox' => true));
			add_settings_field('rule_types', __('Allow to use rule for Custom Posts Types', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_types', 'checkbox' => true));
			add_settings_field('rule_schedule', __('Allow to use rule for scheduling', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_schedule', 'checkbox' => true));
			add_settings_field('rule_hits', __('Allow to use rule for restrictions by hits', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_hits', 'checkbox' => true));
			add_settings_field('rule_clicks', __('Allow to use rule for restrictions by clicks', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_rules_section', array('label_for' => 'rule_clicks', 'checkbox' => true));
			do_action('sam_pro_settings_fields_rules_section');

			add_settings_field('deleteOptions', __("Delete plugin options during deactivating plugin", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_deactivate_section', array('label_for' => 'deleteOptions', 'checkbox' => true));
			add_settings_field('deleteDB', __("Delete database tables of plugin during deactivating plugin", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_deactivate_section', array('label_for' => 'deleteDB', 'checkbox' => true));
			add_settings_field('deleteFolder', __("Delete custom images folder of plugin during deactivating plugin", SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_deactivate_section', array('label_for' => 'deleteFolder', 'checkbox' => true));
			do_action('sam_pro_settings_fields_deactivate_section');

			add_settings_field('mailer', __('Allow SAM Mailing System to send statistical data to advertisers', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_section', array('label_for' => 'mailer', 'checkbox' => true));
			add_settings_field('mail_period', __('Periodicity of sending reports', SAM_PRO_DOMAIN), array(&$this, 'drawRadioOption'), 'sam-pro-settings', 'sam_mailer_section', array('options' => array('monthly' => __('Monthly', SAM_PRO_DOMAIN), 'weekly' => __('Weekly', SAM_PRO_DOMAIN))));
			do_action('sam_pro_settings_fields_mailer_section');

			add_settings_field('mail_hits', __('Ad Hits (Number of shows of the advertisement)', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_data_section', array('label_for' => 'mail_hits', 'checkbox' => true));
			add_settings_field('mail_clicks', __('Ad Clicks (Number of clicks on the advertisement)', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_data_section', array('label_for' => 'mail_clicks', 'checkbox' => true));
			add_settings_field('mail_cpm', __('CPM (Cost per thousand impessions, calculated value)', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_data_section', array('label_for' => 'mail_cpm', 'checkbox' => true));
			add_settings_field('mail_cpc', __('CPC (Cost per click, calculated value)', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_data_section', array('label_for' => 'mail_cpc', 'checkbox' => true));
			add_settings_field('mail_ctr', __('CTR (Click through rate, calculated value)', SAM_PRO_DOMAIN), array(&$this, 'drawCheckboxOption'), 'sam-pro-settings', 'sam_mailer_data_section', array('label_for' => 'mail_ctr', 'checkbox' => true));
			do_action('sam_pro_settings_fields_mailer_data_section');

			add_settings_field('mail_subject', __('Mail Subject', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('Mail subject of sending email.', SAM_PRO_DOMAIN), 'width' => '70%'));
			add_settings_field('mail_greeting', __('Mail Greeting String', SAM_PRO_DOMAIN), array(&$this, 'drawTextOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('Greeting string of sending email.', SAM_PRO_DOMAIN), 'width' => '70%'));
			add_settings_field('mail_text_before', __('Mail Text before statistical data table', SAM_PRO_DOMAIN), array(&$this, 'drawTextareaOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('Some text before statistical data table of sending email.', SAM_PRO_DOMAIN), 'height' => '75px'));
			add_settings_field('mail_text_after', __('Mail Text after statistical data table', SAM_PRO_DOMAIN), array(&$this, 'drawTextareaOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('Some text after statistical data table of sending email.', SAM_PRO_DOMAIN), 'height' => '75px'));
			add_settings_field('mail_warning', __('Mail Warning 1', SAM_PRO_DOMAIN), array(&$this, 'drawTextareaOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('This text will be placed at the end of sending email.', SAM_PRO_DOMAIN), 'height' => '50px'));
			add_settings_field('mail_message', __('Mail Warning 2', SAM_PRO_DOMAIN), array(&$this, 'drawTextareaOption'), 'sam-pro-settings', 'sam_mailer_content_section', array('description' => __('This text will be placed at the very end of sending email.', SAM_PRO_DOMAIN), 'height' => '50px'));
			do_action('sam_pro_settings_fields_mailer_content_section');

			add_settings_field('mail_preview', __('Mail Preview', SAM_PRO_DOMAIN).':', array(&$this, 'drawPreviewMail'), 'sam-pro-settings', 'sam_mailer_preview_section', array('user' => array('owner' => $current_user->user_login, 'owner_name' => $current_user->display_name, 'owner_mail' => $current_user->user_email)));
			do_action('sam_pro_settings_fields_mailer_preview_section');

			register_setting('sam-pro-settings', SAM_PRO_OPTIONS_NAME, array(&$this, 'sanitizeSettings'));
		}

		public function checkMaintenanceDate( $period ) {
			$mDate = get_transient( 'sam_pro_maintenance_date' );
			$force = (false === $mDate);
			$trDate = (false !== $mDate) ? new DateTime($mDate) : false;
			$date = new DateTime('now');
			if($period == 'monthly') {
				$date->modify('+1 month');
				$nextDate = new DateTime($date->format('Y-m-01 02:00'));
				$diff = $nextDate->format('U') - $_SERVER['REQUEST_TIME'];
			}
			else {
				$dd = 8 - ((integer) $date->format('N'));
				$date->modify("+{$dd} day");
				$nextDate = new DateTime($date->format('Y-m-d 02:00'));
				$diff = (8 - ((integer) $date->format('N'))) * DAY_IN_SECONDS;
			}
			$format = get_option('date_format').' '.get_option('time_format');
			if(false !== $trDate) {
				$trDiff = $nextDate->diff($trDate);
				$force = ((int)$trDiff->days <> 0);
			}
			if ($force) set_transient( 'sam_pro_maintenance_date', $nextDate->format($format), $diff );
		}

		public function sanitizeSettings( $input ) {
			global $wpdb;

			$pTable = $wpdb->prefix . "sampro_places";
			$sql = "SELECT DISTINCT sp.dfp AS block, sp.width, sp.height, CONCAT('[', sp.width, ', ', sp.height, ']') AS sizes
FROM {$pTable} sp WHERE sp.amode = 2;";
			$rows = $wpdb->get_results($sql, ARRAY_A);
			$blocks = array();
			$blocks2 = array();
			$pub = explode('-', $input['dfpPub']);
			$divStr = (is_array($pub)) ? $pub[count($pub) - 1] : rand(1111111, 9999999);
			$div = "spa-dfp-{$divStr}";
			$k = 0;
			foreach($rows as $value) {
				array_push($blocks, $value['block']);
				array_push($blocks2, array(
					'name' => $value['block'],
					'size' => $value['sizes'],
					'width' => $value['width'],
					'height' => $value['height'],
					'div' => $div.'-'.$k
				));

				$k++;
			}
			$output = $input;

			$intNames = array(
				'keepStats',
				'keepExpired',
				'clOffset',
				'mpLimit',
				'mpPos',
				'mpOffset',
				'mpTail',
				'welcome'
			);

			foreach($intNames as $name)
				$output[$name] = (isset($input[$name])) ? (integer)$input[$name] : 0;

			$boolNames = array(
				'mailer',
				'detectBots',
				'deleteOptions',
				'deleteDB',
				'deleteFolder',
				'beforePost',
				'bpUseCodes',
				'bpExcerpt',
				'bbpBeforePost',
				'bbpList',
				'middlePost',
				'mpUseCodes',
				'mpRepeat',
				'bbpMiddlePost',
				'afterPost',
				'apUseCodes',
				'bbpAfterPost',
				'beforeLoop',
				'blUseCodes',
				'afterLoop',
				'alUseCodes',
				'contentLoop',
				'clUseCodes',
				'clEach',
				'loopHome',
				'useDFP',
				'useSWF',
				'errorlog',
				'errorlogFS',
				'bbpActive',
				'bbpEnabled',
				'bbpBeforeLoop',
				'bbpAfterLoop',
				'bbpContentLoop',
				'bbpBeforeTopics',
				'bbpAfterTopics',
				'mail_hits',
				'mail_clicks',
				'mail_cpm',
				'mail_cpc',
				'mail_ctr',
				'mail_preview',
				'stats',
				'rule_id',
				'rule_categories',
				'rule_tags',
				'rule_authors',
				'rule_taxes',
				'rule_types',
				'rule_schedule',
				'rule_hits',
				'rule_clicks',
				'rule_geo',
				'moveExpired',
				'table_est_view',
				'enablePageLevelAds'
			);
			foreach($boolNames as $name) {
				$output[$name] = ((isset($input[$name])) ? $input[$name] : 0);
			}

			$output['dfpBlocks'] = $blocks;
			$output['dfpBlocks2'] = $blocks2;

			$output['table_title'] = (empty($input['table_title'])) ? __('Name', SAM_PRO_DOMAIN) : $input['table_title'];
			$output['table_desc'] = (empty($input['table_desc'])) ? __('Description', SAM_PRO_DOMAIN) : $input['table_desc'];
			$output['table_buy'] = (empty($input['table_buy'])) ? __('You Buy', SAM_PRO_DOMAIN) : $input['table_buy'];
			$output['table_size'] = (empty($input['table_size'])) ? __('Size', SAM_PRO_DOMAIN) : $input['table_size'];
			$output['table_price'] = (empty($input['table_price'])) ? __('Price', SAM_PRO_DOMAIN) : $input['table_price'];
			$output['table_space'] = (empty($input['table_space'])) ? __('Space', SAM_PRO_DOMAIN) : $input['table_space'];
			$output['table_single'] = (empty($input['table_single'])) ? __('Single Ad', SAM_PRO_DOMAIN) : $input['table_single'];
			$output['table_est'] = (empty($input['table_est'])) ? __('Estimated impressions', SAM_PRO_DOMAIN) : $input['table_est'];

      $output['site_admin_url'] = ($input['site_admin_url'] == admin_url('admin.php')) ? $input['site_admin_url'] : admin_url('admin.php');

			$output = apply_filters('sam_pro_settings_sanitize_settings', $output);

			return $output;
		}

		// Sections
		public function drawGeneralSection() {
			echo '<p>'.__('There are general options.', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawSingleSection() {
			echo '<p>'.__('Use these parameters for allowing/defining Ads Objects which will be automatically inserted into post/page content.', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawLoopSection() {
			echo '<p>'.__('Use these parameters for allowing/defining Ads Objects which will be automatically inserted into pages of archives.', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawExtSection() {
			echo '';
		}

		public function drawDFPSection() {
			echo '<p>'.__('Adjust parameters of your Google DFP account.', SAM_PRO_DOMAIN).'</p>';
		}
		
		public function drawAdsenseSection() {
			echo "<p></p>";
		}

		public function drawStatisticsSection() {
			echo '<p>'.__('Adjust parameters of plugin statistics.', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawLayoutSection() {
			echo '<p>'.__('This options define layout for Ads Managin Pages.', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawRulesSection() {
			echo '<p>' . __('The rules of the extended restrictions of the Ads. You can reduce the length of the ad request by disabling unused rules.', SAM_PRO_DOMAIN) . '</p>';
		}

		public function drawDeactivateSection() {
			echo '<p>'.__('Are you allow to perform these actions during deactivating plugin?', SAM_PRO_DOMAIN).'</p>';
		}

		public function drawRequestSection() {
			echo "<p><strong>".__('Advertising Request Form Labels', SAM_PRO_DOMAIN)."</strong></p>";
		}

		public function drawTableSection() {}

		public function drawScavengerSection() {
			$time = get_transient( 'sam_pro_scavenge_time' );
			$service = ($time === false) ? '' : __('Next cleaning is scheduled for', SAM_PRO_DOMAIN)." <code>{$time}</code>... ";
			echo '<p>'.__('Parameters of moving to the trash and removing of expired ads.', SAM_PRO_DOMAIN)." {$service}</p>";
		}

		public function drawHtmlSection() {
			echo '<p>'.__("Define HTML classes for plugin's output tags.", SAM_PRO_DOMAIN).'</p>';
		}

		public function drawMailerSection() {
			$options = parent::getSettings();

			if((int)$options['mailer']) {
				self::checkMaintenanceDate($options['mail_period']);
				$time = get_transient( 'sam_pro_maintenance_date' );
				if($time !== false)
					echo "<p>".__("Next mailing is scheduled on", SAM_PRO_DOMAIN)." <code>{$time}</code>... "."</p>";
			}
			else echo "<p>".__("Adjust parameters of Mailing System.", SAM_PRO_DOMAIN)."</p>";
		}

		public function drawMailerDataSection() {
			$str = __('Adjust Reporting Data. Name and Description of the ad will be included to the reporting data in any case.', SAM_PRO_DOMAIN);
			echo "<p>{$str}</p>";
		}

		public function drawMailerContentSection() {
			$str = __('Adjust Mail Content.', SAM_PRO_DOMAIN) . ' ';
			$str .= __('You can use the following short codes in the parameter fields of this section:', SAM_PRO_DOMAIN) . "<ul>";
			$str .= "<li><code>[name]</code> - " . __("will be replaced with the advertiser's name.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "<li><code>[site]</code> - " . __("will be replaced with the name of your site.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "<li><code>[month]</code> - " . __("will be replaced with the name of the month of the reporting period.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "<li><code>[year]</code> - " . __("will be replaced with the year of the reporting period.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "<li><code>[first]</code> - " . __("will be replaced with the first date of the reporting period.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "<li><code>[last]</code> - " . __("will be replaced with the last date of the reporting period.", SAM_PRO_DOMAIN) . "</li>";
			$str .= "</ul>";
			echo "<p>{$str}</p>";
		}

		public function drawPreviewSection() {
			return '';
		}

		public function settingsTabsHeader( $tabs ) {
			$out = "<ul>";

			foreach($tabs as $tab) {
				if(isset($tab['uri']) && isset($tab['name'])) {
					$tabUri = $tab['uri'];
					$tabName = $tab['name'];
					$tabClass = $tab['class'];
					$class = ((!empty($tab['class'])) ? "<i class='{$tabClass}'></i>" : '' );
					$out .= "<li><a href='#{$tabUri}'>{$class}{$tabName}</a></li>";
				}
			}

			$out .= "</ul>";

			return $out;
		}

		// Options
		public function drawTextOption( $id, $args ) {
			$settings = parent::getSettings();
			$width = (isset($args['width'])) ? $args['width'] : 55;
			$type = (isset($args['type'])) ? $args['type'] : 'text';
			$min = (isset($args['min'])) ? " min='{$args['min']}'" : '';
			$max = (isset($args['max'])) ? " max='{$args['max']}'" : '';
			$placeholder = ( isset( $args['placeholder'] ) ) ? $args['placeholder'] : '';
			?>
			<input id="<?php echo $id; ?>"
			       name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
			       type="<?php echo $type; ?>"<?php echo $min . $max; ?>
			       value="<?php echo $settings[$id]; ?>"
			       style="<?php echo "width: {$width};" ?>" <?php if(!empty($placeholder)) echo "placeholder='{$placeholder}'"?> />
		<?php
		}

		public function drawTextareaOption( $id, $args ) {
			$settings = parent::getSettings();
			if(isset($args['height'])) $height = $args['height'];
			else $height = '100px';
			?>
			<textarea id="<?php echo $id; ?>"
			          name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
			          style="width: 100%; height: <?php echo $height ?>;"><?php echo $settings[$id]; ?></textarea>
		<?php
		}

		public function drawCheckboxOption( $id, $args ) {
			$settings = parent::getSettings();
			$disabled = '';
			$hide = '';
			if(isset($args['enabled'])) $disabled = (($args['enabled']) ? '' : 'disabled="disabled"');
			if(isset($args['hide'])) $hide = ( ($args['hide']) ? " style='display: none;'" : '');
			?>
			<input id="<?php echo $id; ?>"
				<?php checked('1', $settings[$id]); ?>
				     name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
				     type="checkbox"
				     value="1"
				<?php echo $disabled.$hide; ?>>
		<?php
		}

		public function drawSelectOption( $id, $args ) {
			$options = $args['options'];
			$settings = parent::getSettings();

			?>
			<select id="<?php echo $id; ?>" name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>">
				<?php
				foreach($options as $val=>$name)
					echo "<option value='$val' ".selected($val, $settings[$id], false).">$name</option>";
				?>
			</select>
		<?php
		}

		public function drawSelectOptionEx( $id, $args ) {
			$settings = parent::getSettings();
			$options = $this->adsObjects;
			$hidden = (isset($args['hide']) && $args['hide']) ? "style='display:none;'" : '';
			?>
			<select id="<?php echo $id; ?>" name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>" <?php echo $hidden; ?>>
				<?php
				foreach($options as $group) {
					echo "<optgroup label='{$group['title']}'>";
					foreach($group['data'] as $val) {
						$sel = selected($val['ival'], $settings[$id], false);
						echo "<option value='{$val['ival']}' {$sel}>{$val['title']}</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<?php
		}

		public function drawSelectOptionX( $id, $args ) {
			global $wpdb;
			$pTable = $wpdb->prefix . "sampro_places";

			$ids = $wpdb->get_results("SELECT $pTable.id, $pTable.name FROM $pTable WHERE $pTable.trash IS FALSE", ARRAY_A);
			$settings = parent::getSettings();
			?>
			<select id="<?php echo $id; ?>" name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>">
				<?php
				foreach($ids as $value) {
					echo "<option value='{$value['id']}' ".selected($value['id'], $settings[$id], false)." >{$value['name']}</option>";
				}
				?>
			</select>
		<?php
		}

		public function drawSelectOptionP( $id, $args ) {
			global $wpdb;
			$posts = $wpdb->prefix . 'posts';
			$sql = "SELECT wp.ID, wp.post_title FROM {$posts} wp WHERE wp.post_status = 'publish' AND wp.post_type = 'page';";
			$pages = $wpdb->get_results($sql, ARRAY_A);
			$oName = SAM_PRO_OPTIONS_NAME;
			$oNone = __('None', SAM_PRO_DOMAIN);
			$settings = parent::getSettings();

			$selected = selected(0, $settings[$id], false);
			$out = "<select id='{$id}' name='{$oName}[{$id}]'><option value='0' {$selected}>{$oNone}</option>";
			if(!empty($pages)) {
				foreach ( $pages as $page ) {
					$selected = selected($page['ID'], $settings[$id], false);
					$out .= "<option value='{$page['ID']}' {$selected}>{$page['post_title']}</option>";
				}
			}
			$out .= "</select>";
			echo $out;
		}

		public function drawCascadeSelectOption( $id, $args ) {
			$settings = parent::getSettings();
			?>
			<input
				id="<?php echo $id; ?>"
				name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
				type="text"
				value="<?php echo $settings[$id]; ?>">
		<?php
		}

		public function drawRadioOption( $id, $args ) {
			$options = $args['options'];
			$settings = parent::getSettings();

			foreach ($options as $key => $option) {
				?>
				<input type="radio"
				       id="<?php echo $id.'_'.$key; ?>"
				       name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
				       value="<?php echo $key; ?>"
					<?php checked($key, $settings[$id]); ?>
					<?php if($key === 'more') disabled('', ini_get("browscap")); ?>>
				<label for="<?php echo $id.'_'.$key; ?>">
					<?php echo $option;?>
				</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<?php
			}
		}

		public function drawJSliderOption( $id, $args ) {
			//$options = $args['options'];
			$values = $args['values'];
			$settings = parent::getSettings();
			$key = array_search($settings[$id], $values);
			if($key === false) $key = 1;

			?>
			<input
				type="hidden"
				id="<?php echo $id; ?>"
				name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
				value="<?php echo $settings[$id]; ?>" >
			<div class="layout">
				<input id="role-slider" type="hidden" name="area" value="<?php echo $key; ?>"/>
			</div>
		<?php
		}

		public function drawHiddenOption( $id, $args ) {
			$settings = parent::getSettings();
			?>
			<input
				type="hidden"
				id="<?php echo $id; ?>"
				name="<?php echo SAM_PRO_OPTIONS_NAME.'['.$id.']'; ?>"
				value="<?php echo $settings[$id]; ?>">
			<?php
		}

		public function drawGeoOption( $id, $args ) {
			$options = $args['options'];
			$settings = parent::getSettings();
			echo "<div id='geo-settings' style='text-align: initial;'>";
			foreach($options as $key => $option) {
				?>
				<p>
					<input type="radio"
				    id="<?php echo $id . '_' . $key; ?>"
				    name="<?php echo SAM_PRO_OPTIONS_NAME . '[' . $id . ']'; ?>"
				    value="<?php echo $key; ?>"
						<?php checked($key, $settings[$id]); ?>>
					<label for="<?php echo $id.'_'.$key; ?>">
						<?php echo $option['name'];?>
					</label>
				</p>
				<div class="sub-content">
					<?php foreach($option['fields'] as $field => $label) { ?>
					<label for="<?php echo $field; ?>"><?php echo $label; ?>:</label>
					<input
						type="text"
						id="<?php echo $field; ?>"
						name="<?php echo SAM_PRO_OPTIONS_NAME . '[' . $field . ']'; ?>"
						value="<?php echo $settings[$field]; ?>"
						style="width: 100%;margin: 5px 0;">
					<?php } ?>
				</div>
			<?php
			}
			echo "</div>";
		}

		public function drawNone( $id, $args) {}

		public function drawPreviewMail( $id, $args ) {
			include_once('tools/sam-pro-mailer.php');

			$mail = new SamProMailer(parent::getSettings());
			$prev = $mail->buildPreview($args['user']);

			echo "<div class='graph-container'>{$prev}</div>";
		}

		public function doSettingsTabs() {}

		public function doSettingsSections( $page, $tabs ) {
			global $wp_settings_sections, $wp_settings_fields;

			if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
				return;

			echo "<div id='tabs'>\n";
			echo self::settingsTabsHeader($tabs);

			foreach ( (array) $wp_settings_sections[$page] as $section ) {
				if(isset($this->settingsTabs[ $section['id'] ]['start_tab']) && $this->settingsTabs[ $section['id'] ]['start_tab'])
					echo "<div id='{$this->settingsTabs[ $section['id'] ]['uri']}'>";

				echo "<div class='ui-sortable sam-section'>\n";
				echo "<div class='postbox opened'>\n";
				echo "<h3 class='hndle'>{$section['title']}</h3>\n";
				echo '<div class="inside">';
				call_user_func($section['callback'], $section);
				if ( !isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']]) )
					continue;
				$this->doSettingsFields($page, $section['id']);
				echo '</div>';
				echo '</div>';
				echo '</div>';
				if(isset($this->settingsTabs[ $section['id'] ]['finish_tab']) && $this->settingsTabs[ $section['id'] ]['finish_tab']) echo "</div>";
			}
			echo "</div>";
		}

		public function doSettingsFields( $page, $section ) {
			global $wp_settings_fields;

			if ( !isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section]) )
				return;

			foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
				if ( !empty($field['args']['checkbox']) ) {
					echo '<p>';
					call_user_func($field['callback'], $field['id'], $field['args']);
					echo '<label for="' . $field['args']['label_for'] . '"' . ((isset($field['args']['hide']) && $field['args']['hide']) ? 'style="display: none;"' : '' ) . '>' . $field['title'] . '</label>';
					echo '</p>';
				}
				else {
					if(isset($field['args']['group'])) {
						if($field['args']['group']['master']) {
							echo "<p><strong>{$field['title']}</strong></p>";
							echo "<div class='group-frame'><div class='cascade-item'>";
							call_user_func($field['callback'], $field['id'], $field['args']);
							echo "</div>";
						}
						else {
							echo "<div class='cascade-item'>";
							call_user_func($field['callback'], $field['id'], $field['args']);
							echo "</div><div class='cascade-body'>&nbsp;</div></div>";
						}
					}
					elseif(isset($field['args']['hidden']) || isset($field['args']['geo'])) {
						call_user_func($field['callback'], $field['id'], $field['args']);
					}
					else {
						echo '<p>';
						if ( !empty($field['args']['label_for']) )
							echo '<label for="' . $field['args']['label_for'] . '">' . $field['title'] . '</label>';
						else {
							if(!(isset($field['args']['hide']) && (bool)$field['args']['hide'])) {
								if ( isset( $field['args']['plain'] ) && $field['args']['plain'] ) {
									echo $field['title'] . '<br>';
								} else {
									echo '<strong>' . $field['title'] . '</strong><br>';
								}
							}
						}
						echo '</p>';
						echo '<p>';
						call_user_func($field['callback'], $field['id'], $field['args']);
						echo '</p>';
					}
				}
				if(!empty($field['args']['description'])) echo '<p>' . $field['args']['description'] . '</p>';
				if(!empty($field['args']['warning'])) echo self::getWarningString($field['args']['warning']);
			}
		}

		public function adminPage() {
			global $wpdb, $wp_version;

			$row = $wpdb->get_row('SELECT VERSION() AS ver', ARRAY_A);
			$sqlVersion = $row['ver'];
			$mem = ini_get('memory_limit');
			$phpStyle = ((int)$mem < 128) ? 'red' : 'green';
			$exeTime = ini_get('max_execution_time');
			$timeStyle = ((int)$exeTime < 30) ? 'red' : ( ((int)$exeTime < 50) ? 'orange' : 'green' );
			$wpMem = WP_MEMORY_LIMIT;
			$wpStyle = ((int)$wpMem < 128) ? 'red' : 'green';
			$edition = 0;
			$editions = array('Free', 'Lite', 'Full', 'Ultimate');
			if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true')	$hidden = "";
			else $hidden = " style='display:none;'";
			?>
<div class="wrap">
	<h2><?php  _e('SAM Pro Settings', SAM_PRO_DOMAIN); ?></h2>
	<div class="updated below-h2"<?php echo $hidden; ?>>
		<p>
			<strong><?php _e('SAM Pro Settings Updated.') ?></strong>
		</p>
	</div>
	<form action="options.php" method="post">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="side-info-column" class="inner-sidebar">
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('System Info', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<p>
							<?php
							echo __('SAM Pro Edition', SAM_PRO_DOMAIN).': <strong>'.$editions[$edition].'</strong><br>';
							echo __('SAM Pro Version', SAM_PRO_DOMAIN).': <strong>'.SAM_PRO_VERSION.'</strong><br>';
							echo __('SAM Pro DB Version', SAM_PRO_DOMAIN).': <strong>'.SAM_PRO_DB_VERSION.'</strong><br>';
							echo __('Wordpress Version', SAM_PRO_DOMAIN).': <strong>'.$wp_version.'</strong><br>';
							echo __('PHP Version', SAM_PRO_DOMAIN).': <strong>'.PHP_VERSION.'</strong><br>';
							echo __('MySQL Version', SAM_PRO_DOMAIN).': <strong>'.$sqlVersion.'</strong><br>';
							echo __('PHP Max Execution Time', SAM_PRO_DOMAIN).": <strong style='color:{$timeStyle};'>{$exeTime}</strong><br>";
							echo __('PHP Memory Limit', SAM_PRO_DOMAIN).": <strong style='color:{$phpStyle};'>{$mem}</strong><br>";
							echo __('WP Memory Limit', SAM_PRO_DOMAIN).": <strong style='color:{$wpStyle};'>{$wpMem}</strong>";
							?>
						</p>
						<?php
						global $samProAddonsList;

						if(!empty($samProAddonsList)) {
							$strAddons = __('Addons', SAM_PRO_DOMAIN) . ':';
							echo "<p><strong>{$strAddons}</strong><ul style='list-style: inherit !important;margin-left: 20px;'>";
							foreach($samProAddonsList as $addon) {
								$aVersion = (isset($addon['version'])) ? "({$addon['version']})" : '';
								echo "<li>{$addon['name']} {$aVersion}</li>";
							}
							echo "</ul></p>";
						}
						?>
						<p>
							<?php _e('Note! If you have detected a bug, include this data to bug report.', SAM_PRO_DOMAIN); ?>
						</p>
					</div>
				</div>
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('SAM Pro (Lite Edition)', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<a href="http://codecanyon.net/item/sam-pro-lite/12721925?ref=minimus_simplelib" target="_blank">
							<img src="<?php echo SAM_PRO_URL . 'images/upgrade-sidebar.jpg'; ?>">
						</a>
						<p><?php _e('Get more features:', SAM_PRO_DOMAIN); ?></p>
						<ul style="list-style: inherit !important;margin-left: 20px;">
							<li><?php _e("ads rotation by timer", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("online advertiser statistics", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("an advertising request form", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("geo targeting", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("extended ALE (Ads Linking and Embedding)", SAM_PRO_DOMAIN); ?></li>
						</ul>
						<p><?php _e('and', SAM_PRO_DOMAIN); ?> <a href="http://uncle-sam.info/sam-pro-lite/sam-pro-lite-info/features/" target="_blank" title="<?php _e('Features List', SAM_PRO_DOMAIN); ?>"><?php _ex('more', 'SAM Pro Lite', SAM_PRO_DOMAIN); ?></a> ...</p>
						<p style="text-align: center;">
							<a href="http://codecanyon.net/item/sam-pro-lite/12721925?ref=minimus_simplelib" target="_blank" class="button-primary" style="width: 100%;">
								<?php _e('Purchase SAM Pro (Lite Edition)', SAM_PRO_DOMAIN); ?>
							</a>
						</p>
					</div>
				</div>
				<div class="postbox opened">
					<h3 class="hndle"><?php _e('Available Addons', SAM_PRO_DOMAIN); ?></h3>
					<div class="inside">
						<ul>
							<li>
								<a href="http://uncle-sam.info/addons/advertising-request/" target="_blank">
									<img src="<?php echo SAM_PRO_URL . 'images/ad-request-plugin-ad.jpg'; ?>">
								</a>
							</li>
							<li>
								<a href="http://uncle-sam.info/addons/geo-targeting/" target="_blank">
									<img src="<?php echo SAM_PRO_URL . 'images/geo-targeting-plugin-ad.jpg'; ?>">
								</a>
							</li>
						</ul>
					</div>
				</div>
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('Resources', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<ul>
							<li><a target='_blank' href='http://uncle-sam.info'><?php _e("SAM Pro Site", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target="_blank" href="http://uncle-sam.info/sam-pro/getting-started/"><?php _e('Getting Started', SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://uncle-sam.info/category/sam-pro-free/sam-pro-free-docs/'><?php _e("Documentation", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://forum.simplelib.com/index.php?forums/sam-pro-free-edition.21/'><?php _e("Support Forum", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://www.simplelib.com/'><?php _e("Author's Blog", SAM_PRO_DOMAIN); ?></a></li>
						</ul>
					</div>
				</div>
			</div>
			<div id="post-body">
				<div id="post-body-content">
					<?php
					settings_fields('samProOptions');
					$this->doSettingsSections('sam-pro-settings', $this->settingsTabs);
					?>
					<p class="submit">
						<button id="submit-button" class="button-primary" name="Submit" type="submit">
							<?php esc_attr_e('Save Changes'); ?>
						</button>
					</p>
					<p style='color: #777777; font-size: 13px; font-style: italic;'><?php echo SAM_PRO_COPYRIGHT; ?></p>
				</div>
			</div>
		</div>
	</form>
</div>
			<?php
		}

		public function adsList() {
			include_once('sam-pro-ads-list.php');

			$ads = new SamProAdsList( parent::getSettings() );
			$ads->show();
		}

		public function adsEditor() {
			require_once('sam-pro-ad-editor.php');

			$editor = new SamProAdEditor($this->samOptions);
			$editor->show();
		}

		public function placesList() {
			include_once('sam-pro-places-list.php');

			$places = new SamProPlacesList(parent::getSettings());
			$places->show();
		}

		public function placesEditor() {
			include_once('sam-pro-place-editor.php');

			$editor = new SamProPlaceEditor( parent::getSettings() );
			$editor->show();
		}

		public function zonesList() {
			include_once('sam-pro-zones-list.php');

			$zones = new SamProZonesList(parent::getSettings());
			$zones->show();
		}

		public function zonesEditor() {
			include_once('sam-pro-zone-editor.php');

			$editor = new SamProZoneEditor(parent::getSettings());
			$editor->show();
		}

		public function blocksList() {
			include_once('sam-pro-blocks-list.php');

			$blocks = new SamProBlocksList(parent::getSettings());
			$blocks->show();
		}

		public function blocksEditor() {
			include_once('sam-pro-block-editor.php');

			$editor = new SamProBlockEditor(parent::getSettings());
			$editor->show();
		}

		public function errorLog() {
			include_once('sam-pro-error-log.php');

			$errors = new SamProErrorLog(parent::getSettings());
			$errors->show();
		}

		public function tools() {
			include_once('sam-pro-tools-page.php');

			$page = new SamProToolsPage(parent::getSettings());
			$page->show();
		}

		public function statistics() {
			$period = ((isset($_POST['period'])) ? $_POST['period'] : 0);
			$owner = ((isset($_POST['owner'])) ? $_POST['owner'] : 'all');
			$item = ((isset($_POST['item'])) ? $_POST['item'] : 0);
			$view = ((isset($_POST['view'])) ? $_POST['view'] : 'sold');
			include_once('sam-pro-statistics.php');

			$page = new SamProStatistics(parent::getSettings(), $item, $period, $owner, $view);
			$page->show();
		}

		public function advList() {
			include_once('sam-pro-advertisers-list.php');

			$page = new SamProAdvertisersList(parent::getSettings());
			$page->show();
		}

		// TinyMCE Buttons
		public function addButtons() {
			if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') && ! current_user_can(SAM_PRO_ACCESS) )
				return;

			if ( get_user_option('rich_editing') == 'true') {
				add_filter("mce_external_plugins", array(&$this, "addTinyMCEPlugin"));
				add_filter('mce_buttons', array(&$this, 'registerButton'));
			}
		}

		public function registerButton( $buttons ) {
			$options = $this->getSettings();
			array_push($buttons, "separator", "spButton");
			$buttons = apply_filters('sam_pro_register_mce_buttons', $buttons);
			return $buttons;
		}

		public function addTinyMCEPlugin( $plugin_array ) {
			$options = parent::getSettings();
			$plugin_array['spButton'] = SAM_PRO_URL.'js/sam.pro.editor.plugin.js';
			$plugin_array = apply_filters('sam_pro_mce_plugins', $plugin_array);
			return $plugin_array;
		}

		public function tinyMCEVersion( $version ) {
			return ++$version;
		}

		public function addMetaBoxes( $postType ) {
			$postTypes = array('post', 'page');
			if(in_array($postType, $postTypes)) {
				add_meta_box(
					'sam_pro_cancel_ads',
					__('SAM Pro Ad Serving', SAM_PRO_DOMAIN),
					array(&$this, 'drawMetaBox'),
					$postType,
					'advanced',
					'high'
				);
			}
		}

		public function drawMetaBox( $post ) {
			wp_nonce_field( 'sam_pro_cancel_ads_box', 'sam_pro_cancel_ads_box_nonce' );
			$val = get_post_meta( $post->ID, 'sam_pro_disable_ad_serving', true );
			$value = (empty($val)) ? 0 : 1;
			?>
			<input type="checkbox" id="sam_pro_cancel_ads_field" name="sam_pro_cancel_ads_field" value="1" <?php checked(1, $value); ?>>
			<label for="sam_pro_cancel_ads_field"><?php _e('disable ad serving', SAM_PRO_DOMAIN); ?></label>
			<?php
		}

		public function savePost( $postId ) {
			if ( ! isset( $_POST['sam_pro_cancel_ads_box_nonce'] ) ) {
				return $postId;
			}

			$nonce = $_POST['sam_pro_cancel_ads_box_nonce'];

			if ( ! wp_verify_nonce( $nonce, 'sam_pro_cancel_ads_box' ) ) {
				return $postId;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $postId;
			}

			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $postId ) ) {
					return $postId;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $postId ) ) {
					return $postId;
				}
			}

			$data = (int)isset($_POST['sam_pro_cancel_ads_field']);
			update_post_meta( $postId, 'sam_pro_disable_ad_serving', $data );
		}
	}
}