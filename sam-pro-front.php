<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 03.05.2015
 * Time: 10:39
 */

if( class_exists( 'SamProCore' ) && ! class_exists( 'SamProFront' ) ) {
	class SamProFront extends SamProCore {
		private $settings;
		private $crawler = false;
		public $userLocation = null;
		public $clause;
		public $isHome;
		public $isArchive;
		public $isSingle;
		public $isPage;
    public $disableAdServing = false;

		public function __construct() {
			parent::__construct();

			global $SAM_Pro_Loop_Counter;
			$SAM_Pro_Loop_Counter = 0;
			$this->settings = parent::getSettings();
			$this->crawler = self::isCrawler();
			add_action('template_redirect', array(&$this, 'setSamQuery'));
			add_action('wp_enqueue_scripts', array(&$this, 'headerScripts'));

			add_shortcode('sam_pro', array(&$this, 'doShortcode'));

			// Simple Ads Manager Compatibility ** Start
			add_shortcode('sam', array(&$this, 'doPlaceShortcode'));
			add_shortcode('sam_ad', array(&$this, 'doAdShortcode'));
			add_shortcode('sam_zone', array(&$this, 'doZoneShortcode'));
			add_shortcode('sam_block', array(&$this, 'doBlockShortcode'));
			// Simple Ads Manager Compatibility ** End

			add_filter('the_content', array(&$this, 'addContentAds'), 8);
			add_filter('get_the_excerpt', array(&$this, 'addExcerptAds'), 10);
			if( $this->settings['bbpActive'] && $this->settings['bbpEnabled'] ) {
				add_filter('bbp_get_reply_content', array(&$this, 'addBbpContentAds'), 39, 2);
				add_filter('bbp_get_topic_content', array(&$this, 'addBbpContentAds'), 39, 2);
				add_action('bbp_theme_after_forum_sub_forums', array(&$this, 'addBbpForumAds'));
				add_action('bbp_theme_before_topic_started_by', array(&$this, 'addBbpForumAds'));
			}
			if($this->settings['wptouchEnabled']) {
				if($this->settings['wptAd'] && isset($this->settings['wptAdsId']))
					add_action('wptouch_advertising_top', array(&$this, 'drawWptAd'), 9999);
			}
		}

		private function getDfpCodes( $mode ) {
			$options = self::getSettings();
			$netCode = $options['dfpNetworkCode'];
			$dfpPub = $options['dfpPub'];
			$out = '';

			if($mode == 'gam') {
				if($options['useDFP'] == 1 && !empty($dfpPub) && is_array($options['dfpBlocks'])) {
					$slots = '';
					foreach ( $options['dfpBlocks'] as $value ) {
						$slots .= "\n  GA_googleAddSlot('{$dfpPub}', '{$value}');";
					}
					$out = "
<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'></script>
<script type='text/javascript'>
  GS_googleAddAdSenseService('$dfpPub');
  GS_googleEnableAllServices();
</script>
<script type='text/javascript'>{$slots}
</script>
<script type='text/javascript'>
GA_googleFetchAds();
</script>";
				}
				else $out = '';
			}
			elseif($mode == 'gpt') {
				if ($options['useDFP'] == 1 && ! empty( $netCode ) && is_array( $options['dfpBlocks2'] ) ) {
					$slots = '';
					foreach ( $options['dfpBlocks2'] as $value ) {
						$slots .= "\n  googletag.defineSlot('/{$netCode}/{$value['name']}', {$value['size']}, '{$value['div']}').addService(googletag.pubads());";
					}
					$out = "
<script type='text/javascript'>
  (function() {
    var useSSL = 'https:' == document.location.protocol;
    var src = (useSSL ? 'https:' : 'http:') + '//www.googletagservices.com/tag/js/gpt.js';
    document.write('<scr' + 'ipt src=\"' + src + '\"></scr' + 'ipt>');
  })();
</script>

<script type='text/javascript'>
  googletag.cmd.push(function() { {$slots}
    googletag.pubads().enableSingleRequest();
    googletag.enableServices();
  });
</script>";
				} else $out = '';
			}

			return $out;
		}

		public function getAdSenseCodes() {
			$settings = parent::getSettings();
			$out = '';
			if($settings['enablePageLevelAds'] && !empty($settings['adsensePub'])) {
				$out = "<script async src='//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js'></script>
<script>
  (adsbygoogle = window.adsbygoogle || []).push({
    google_ad_client: '{$settings['adsensePub']}',
    enable_page_level_ads: true
  });
</script>";
			}

			return $out;
		}

		public function setSamQuery() {
			global $SAM_PRO_Query, $post;
			if(empty($this->clause)) $this->clause = self::buildWhereClause();
			$SAM_PRO_Query = $this->clause;
			$this->isHome = (is_home() || is_front_page());
			$this->isArchive = is_archive();
			$this->isPage = is_page();
			$this->isSingle = is_single();

			if($this->isSingle || $this->isPage) {
				$postId = $post->ID;
				$val = get_post_meta( $postId, 'sam_pro_disable_ad_serving', true );
				$this->disableAdServing = (!empty($val));
			}
		}

		private function isCrawler() {
			$options = $this->getSettings();
			$crawler = false;

			if((int)$options['detectBots']) {
				switch($options['detectingMode']) {
					case 'inexact':
						if(((!isset($_SERVER["HTTP_USER_AGENT"])) ? true : ($_SERVER["HTTP_USER_AGENT"] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT'])) ? true : ($_SERVER['HTTP_ACCEPT'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? true : ($_SERVER['HTTP_ACCEPT_ENCODING'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? true : ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == '')) ||
						   ((!isset($_SERVER['HTTP_CONNECTION'])) ? true : $_SERVER['HTTP_CONNECTION'] == ''))
							$crawler = true;
						break;

					case 'exact':
						if(!class_exists('SamProBrowser')) include_once('tools/sam-pro-browser.php');
						$browser = new SamProBrowser();
						$crawler = $browser->isRobot();
						break;

					case 'more':
						if(ini_get("browscap")) {
							$browser = get_browser(null, true);
							$crawler = $browser['crawler'];
						}
						break;
					case 'js':
						$crawler = false;
						break;
					default:
						$crawler = false;
						break;
				}
			}
			return $crawler;
		}

		public function headerScripts() {
			$options = parent::getSettings();
			$postId = null;
			if(is_singular() && is_page()) {
				global $post;
				$postId = $post->ID;
			}

			$siteLocale = explode('_', get_locale());
			$locale = $siteLocale[0];

			$container = (isset($options['containerClass']) && $options['samClasses'] == 'custom') ? $options['containerClass'] : 'sam-pro-container';
			$samAd = (isset($options['adClass']) && $options['samClasses'] == 'custom') ? $options['adClass'] : 'sam-pro-ad';
			$samPlace = ($options['samClasses'] == 'custom' && !empty($options['placeClass'])) ? $options['placeClass'] : 'sam-pro-place';

			$key = pack('H*', $options['spkey']);
			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

			$txt = serialize($this->clause);
			$clauses = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $txt, MCRYPT_MODE_CBC, $iv);
			$clauses = $iv . $clauses;
			$clauses64 = base64_encode($clauses);

			do_action('sam_pro_front_styles', $locale, $postId);

			if((int)$this->settings['useSWF']) wp_enqueue_script('swfobject');
			if((int)$this->settings['useDFP']) echo self::getDfpCodes($this->settings['dfpMode']);
			if((int)$this->settings['enablePageLevelAds']) echo self::getAdSenseCodes();

			wp_enqueue_script('jquery');
			$jsOptions = array(
				'au' => SAM_PRO_URL . 'sam-pro-layout.php',
				'load' => ($options['adShow'] == 'js') ? '1' : '0',
				'mailer' => $options['mailer'],
				'clauses' => $clauses64,
				'doStats' => $options['stats'],
				'jsStats' => ((int)$options['detectBots'] && ($options['detectingMode'] === 'js')),
				'container' => $container,
				'place' => $samPlace,
				'ad' => $samAd,
				'wap' => $this->wap
			);
			$jsOptions = apply_filters('sam_pro_front_js_options', $jsOptions, $locale, $postId);

			wp_enqueue_script('samProTracker', SAM_PRO_URL . 'js/jquery.iframetracker.js', array('jquery'));
			wp_enqueue_script('samProLayout', SAM_PRO_URL . 'js/sam.pro.layout.min.js', array('jquery', 'samProTracker'), '1.0.0.10');
			wp_localize_script('samProLayout', 'samProOptions', $jsOptions);
			do_action('sam_pro_front_scripts', $locale, $postId);
		}

		private function getCustomPostTypes() {
			$args = array('public' => true, '_builtin' => false);
			$output = 'names';
			$operator = 'and';
			$post_types = get_post_types($args, $output, $operator);

			return $post_types;
		}

		private function isCustomPostType() {
			return (in_array(get_post_type(), $this->getCustomPostTypes()));
		}

		private function customTaxonomiesTerms($id) {
			$post = get_post($id);
			$postType = $post->post_type;
			$taxonomies = get_object_taxonomies($postType, 'objects');

			$out = array();
			foreach ($taxonomies as $tax_slug => $taxonomy) {
				$terms = get_the_terms($id, $tax_slug);
				if(!empty($terms)) {
					foreach($terms as $term) {
						$out[] = $term->slug;
					}
				}
			}
			return $out;
		}

		public function buildWhereClause() {
			$settings = parent::getSettings();
			$cycle = ((!isset($settings['adCycle']) || $settings['adCycle'] == 0) ? 1000 : $settings['adCycle']);
			$rules = array(
				'posts' => (isset($settings['rule_id'])) ? (integer)$settings['rule_id'] : 0,
				'categories' => (isset($settings['rule_categories'])) ? (integer)$settings['rule_categories'] : 0,
				'tags' => (isset($settings['rule_tags'])) ? (integer)$settings['rule_tags'] : 0,
				'authors' => (isset($settings['rule_authors'])) ? (integer)$settings['rule_authors'] : 0,
				'taxes' => (isset($settings['rule_taxes'])) ? (integer)$settings['rule_taxes'] : 0,
				'types' => (isset($settings['rule_types'])) ? (integer)$settings['rule_types'] : 0,
				'schedule' => (isset($settings['rule_schedule'])) ? (integer)$settings['rule_schedule'] : 0,
				'hits' => (isset($settings['rule_hits'])) ? (integer)$settings['rule_hits'] : 0,
				'clicks' => (isset($settings['rule_clicks'])) ? (integer)$settings['rule_clicks'] : 0,
				'geo' => (isset($settings['rule_geo'])) ? (integer)$settings['rule_geo'] : 0
			);

			global $wpdb;

			$sTable = $wpdb->prefix . 'sampro_stats';
			$viewPages = 0x0;

			$arc = '';
			$singular = '';
			$zone = '';
			$isSingle = false;

			// Users
			if(is_user_logged_in()) {
				$current_user = wp_get_current_user();
				$userSlug = $current_user->user_nicename;
				$ul = "IF(sa.users_reg = 1, IF(sa.xusers = 1, NOT FIND_IN_SET(\"$userSlug\", sa.xvusers), TRUE) AND IF(sa.advertiser = 1, (sa.owner <> \"$userSlug\"), TRUE), FALSE)";
			}
			else $ul = "(sa.users_unreg = 1)";

			$users = "IF(sa.users = 0, TRUE, {$ul})";

			// Single Pages
			if(is_home() || is_front_page()) {
				$viewPages |= SAM_PRO_IS_HOME;
				$zone = "page_home";
			}
			if(is_singular()) {
				$viewPages |= SAM_PRO_IS_SINGULAR;
				$zone = "page_singular";
				$isSingle = true;

				// Custom Post Type
				if(self::isCustomPostType()) {
					$viewPages |= SAM_PRO_IS_SINGLE;
					$viewPages |= SAM_PRO_IS_POST_TYPE;
					$postType = get_post_type();
					$zone .= ((!empty($zone)) ? ',' : '') . "cpt_all-cpt,cpt_{$postType}";
					if($rules['types']) {
						$singular .= (((!empty($singular)) ? ' AND ' : '') . "IF(sa.etypes, IF(sa.xtypes, NOT FIND_IN_SET(\"{$postType}\", sa.types), FIND_IN_SET(\"{$postType}\", sa.types)), TRUE)");
					}
				}

				// Single Post
				if(is_single()) {
					global $post;

					$viewPages |= SAM_PRO_IS_SINGLE;
					$categories = get_the_category($post->ID);
					$tags = get_the_tags();
					$postID = ((!empty($post->ID)) ? (integer)$post->ID : 0);
					$customTerms = self::customTaxonomiesTerms($postID);

					$cats = '';
					$sTags = '';
					$sTaxes = '';
					$sPost = '';
					$sAuthor = '';

					$zone .= ((!empty($zone)) ? ',' : '') . "page_single";

					// Categories
					if(!empty($categories)) {
						$zone .= ( ( ! empty( $zone ) ) ? ',' : '' ) . "category_all-cats";
						foreach ( $categories as $category ) {
							$zone .= ( ( ! empty( $zone ) ) ? ',' : '' ) . "category_{$category->category_nicename}";
						}
					}
					if($rules['categories'] && !empty($categories)) {
						$cats0 = '';
						$cats1 = '';
						foreach($categories as $category) {
							$cats0 .= ((!empty($cats0)) ? ' OR ' : '') . "FIND_IN_SET(\"{$category->category_nicename}\", sa.cats)";
							$cats1 .= ((!empty($cats1)) ? ' AND ' : '') . "NOT FIND_IN_SET(\"{$category->category_nicename}\", sa.cats)";
						}
						$cats = "IF(sa.ecats, IF(sa.xcats, {$cats1}, {$cats0}), TRUE)";
					}

					// Tags
					if(!empty($tags)) {
						$zone .= ((!empty($zone)) ? ',' : '') . "post_tag_all-tags";
						foreach($tags as $val) $zone .= ((!empty($zone)) ? ',' : '') . "post_tag_{$val->slug}";
					}
					if($rules['tags']) {
						if(!empty($tags)) {
							$sTags0 = '';
							$sTags1 = '';
							foreach ( $tags as $val ) {
								$sTags0 .= ( ( ! empty( $sTags0 ) ) ? ' OR ' : '' ) . "FIND_IN_SET(\"{$val->slug}\", sa.tags)";
								$sTags1 .= ( ( ! empty( $sTags1 ) ) ? ' AND ' : '' ) . "NOT FIND_IN_SET(\"{$val->slug}\", sa.tags)";
							}
							$sTags = "IF(sa.etags, IF(sa.xtags, {$sTags1}, {$sTags0}), TRUE)";
						}
						else $sTags = "IF(sa.etags, IF(sa.xtags, TRUE, FALSE), TRUE)";
					}

					// Custom Taxonomies Terms
					if(!empty($customTerms)) {
						$zone .= ((!empty($zone)) ? ',' : '') . "ctt_all-ctt";
						foreach($customTerms as $cTerm) $zone .= ((!empty($zone)) ? ',' : '') . "ctt_{$cTerm}";
					}
					if($rules['taxes']) {
						if(!empty($customTerms)) {
							$sTaxes0 = '';
							$sTaxes1 = '';
							foreach ( $customTerms as $cTerm ) {
								$sTaxes0 .= ( ( ! empty( $sTaxes0 ) ) ? ' OR ' : '' ) . "FIND_IN_SET(\"{$cTerm}\", sa.taxes)";
								$sTaxes1 .= ( ( ! empty( $sTaxes1 ) ) ? ' AND ' : '' ) . "NOT FIND_IN_SET(\"{$cTerm}\", sa.taxes)";
							}
							$sTaxes = "IF(sa.etax, IF(sa.xtax, {$sTaxes1}, {$sTaxes0}), TRUE)";
						}
						else $sTaxes = "IF(sa.etax, IF(sa.xtax, TRUE, FALSE), TRUE)";
					}

					// Post ID
					if($rules['posts'] && $postID > 0) {
						$sPost = "IF(sa.eposts, IF(sa.xposts, NOT FIND_IN_SET({$postID}, sa.posts), FIND_IN_SET({$postID}, sa.posts)), TRUE)";
					}

					// Authors
					$pAuthor = get_userdata($post->post_author);
					$zone .= ((!empty($zone)) ? ',' : '') . "author_all-authors,author_{$pAuthor->user_nicename}";
					if($rules['authors']) {
						$sAuthor = "IF(sa.eauthors, IF(sa.xauthors, NOT FIND_IN_SET(\"{$pAuthor->user_nicename}\", sa.authors), FIND_IN_SET(\"{$pAuthor->user_nicename}\", sa.authors)), TRUE)";
					}

					if(!empty($sPost)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $sPost);
					if(!empty($cats)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $cats);
					if(!empty($sTags)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $sTags);
					if(!empty($sTaxes)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $sTaxes);
					if(!empty($sAuthor)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $sAuthor);
				}

				// Page
				if(is_page()) {
					global $post;
					$viewPages |= SAM_PRO_IS_PAGE;
					$zone .= ((!empty($zone)) ? ',' : '') . "page_page";
					if($rules['posts']) {
						$postID = ((!empty($post->ID)) ? (integer)$post->ID : 0);
						$singular .= (((!empty($singular)) ? ' AND ' : '') . "IF(sa.eposts, IF(sa.xposts, NOT FIND_IN_SET({$postID}, sa.posts), FIND_IN_SET({$postID}, sa.posts)), TRUE)");
					}
					$pAuthor = get_userdata($post->post_author);
					$zone .= ((!empty($zone)) ? ',' : '') . "author_all-authors,author_{$pAuthor->user_nicename}";
					if($rules['authors']) {
						$sAuthor = "IF(sa.eauthors, IF(sa.xauthors, NOT FIND_IN_SET(\"{$pAuthor->user_nicename}\", sa.authors), FIND_IN_SET(\"{$pAuthor->user_nicename}\", sa.authors)), TRUE)";
					}
					if(!empty($sAuthor)) $singular .= (((!empty($singular)) ? ' AND ' : '') . $sAuthor);
				}

				// Attachment
				if(is_attachment()) {
					$viewPages |= SAM_PRO_IS_ATTACHMENT;
					$zone .= ((!empty($zone)) ? ',' : '') . "page_attachment";
				}
			}
			if(is_search()) {
				$viewPages |= SAM_PRO_IS_SEARCH;
				$zone .= ((!empty($zone)) ? ',' : '') . "page_search";
			}
			if(is_404()) {
				$viewPages |= SAM_PRO_IS_404;
				$zone .= ((!empty($zone)) ? ',' : '') . "page_404";
			}

			// Archives
			if(is_archive()) {
				$viewPages |= SAM_PRO_IS_ARCHIVE;
				$zone .= ((!empty($zone)) ? ',' : '') . "page_archive";
				if(is_tax()) {
					$viewPages |= SAM_PRO_IS_TAX;
					$term = get_query_var('term');
					$zone .= ((!empty($zone)) ? ',' : '') . "ctt_all-ctt,ctt_{$term}";
					if($rules['taxes']) {
						$arc = "IF(sa.etax, IF(sa.xtax, NOT FIND_IN_SET(\"{$term}\", sa.taxes), FIND_IN_SET(\"{$term}\", sa.taxes)), TRUE)";
					}
				}
				elseif(is_category()) {
					$viewPages |= SAM_PRO_IS_CATEGORY;
					$cat = get_category( get_query_var( 'cat' ), false );
					$zone .= ((!empty($zone)) ? ',' : '') . "category_all-cats,category_{$cat->category_nicename}";
					if($rules['categories']) {
						$arc = "IF(sa.ecats, IF(sa.xcats, NOT FIND_IN_SET(\"{$cat->category_nicename}\", sa.cats), FIND_IN_SET(\"{$cat->category_nicename}\", sa.cats)), TRUE)";
					}
				}
				elseif(is_tag()) {
					$viewPages |= SAM_PRO_IS_TAG;
					$tag = get_tag( get_query_var( 'post_tag_id' ) );
					$zone .= ((!empty($zone)) ? ',' : '') . "post_tag_all-tags,post_tag_{$tag->slug}";
					if($rules['tags']) {
						$arc = "IF(sa.etags, IF(sa.xtags, NOT FIND_IN_SET(\"{$tag->slug}\", sa.tags), FIND_IN_SET(\"{$tag->slug}\", sa.tags)), TRUE)";
					}
				}
				elseif(is_author()) {
					$viewPages |= SAM_PRO_IS_AUTHOR;
					if($rules['authors']) {
						global $wp_query;
						$author = $wp_query->get_queried_object();
						$zone .= ((!empty($zone)) ? ',' : '') . "author_all-authors,author_{$author->user_nicename}";
						$arc = "IF(sa.eauthors, IF(sa.xauthors, NOT FIND_IN_SET(\"{$author->user_nicename}\", sa.authors), FIND_IN_SET(\"{$author->user_nicename}\", sa.authors)), TRUE)";
					}
				}
				elseif(is_post_type_archive()) {
					$viewPages |= SAM_PRO_IS_POST_TYPE_ARCHIVE;
					$postType = get_post_type();
					$zone .= ((!empty($zone)) ? ',' : '') . "cpt_all-cpt,cpt_{$postType}";
					if($rules['types']) {
						$arc = "IF(sa.etypes, IF(sa.xtypes, NOT FIND_IN_SET(\"{$postType}\", sa.types), FIND_IN_SET(\"{$postType}\", sa.types)), TRUE)";
					}
				}
				elseif(is_date()) {
					$viewPages |= SAM_PRO_IS_DATE;
					$zone .= ((!empty($zone)) ? ',' : '') . "page_all-date";
				}
				else $arc = '';
			}

			// Schedule
			$schedule = '';
			if($rules['schedule']) {
				$now = current_time('mysql');
				$schedule = " AND IF(sa.schedule, '{$now}' BETWEEN sa.sdate AND sa.fdate, TRUE)";
			}

			$wcp = apply_filters('sam_pro_front_place_clause_logic', ((!empty($singular)) ? ' AND (' . $singular . ')' : ((!empty($arc)) ? ' AND ' . $arc : '')));
			$clause = apply_filters('sam_pro_front_place_clause', "{$users} AND ((sa.ptype = 0) OR (sa.ptype = 1 AND (sa.ptypes+0 & {$viewPages}))){$wcp}{$schedule}", $rules);
			$zone = apply_filters('sam_pro_front_zone_clause', ((!empty($zone)) ? "AND FIND_IN_SET(szr.slug, '{$zone}')" : ''));

			return array('place' => $clause, 'zone' => $zone, 'single' => $isSingle);
		}

		public function buildAd( $id, $args = null, $useCodes = false ) {
      if($this->disableAdServing) return '';

      include_once('sam-pro-ad.php');

			$ad = new SamProAd($id, $args, $useCodes, $this->crawler);
			return $ad->ad;
		}

		public function buildPlace( $id, $args = null, $useCodes = false, $clauses = null ) {
      if($this->disableAdServing) return '';

      include_once('sam-pro-place.php');
			if(is_null($clauses)) $clauses = self::buildWhereClause();

			$ad = new SamProPlace($id, $args, $useCodes, $this->crawler, $clauses);
			return $ad->ad;
		}

		public function buildZone( $id, $args = null, $useCodes = false, $clauses = null ) {
      if($this->disableAdServing) return '';

      include_once('sam-pro-zone.php');
			if(is_null($clauses)) $clauses = self::buildWhereClause();

			$ad = new SamProZone($id, $args, $useCodes, $this->crawler, $clauses);
			return $ad->ad;
		}

		public function buildBlock( $id, $args = null, $clauses = null ) {
      if($this->disableAdServing) return '';

      include_once('sam-pro-block.php');
			if(is_null($clauses)) $clauses = self::buildWhereClause();

			$ad = new SamProBlock($id, $args, $this->crawler, $clauses);
			return $ad->ad;
		}

		public function buildAdObject( $adType, $id, $args = null, $useCodes = false, $clauses = null ) {
			$output = null;
			if(is_null($clauses)) $clauses = self::buildWhereClause();
			switch($adType) {
				case 0:
					$output = self::buildPlace($id, $args, $useCodes, $clauses);
					break;
				case 1:
					$output = self::buildAd($id, $args, $useCodes);
					break;
				case 2:
					$output = self::buildZone($id, $args, $useCodes, $clauses);
					break;
				case 3:
					$output = self::buildBlock($id, $args, $clauses);
					break;
				default:
					$output = '';
					break;
			}

			return $output;
		}

		public function addContentAds( $content ) {
			$options = self::getSettings();
			if(empty($this->clause)) $this->clause = self::buildWhereClause();
			$bpAd = '';
			$apAd = '';
			$out = $content;

			if(($this->isSingle || $this->isPage) && !$this->isHome) {
				if(!empty($options['beforePost']) && !empty($options['bpAdsId'])) {
					$bpTI = explode('_', $options['bpAdsId']);
					$bpId = (int)$bpTI[1];
					$bpType = (int)$bpTI[0];
					$bpAd = self::buildAdObject($bpType, $bpId, null, $options['bpUseCodes'], $this->clause);
				}

				if(!empty($options['middlePost']) && !empty($options['mpAdsId'])) {
					$mpTI = explode('_', $options['mpAdsId']);
					$mpId = (int)$mpTI[1];
					$mpType = (int)$mpTI[0];
					$cnt = explode("\r\n\r\n", $content);
					$count = count($cnt);
					$offset = (int)floor($count/2);
					$tail = $offset;
					$k = 0; $i = 0;
					if($count >= 2) {
						if((int)$offset < $count) {
							$out = '';
							foreach ( $cnt as $paragraph ) {
								$out .= ( $paragraph . "\r\n\r\n" );
								$k++; $i++;
								if ( $k == $offset && $i <= ($count - $tail) ) {
									$mpAd = self::buildAdObject( $mpType, $mpId, null, $options['mpUseCodes'], $this->clause );
									$out .= $mpAd;
								}
							}
						}
					}
				}

				if(!empty($options['afterPost']) && !empty($options['apAdsId'])) {
					$apTI = explode('_', $options['apAdsId']);
					$apId = (int)$apTI[1];
					$apType = (int)$apTI[0];
					$apAd = self::buildAdObject($apType, $apId, null, $options['apUseCodes'], $this->clause);
				}
			}
			elseif($options['bpExcerpt']) {
				if(!empty($options['beforePost']) && !empty($options['bpAdsId'])) {
					$bpTI = explode('_', $options['bpAdsId']);
					$bpId = (int)$bpTI[1];
					$bpType = (int)$bpTI[0];
					$bpAd = self::buildAdObject($bpType, $bpId, null, $options['bpUseCodes'], $this->clause);
				}
			}

			return $bpAd.$out.$apAd;
		}

		public function addExcerptAds( $excerpt ) {
			$options = self::getSettings();
			if(!empty($options['beforePost']) && !empty($options['bpExcerpt']) && !empty($options['bpAdsId'])) {
				$bpTI   = explode( '_', $options['bpAdsId'] );
				$bpId   = (int) $bpTI[1];
				$bpType = (int) $bpTI[0];
				if ( ! $this->isSingle ) {
					if ( empty( $this->clause ) ) {
						$this->clause = self::buildWhereClause();
					}
					$bpAd = self::buildAdObject( $bpType, $bpId, null, $options['bpUseCodes'], $this->clause );

					return $bpAd . $excerpt;
				} else {
					return $excerpt;
				}
			}
			else return $excerpt;
		}

		public function addBbpContentAds( $content, $reply_id ) {
			$options = self::getSettings();
			if(empty($this->clause)) $this->clause = self::buildWhereClause();
			$bpAd = '';
			$apAd = '';
			$out = $content;

			if(!empty($options['bbpBeforePost']) && !empty($options['bpAdsId'])) {
				$bpTI = explode('_', $options['bpAdsId']);
				$bpId = (int)$bpTI[1];
				$bpType = (int)$bpTI[0];
				$bpAd = self::buildAdObject($bpType, $bpId, null, $options['bpUseCodes'], $this->clause);
			}
			if(!empty($options['bbpMiddlePost']) && !empty($options['mpAdsId'])) {
				$mpTI = explode('_', $options['mpAdsId']);
				$mpId = (int)$mpTI[1];
				$mpType = (int)$mpTI[0];
				$cnt = explode("\r\n\r\n", $content);
				$count = count($cnt);
				$offset = (int)floor($count/2);
				$tail = $offset;
				$k = 0; $i = 0;
				if($count >= 2) {
					if((int)$offset < $count) {
						$out = '';
						foreach ( $cnt as $paragraph ) {
							$out .= ( $paragraph . "\r\n\r\n" );
							$k++; $i++;
							if ( $k == $offset && $i <= ($count - $tail) ) {
								$mpAd = self::buildAdObject( $mpType, $mpId, null, $options['mpUseCodes'], $this->clause );
								$out .= $mpAd;
							}
						}
					}
				}
			}
			if(!empty($options['bbpAfterPost']) && !empty($options['apAdsId'])) {
				$apTI = explode('_', $options['apAdsId']);
				$apId = (int)$apTI[1];
				$apType = (int)$apTI[0];
				$apAd = self::buildAdObject($apType, $apId, null, $options['apUseCodes'], $this->clause);
			}

			return $bpAd.$out.$apAd;
		}

		public function addBbpForumAds() {
			$options = self::getSettings();
			$bpAd = '';
			if(empty($this->clause)) $this->clause = self::buildWhereClause();

			if(!empty($options['bbpList']) && !empty($options['bpAdsId'])) {
				$bpTI = explode('_', $options['bpAdsId']);
				$bpId = (int)$bpTI[1];
				$bpType = (int)$bpTI[0];
				$bpAd = self::buildAdObject($bpType, $bpId, null, $options['bpUseCodes'], $this->clause);
			}

			echo $bpAd;
		}

		public function drawWptAd() {
			$options = self::getSettings();
			$ti = explode('_', $options['wptAdsId']);
			$id = (int)$ti[1];
			$type = (int)$ti[0];
			if(empty($this->clause)) $this->clause = self::buildWhereClause();

			$ad = self::buildAdObject($type, $id, null, false, $this->clause);
			$ad = (!empty($ad)) ? "<div id='wpt-spl-header' style='margin:0 0 -4px'>{$ad}</div>" : '';

			echo $ad;
		}

		public function doShortcode( $atts ) {
			$atts = shortcode_atts( array( 'id' => '', 'codes' => ''), $atts, 'sam_pro' );
			$ti = explode('_', $atts['id']);
			$id = (int)$ti[1];
			$type = (int)$ti[0];
			$ad = self::buildAdObject($type, $id, array('id' => (int)$atts['id']), ($atts['codes'] == 'true'), $this->clause);
			return $ad;
		}

		public function doAdShortcode($atts) {
			$atts = shortcode_atts( array( 'id' => '', 'name' => '', 'codes' => ''), $atts );
			$ad = self::buildAd((int)$atts['id']);
			return $ad;
		}

		public function doPlaceShortcode( $atts ) {
			$atts = shortcode_atts( array( 'id' => '', 'name' => '', 'codes' => ''), $atts );
			$ad = self::buildPlace((int)$atts['id'], array('id' => $atts['id']), ($atts['codes'] == 'true'), $this->clause);
			return $ad;
		}

		public function doZoneShortcode($atts) {
			$atts = shortcode_atts( array( 'id' => '', 'name' => '', 'codes' => ''), $atts );
			$ad = self::buildZone((int)$atts['id'], array('id' => $atts['id']), ($atts['codes'] == 'true'), $this->clause);
			return $ad;
		}

		public function doBlockShortcode($atts) {
			$atts = shortcode_atts( array( 'id' => '', 'name' => ''), $atts );
			$ad = self::buildBlock((int)$atts['id'], array('id' => $atts['id'], 'name' => $atts['name']), $this->clause);
			return $ad;
		}
	}
}