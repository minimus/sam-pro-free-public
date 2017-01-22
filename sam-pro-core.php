<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 21.12.2014
 * Time: 4:52
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( "SamProCore" ) ) {
	class SamProCore {
		protected $samOptions;
		protected $samVersions = array( 'sam' => null, 'db' => null );
		protected $nonce;

		protected $defaultSettings = array(
			'edition'          => 0,
			'adCycle'          => 1000,
			'adShow'           => 'php',                // php|js
			'adDisplay'        => 'blank',           // string or null
			'itemsPerPage'     => 10,             // int
			'deleteOptions'    => 0,             // bool
			'deleteDB'         => 0,                  // bool
			'deleteFolder'     => 0,              // bool
			// Auto Injection Content
			'beforePost'       => 0,                // bool
			'bpAdsId'          => 0,                   // string
			//'bpAdsType' => 1,
			'bpUseCodes'       => 0,                // bool
			'bpExcerpt'        => 0,                 // bool
			'bbpBeforePost'    => 0,             // bool
			'bbpList'          => 0,                   // bool
			'middlePost'       => 0,                // bool
			'mpAdsId'          => 0,                   // string
			'mpUseCodes'       => 0,                // bool
			'bbpMiddlePost'    => 0,             // bool
			'mpLimit'          => 6,                   // int
			'mpPos'            => 0,                     // int
			'mpOffset'         => 3,                  // int
			'mpTail'           => 2,                    // int
			'mpRepeat'         => 0,                  // bool
			'afterPost'        => 0,                 // bool
			'apAdsId'          => 0,                   // string
			'apUseCodes'       => 0,                // bool
			'bbpAfterPost'     => 0,              // bool
			// Auto Injection Loop
			'beforeLoop'       => 0,                // bool
			'blAdId'           => 0,                    // string
			'blUseCodes'       => 0,                // bool
			'afterLoop'        => 0,                 // bool
			'alAdId'           => 0,                    // string
			'alUseCodes'       => 0,                // bool
			'contentLoop'      => 0,               // bool
			'clAdId'           => 0,                    // string
			'clUseCodes'       => 0,                // bool
			'clOffset'         => 1,                  // int
			'clEach'           => 0,                    // bool
			'loopHome'         => 0,                  // bool
			// Auto Injection bbPress Loop
			'bbpBeforeLoop'    => 0,             // bool
			'bbpAfterLoop'     => 0,              // bool
			'bbpContentLoop'   => 0,            // bool
			'bbpBeforeTopics'  => 0,             // bool
			'bbpAfterTopics'   => 0,              // bool
			// wptouch
			'wptAdsId'         => 0,                  // int
			'wptAd'            => 0,                     // bool
			'wptouchEnabled'   => 0,            // bool
			// Other
			'useDFP'           => 0,                    // bool
			'detectBots'       => 0,                // bool
			'detectingMode'    => 'inexact',
			'currency'         => 'auto',             // usd|eur|auto
			'dfpMode'          => 'gam',               // gam|gpt
			'dfpPub'           => '',                   // string
			'dfpNetworkCode'   => '',           // string
			'dfpBlocks'        => array(),           // array
			'dfpBlocks2'       => array(),          // array
			'useSWF'           => 0,                    // bool
			'access'           => 'manage_options',     //
			'errorlog'         => 1,                  // bool
			'errorlogFS'       => 1,                // bool
			'bbpActive'        => 0,                 // bool
			'bbpEnabled'       => 0,                // bool
			// Mailer
			'mailer'           => 1,                    // bool
			'mail_subject'     => 'Ad campaign report ([month])',
			'mail_greeting'    => 'Hi! [name]!',
			'mail_text_before' => 'This is your Ad Campaign Report:',
			'mail_text_after'  => '',
			'mail_warning'     => 'You received this mail because you are an advertiser of site [site]. If time of your campaign expires or if you refuse to post your ads on our site, you will be excluded from the mailing list automatically. Thank you for your cooperation.',
			'mail_message'     => "Do not respond to this mail! This mail was sent automatically by Wordpress plugin SAM Pro Lite.",
			'mail_period'      => 'monthly',        // monthly|weekly
			'mail_hits'        => 1,                  // bool
			'mail_clicks'      => 1,                // bool
			'mail_cpm'         => 1,                   // bool
			'mail_cpc'         => 1,                   // bool
			'mail_ctr'         => 1,                   // bool
			'mail_preview'     => 0,               // bool
			// Statistics
			'stats'            => 1,                      // bool
			'keepStats'        => 0,                  // int
			'samClasses'       => 'default',
			'containerClass'   => 'sam-pro-container',
			'placeClass'       => 'sam-pro-place',
			'adClass'          => 'sam-pro-ad',
			'rule_id'          => 0,
			'rule_categories'  => 0,
			'rule_tags'        => 0,
			'rule_authors'     => 0,
			'rule_taxes'       => 0,
			'rule_types'       => 0,
			'rule_schedule'    => 1,
			'rule_hits'        => 0,
			'rule_clicks'      => 0,
			'rule_geo'         => 0,
			// +++
			'spkey'            => '',
			'spkey2'           => '',
			'ip'               => 'dbip',
			'dbip_key'         => '',
			'maxmind_key'      => '',
			'maxmind2_user'    => '',
			'maxmind2_pass'    => '',
			'db_blocks'        => '',
			'db_regions'       => '',
			// Scavenger
			'moveExpired'      => 1,
			'keepExpired'      => 2,
			// Shortcodes
			'welcome'          => 0,
			'form_title'       => 'Request for advertising',
			'place'            => 'Select Advertising Area',
			'title'            => 'Ad Name',
			'desc'             => 'Ad Description',
			'alt'              => 'Ad Alternative Text',
			'banner'           => 'Ad Banner Location URL',
			'target'           => 'Ad Target URL',
			'ad_owner'         => 'Your Nickname (unique)',
			'ad_owner_name'    => 'Your Display Name',
			'ad_owner_mail'    => 'Your email',
			'button'           => 'Send Request',
			'button_class'     => '',
			'grc_secret_key'   => '',
			'grc_site_key'     => '',

			'table_title'        => '',
			'table_desc'         => '',
			'table_buy'          => '',
			'table_size'         => '',
			'table_price'        => '',
			'table_space'        => '',
			'table_single'       => '',
			'table_est'          => '',
			'table_est_view'     => 0,
			// Other
			'site_admin_url'     => '',
			// Google AdSense
			'adsensePub'         => '',
			'enablePageLevelAds' => 0
		);

		public function __construct() {
			define( 'SAM_PRO_VERSION', '1.9.9.73' );
			define( 'SAM_PRO_DB_VERSION', '1.1' );
			define( 'SAM_PRO_PATH', dirname( __FILE__ ) );
			define( 'SAM_PRO_URL', plugins_url( '/', __FILE__ ) );
			define( 'SAM_PRO_IMG_URL', SAM_PRO_URL . 'images/' );
			define( 'SAM_PRO_DOMAIN', 'sam-pro-free' );
			define( 'SAM_PRO_OPTIONS_NAME', 'samProOptions' );
			define( 'SAM_PRO_AD_IMG', WP_PLUGIN_DIR . '/sam-images/' );
			define( 'SAM_PRO_AD_URL', plugins_url( '/sam-images/' ) );
			define( 'SAM_PRO_COPYRIGHT', _x( 'SAM Pro (Free Edition) for Wordpress.', 'Copyright String', SAM_PRO_DOMAIN ) . " Copyright &copy; 2015 - 2016, <a href='http://www.simplelib.com/'>minimus</a>. " . _x( 'All rights reserved.', 'Copyright String', SAM_PRO_DOMAIN ) );

			// Constants
			define( 'SAM_PRO_IS_HOME', 1 );
			define( 'SAM_PRO_IS_SINGULAR', 2 );
			define( 'SAM_PRO_IS_SINGLE', 4 );
			define( 'SAM_PRO_IS_PAGE', 8 );
			define( 'SAM_PRO_IS_ATTACHMENT', 16 );
			define( 'SAM_PRO_IS_SEARCH', 32 );
			define( 'SAM_PRO_IS_404', 64 );
			define( 'SAM_PRO_IS_ARCHIVE', 128 );
			define( 'SAM_PRO_IS_TAX', 256 );
			define( 'SAM_PRO_IS_CATEGORY', 512 );
			define( 'SAM_PRO_IS_TAG', 1024 );
			define( 'SAM_PRO_IS_AUTHOR', 2048 );
			define( 'SAM_PRO_IS_DATE', 4096 );
			define( 'SAM_PRO_IS_POST_TYPE', 8192 );
			define( 'SAM_PRO_IS_POST_TYPE_ARCHIVE', 16384 );

			$this->getSettings( true );
			$this->getVersions( true );

			add_action( 'plugins_loaded', array( &$this, 'maintenance' ) );
			add_action( 'plugins_loaded', array( &$this, 'scavenge' ) );
			add_action( 'init', array( &$this, 'createNonce' ) );
			add_filter( 'nonce_user_logged_out', array( &$this, 'loggedOutUserNonce' ), 10, 2 );

			self::registerAdminActions();
			self::registerFrontActions();
		}

		public function getSettings( $force = false ) {
			$forceUpdate = false;
			if ( $force ) {
				$options     = get_option( SAM_PRO_OPTIONS_NAME, '' );
				$defOptions  = $this->defaultSettings;
				$forceUpdate = ( $options == '' || ( is_array( $options ) && count( $options ) != count( $defOptions ) ) );
				if ( $options !== '' ) {
					$defOptions = apply_filters( 'sam_pro_set_default_options', $defOptions );
					$defOptions = array_merge( $defOptions, $options );
					/*foreach( $options as $key => $option ) {
						$defOptions[$key] = $option;
					}*/
				}
				if ( ! isset( $defOptions['spkey'] ) || ( isset( $defOptions['spkey'] ) && empty( $defOptions['spkey'] ) ) ) {
					$spKey               = bin2hex( openssl_random_pseudo_bytes( 32, $cstrong ) );
					$defOptions['spkey'] = $spKey;
				}
				if ( ! isset( $defOptions['spkey2'] ) || ( isset( $defOptions['spkey2'] ) && empty( $defOptions['spkey2'] ) ) ) {
					$spKey                = bin2hex( openssl_random_pseudo_bytes( 32, $cstrong ) );
					$defOptions['spkey2'] = $spKey;
				}
				if ( empty( $defOptions['site_admin_url'] ) || $defOptions['site_admin_url'] != admin_url( 'admin.php' ) ) {
					$defOptions['site_admin_url'] = admin_url( 'admin.php' );
				}
				$this->samOptions = $defOptions;
				if ( $forceUpdate ) {
					update_option( SAM_PRO_OPTIONS_NAME, $defOptions );
				}
			} else {
				$defOptions = $this->samOptions;
			}

			return $defOptions;
		}

		public function getVersions( $force = false ) {
			$versions = array( 'sam' => null, 'db' => null );
			if ( $force ) {
				$versions['sam']   = get_option( 'sam_pro_version', '' );
				$versions['db']    = get_option( 'sam_pro_db_version', '' );
				$this->samVersions = $versions;
			} else {
				$versions = $this->samVersions;
			}

			return $versions;
		}

		public function loggedOutUserNonce( $uid, $action ) {
			if ( is_user_logged_in() ) {
				return get_current_user_id();
			} else {
				return ( ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
			}
		}

		public function maintenance() {
			$options = self::getSettings();
			if ( false === ( $mDate = get_transient( 'sam_pro_maintenance_date' ) ) ) {
				$date = new DateTime( 'now' );
				if ( $options['mail_period'] == 'monthly' ) {
					$date->modify( '+1 month' );
					$nextDate = new DateTime( $date->format( 'Y-m-01 02:00' ) );
					$diff     = $nextDate->format( 'U' ) - $_SERVER['REQUEST_TIME'];
				} else {
					$dd = 8 - ( (integer) $date->format( 'N' ) );
					$date->modify( "+{$dd} day" );
					$nextDate = new DateTime( $date->format( 'Y-m-d 02:00' ) );
					$diff     = ( 8 - ( (integer) $date->format( 'N' ) ) ) * DAY_IN_SECONDS;
				}
				$format = 'Y-m-d 02:00';
				set_transient( 'sam_pro_maintenance_date', $nextDate->format( $format ), $diff );

				if ( $options['mailer'] || $options['keepStats'] > 0 ) {
					if ( (int) $options['mailer'] ) {
						include_once( 'tools/sam-pro-mailer.php' );
						$mailer = new SamProMailer( self::getSettings() );
						$mailer->sendMails();
					}

					if ( (int) $options['keepStats'] > 0 ) {
						include_once( 'tools/sam-pro-stats-tools.php' );
						$cleaner = new SamProStatisticsCleaner( self::getSettings() );
						$cleaner->clear();
					}
				}
				do_action( 'sam_pro_do_maintenance' );
			}
		}

		public function scavenge() {
			if ( false === ( $mDate = get_transient( 'sam_pro_scavenge_time' ) ) ) {
				$date = new DateTime( 'now' );
				$date->modify( '+1 day' );
				$nextDate = new DateTime( $date->format( 'Y-m-d 02:00' ) );
				$diff     = $nextDate->format( 'U' ) - $_SERVER['REQUEST_TIME'];
				$format   = 'Y-m-d 02:00';
				set_transient( 'sam_pro_scavenge_time', $nextDate->format( $format ), $diff );

				include_once( 'tools/sam-pro-scavenger.php' );
				$scavenger = new SamProScavenger( self::getSettings() );
				$scavenger->scavenge();
				do_action( 'sam_pro_do_scavenge' );
			}
		}

		public function createNonce() {
			$this->nonce = wp_create_nonce( 'sam_pro_admin_actions' );
		}

		private function registerAdminActions() {
			$actions = array(
				'load_places',
				'load_places_ads',
				'load_places_ads_item',
				'load_ads',
				'load_zones',
				'load_blocks',
				'load_error_log',
				'update_place_ad',
				'trash_place',
				'trash_ad',
				'trash_zone',
				'trash_block',
				'moderate_ad',
				'trash_place_ad',
				'remove_place',
				'remove_ad',
				'remove_zone',
				'remove_block',
				'remove_error',
				'lookup_ads',
				'load_posts',
				'load_cats',
				'load_tags',
				'load_authors',
				'load_taxes',
				'load_types',
				'load_users',
				'list_ads',
				'link_ads',
				'unlink_ad',
				'unlink_ad_item',
				'load_ad_objects',
				'clear_error_log',
				'solve_error',
				'load_zone_rules',
				'update_zone_rule',
				'remove_zone_rule',
				'add_zone_rules',
				'load_zone_single_rules',
				'update_zone_single_rule',
				'remove_zone_single_rule',
				'add_zone_single_rules',
				'load_regions',
				'load_ad_chart_data',
				'load_chart_data',
				'load_chart_data_all',
				'load_grid_data',
				'load_grid_data_all',
				'load_grid_child_data',
				'load_grid_child_data_all',
				'load_pie_data',
				'load_pie_data_all',
				'load_pie_data_2',
				'load_pie_data_2_all',
				'list_adverts',
				'list_adverts_ads'
			);

			foreach ( $actions as $action ) {
				add_action( "wp_ajax_{$action}", array( &$this, 'doAdminActions' ) );
			}
		}

		private function registerFrontActions() {
			$actions = array(
				'sam_load_ads',
				'sam_hits',
				'sam_click'
			);

			foreach ( $actions as $action ) {
				add_action( "wp_ajax_{$action}", array( &$this, 'doFrontActions' ) );
				add_action( "wp_ajax_nopriv_{$action}", array( &$this, 'doFrontActions' ) );
			}
		}

		public function doAdminActions() {
			$nonce = $_REQUEST['sam_pro_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'sam_pro_admin_actions' ) ) {
				wp_die( 'Rejected. Execution Handler.' );
			}
			include_once "sam-pro-admin-actions.php";
			$action     = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : null;
			$json_param = file_get_contents( "php://input" );
			$params     = json_decode( $json_param, true );
			$acts       = new SamProAdminActions( $action, $params );
			$acts->doAction();
		}

		public function doFrontActions() {
			$nonce = $_REQUEST['sam_pro_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'sam_pro_admin_actions' ) ) {
				wp_die( 'Rejected. Execution Handler.' );
			}
			include_once 'sam-pro-front-actions.php';
			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : null;
			$acts   = new SamProFrontActions( $action );
			$acts->doAction();
		}
	}
}