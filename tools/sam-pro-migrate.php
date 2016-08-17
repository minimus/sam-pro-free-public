<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 05.07.2015
 * Time: 6:33
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists('SamProMigrate') ) {
	class SamProMigrate {
		private $eLog;
		private $settings;
		private $opts;
		private $data;
		private $stats;
		private $message;

		public function __construct( $settings, $opts = false, $data = false, $stats = false ) {
			$this->settings = $settings;
			$this->eLog = (isset($this->settings['errorlog'])) ? $this->settings['errorlog'] : false;
			$this->opts = $opts;
			$this->data = $data;
			$this->stats = $stats;
			$this->message = '';
		}

		private function writeErrorLog( $table, $result, $error, $sql = '' ) {
			global $wpdb;
			$eTable = $wpdb->prefix . 'sampro_errors';
			$data = array(
				'edate' => current_time('mysql'),
				'tname' => $table,
				'etype' => (($result === false) ? 0 : 1),
				'emsg' => ((empty($error)) ? (($result !== false) ? __('Updated...', SAM_PRO_DOMAIN) : __('An error occurred during updating process...', SAM_PRO_DOMAIN)) : $error),
				'esql' => $sql,
				'solved' => 0
			);
			$format = array('%s', '%s', '%d', '%s', '%s', '%d');
			$wpdb->insert($eTable, $data, $format);
		}

		private function clearTables() {
			global $wpdb;
			$pTable  = $wpdb->prefix . 'sampro_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$aTable  = $wpdb->prefix . 'sampro_ads';
			$zTable  = $wpdb->prefix . 'sampro_zones';
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';
			$bTable  = $wpdb->prefix . 'sampro_blocks';
			$sTable  = $wpdb->prefix . 'sampro_stats';

			$tables = array($pTable, $paTable, $aTable, $zTable, $zrTable, $bTable, $sTable);

			foreach($tables as $table) $wpdb->query("DELETE FROM {$table};");
		}

		private function getType( $type ) {
			if((int)$type == 1) $out = 0;
			elseif((int)$type == 0) $out = 1;
			else $out = $type;
			return $out;
		}

		private function migrateOptions() {
			$out = false;
			$sam = 'samPluginOptions';
			$samOptions = get_option($sam, '');
			$options = $this->settings;
			$ads = array('bpAdsId', 'mpAdsId', 'apAdsId', 'bpAdsType', 'mpAdsType', 'apAdsType');
			if(!empty($samOptions)) {
				$options['bpAdsId'] =
					(isset($samOptions['bpAdsId']) && isset($samOptions['bpAdsType']))
						? self::getType($samOptions['bpAdsType']) . '_' . $samOptions['bpAdsId']
						: $options['bpAdsId'];
				$options['apAdsId'] =
					(isset($samOptions['apAdsId']) && isset($samOptions['apAdsType']))
						? self::getType($samOptions['apAdsType']) . '_' . $samOptions['apAdsId']
						: $options['apAdsId'];
				$options['mpAdsId'] =
					(isset($samOptions['mpAdsId']) && isset($samOptions['mpAdsType']))
						? self::getType($samOptions['mpAdsType']) . '_' . $samOptions['mpAdsId']
						: $options['mpAdsId'];
				foreach($samOptions as $key => $value) {
					if(!in_array($key, $ads)) {
						$options[$key] = $value;
					}
				}
				update_option(SAM_PRO_OPTIONS_NAME, $options);
				$out = true;
			}
			else {
				if($this->eLog) {
					self::writeErrorLog(
						__('Options', SAM_PRO_DOMAIN),
						false,
						__( 'Migrating options: There are no options to migrate.', SAM_PRO_DOMAIN ),
						''
					);
				}
				$out = false;
			}

			if(!$out)
				$this->message .= __('Options: Something went wrong. More information in the error log...', SAM_PRO_DOMAIN);
			else
				$this->message .= __('The options of Simple Ads Manager are successfully migrated. ', SAM_PRO_DOMAIN);

			return $out;
		}

		private function getData() {
			global $wpdb;
			$data = array();
			$aTable = $wpdb->prefix . 'sam_ads';
			$pTable =	$wpdb->prefix . 'sam_places';
			$zTable =	$wpdb->prefix . 'sam_zones';
			$bTable =	$wpdb->prefix . 'sam_blocks';
			$sTable = $wpdb->prefix . 'sam_stats';

			// Ads
			if($wpdb->get_var("SHOW TABLES LIKE '$aTable'") == $aTable) {
				$sql         = "SELECT * FROM {$aTable} ORDER BY id LIMIT 1;";
				$data['ads'] = $wpdb->get_results( $sql, ARRAY_A );
			} else $data['ads'] = array();

			// Places
			if($wpdb->get_var("SHOW TABLES LIKE '$pTable'") == $pTable) {
				$sql            = "SELECT * FROM {$pTable} ORDER BY id LIMIT 1;";
				$data['places'] = $wpdb->get_results( $sql, ARRAY_A );
			} else $data['places'] = array();

			// Zones
			if($wpdb->get_var("SHOW TABLES LIKE '$zTable'") == $zTable) {
				$sql           = "SELECT * FROM {$zTable} ORDER BY id;";
				$data['zones'] = $wpdb->get_results( $sql, ARRAY_A );
			} else $data['zones'] = array();

			// Blocks
			if($wpdb->get_var("SHOW TABLES LIKE '$bTable'") == $bTable) {
				$sql            = "SELECT * FROM {$bTable} ORDER BY id;";
				$data['blocks'] = $wpdb->get_results( $sql, ARRAY_A );
			} else $data['blocks'] = array();

			// Statistics
			if($wpdb->get_var("SHOW TABLES LIKE '$sTable'") == $sTable) {
				$sql            = "SELECT * FROM {$sTable} ORDER BY id LIMIT 1;";
				$data['stats'] = $wpdb->get_results( $sql, ARRAY_A );
			} else $data['stats'] = array();

			return $data;
		}

		public function migrateData( $clearTables = false ) {
			global $wpdb, $wp_taxonomies;
			$pTable  = $wpdb->prefix . 'sampro_places';
			$opTable = $wpdb->prefix . 'sam_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$aTable  = $wpdb->prefix . 'sampro_ads';
			$oaTable = $wpdb->prefix . 'sam_ads';
			$zTable  = $wpdb->prefix . 'sampro_zones';
			$ozTable = $wpdb->prefix . 'sam_zones';
			$zrTable = $wpdb->prefix . 'sampro_zones_rules';
			$bTable  = $wpdb->prefix . 'sampro_blocks';
			$obTable = $wpdb->prefix . 'sam_blocks';
			$sTable  = $wpdb->prefix . 'sampro_stats';
			$osTable = $wpdb->prefix . 'sam_stats';

			$data = self::getData();
			$out = true;

			if($clearTables) self::clearTables();

			// Places
			if(is_array($data['places']) && !empty($data['places'])) {
				$sql = "INSERT IGNORE INTO {$pTable}
  (pid, title, description, code_before, code_after, asize, width, height,
  img, link, acode, ad_server, dfp, amode, trash)
  SELECT
    sp.id AS pid, sp.name AS title, sp.description,
    REPLACE(sp.code_before, '\"', \"'\") AS code_before,
    REPLACE(sp.code_after, '\"', \"'\") AS code_after,
    sp.place_size AS asize,
    IF(sp.place_size = 'custom', sp.place_custom_width, SUBSTR(sp.place_size, 1, LOCATE('x', sp.place_size) - 1)) AS width,
    IF(sp.place_size = 'custom', sp.place_custom_height, SUBSTR(sp.place_size, LOCATE('x', sp.place_size) + 1)) AS height,
    sp.patch_img AS img, sp.patch_link AS link,
    sp.patch_code AS acode, sp.patch_adserver AS ad_server, sp.patch_dfp AS dfp, sp.patch_source AS amode, sp.trash
    FROM {$opTable} sp ORDER BY sp.id;";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$pTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $pTable),
						$sql
					);
				}
				if(false === $res) $out = false;
			}

			// Ads
			if(is_array($data['ads']) && !empty($data['ads'])) {
				$sql = "INSERT IGNORE INTO {$aTable}
  (aid, title, description, img, link, alt, rel,
  swf, swf_vars, swf_params, swf_attrs, swf_fallback,
  acode, php, amode, clicks, moderated,
  asize, width, height,
  ptype, ptypes,
  eposts, xposts, posts,
  ecats, xcats, cats,
  etags, xtags, tags,
  eauthors, xauthors, authors,
  etax, xtax, taxes,
  etypes, xtypes, types,
  schedule, sdate, fdate,
  limit_hits, hits_limit, limit_clicks, clicks_limit,
  users, users_unreg, users_reg, xusers, xvusers, advertiser,
  owner, owner_name, owner_mail, ppm, ppc, ppi, trash)
  SELECT
    sa.id AS aid, sa.name AS title, sa.description, sa.ad_img AS img, sa.ad_target AS link, sa.ad_alt AS alt, sa.ad_no AS rel,
    sa.ad_swf AS swf, sa.ad_swf_flashvars AS swf_vars, sa.ad_swf_params AS swf_params, sa.ad_swf_attributes AS swf_attrs, sa.ad_swf_fallback AS swf_fallback,
    sa.ad_code AS acode, sa.code_type AS php, sa.code_mode AS amode, sa.count_clicks AS clicks, 1 AS moderated,
    sp.place_size AS asize,
    IF(sp.place_size = 'custom', sp.place_custom_width, SUBSTR(sp.place_size, 1, LOCATE('x', sp.place_size) - 1)) AS width,
    IF(sp.place_size = 'custom', sp.place_custom_height, SUBSTR(sp.place_size, LOCATE('x', sp.place_size) + 1)) AS height,
    IF(sa.view_type = 0, 1, 0) AS ptype, CAST(sa.view_pages AS UNSIGNED) AS ptypes,
    (sa.view_type = 2 OR sa.x_id) AS eposts, sa.x_id AS xposts, IF(sa.x_id, sa.x_view_id, sa.view_id) AS posts,
    (sa.ad_cats OR sa.x_cats) AS ecats, sa.x_cats AS xcats, IF(sa.x_cats, sa.x_view_cats, sa.view_cats) AS cats,
    (sa.ad_tags OR sa.x_tags) AS etags, sa.x_tags AS xtags, IF(sa.x_tags, sa.x_view_tags, sa.view_tags) AS tags,
    (sa.ad_authors OR sa.x_authors) AS eauthors, sa.x_authors AS xauthors, IF(sa.x_authors, sa.x_view_authors, sa.view_authors) AS authors,
    (sa.ad_custom_tax_terms OR sa.x_ad_custom_tax_terms) AS etax, sa.x_ad_custom_tax_terms AS xtax, IF(sa.x_ad_custom_tax_terms, sa.x_view_custom_tax_terms, sa.x_view_custom_tax_terms) AS taxes,
    (sa.ad_custom OR sa.x_custom) AS etypes, sa.x_custom AS xtypes, IF(sa.x_custom, sa.x_view_custom, sa.view_custom) AS types,
    sa.ad_schedule AS schedule, sa.ad_start_date AS sdate, sa.ad_end_date AS fdate,
    sa.limit_hits, sa.hits_limit, sa.limit_clicks, sa.clicks_limit,
    sa.ad_users AS users, sa.ad_users_unreg AS users_unreg, sa.ad_users_reg AS users_reg, sa.x_ad_users AS xusers, sa.x_view_users AS xvusers, sa.ad_users_adv AS advertiser,
    sa.adv_nick AS owner, sa.adv_name AS owner_name, sa.adv_mail AS owner_mail, sa.per_month AS ppm, sa.cpc AS ppc, sa.cpm AS ppi, sa.trash
    FROM {$oaTable} sa
    INNER JOIN {$opTable} sp ON sa.pid = sp.id
    ORDER BY sa.id;";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$aTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $aTable),
						$sql
					);
				}
				if(false === $res) $out = false;

				// Linked Ads
				$sql = "INSERT IGNORE INTO {$paTable} (pid, aid, weight, trash)
  SELECT sa.pid, sa.id AS aid, sa.ad_weight AS weight, sa.trash
    FROM {$oaTable} sa ORDER BY sa.pid, sa.id;";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$paTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $paTable),
						$sql
					);
				}
				if(false === $res) $out = false;
			}

			// Zones
			if(is_array($data['zones']) && !empty($data['zones'])) {
				$sql = "INSERT IGNORE INTO {$zTable} (zid, title, description, single_id, arc_id, trash)
  SELECT sz.id AS zid, sz.name AS title, sz.description, sz.z_singular AS single_id, sz.z_default AS arc_id, sz.trash
    FROM {$ozTable} sz ORDER BY sz.id;";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$zTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $zTable),
						$sql
					);
				}
				if(false === $res) $out = false;

				// Zones Rules
				$zones = $data['zones'];
				$values = '';
				foreach($zones as $zone) {
					$singleCT = unserialize($zone['z_single_ct']);
					$arcCT = unserialize($zone['z_archive_ct']);
					$taxes = unserialize($zone['z_taxes']);
					$cats = unserialize($zone['z_cats']);
					$authors = unserialize($zone['z_authors']);

					// Home page
					$tax = __('Home/Front Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_home'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_home', 0, '{$tax}', '{$name}', 'page_home', {$zone['z_home']}, 100)" : '');

					// Search Page
					$tax = __('Search Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_search'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_search', 0, '{$tax}', '{$name}', 'page_search', {$zone['z_search']}, 100)" : '');

					// 404 page
					$tax = __('404 Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_404'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_404', 0, '{$tax}', '{$name}', 'page_404', {$zone['z_404']}, 100)" : '');

					// Archive page
					$tax = __('Archive Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_archive'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_archive', 0, '{$tax}', '{$name}', 'page_archive', {$zone['z_archive']}, 100)" : '');

					// Singular Page
					$tax = __('Singular Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_singular'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_singular', 1, '{$tax}', '{$name}', 'page_singular', {$zone['z_singular']}, 100)" : '');

					// Single Page
					$tax = __('Single Post/Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_single'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_single', 1, '{$tax}', '{$name}', 'page_single', {$zone['z_single']}, 90)" : '');

					// Page
					$tax = __('Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_page'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_page', 1, '{$tax}', '{$name}', 'page_page', {$zone['z_page']}, 3)" : '');

					// Attachment
					$tax = __('Attachment', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_attachment'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_attachment', 1, '{$tax}', '{$name}', 'page_attachment', {$zone['z_attachment']}, 3)" : '');

					// Categories
					$tax = __('Categories', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_category'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'category_all-cats', 0, '{$tax}', '{$name}', 'all-cats', {$zone['z_category']}, 2)" : '');
					$tax = __('Category', SAM_PRO_DOMAIN);
					foreach($cats as $key => $cat) {
						$catObj = get_category_by_slug($key);
						$values .=
							(((int)$cat > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'category_{$key}', 0, '{$tax}', '{$catObj->name}', '{$key}', {$cat}, 1), ({$zone['id']}, 'category_{$key}', 1, '{$tax}', '{$catObj->name}', '{$key}', {$cat}, 1)" : '');
					}

					// Tags
					$tax = __('Tags', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_tag'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'post_tag_all-tags', 0, '{$tax}', '{$name}', 'all-tags', {$zone['z_tag']}, 2)" : '');

					// Custom Taxonomy Terms
					$tax = __('Custom Taxonomy Terms', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_tax'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'ctt_all-ctt', 0, '{$tax}', '{$name}', 'all-ctt', {$zone['z_tax']}, 2)" : '');
					if(!empty($taxes)) {
						foreach ( $taxes as $key => $taxonomy ) {
							$to = get_term_by( 'slug', $key, $taxonomy['tax'], OBJECT );
							if ( $to ) {
								$tax  = $wp_taxonomies[ $taxonomy['tax'] ]->label;
								$name = $to->name;
								$values .=
									( ( (int) $taxonomy['id'] > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, '{$taxonomy['tax']}_{$key}', 0, '{$tax}', '{$name}', '{$key}', {$taxonomy['id']}, 1)" : '' );
								$values .=
									( ( (int) $taxonomy['id'] > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, '{$taxonomy['tax']}_{$key}', 1, '{$tax}', '{$name}', '{$key}', {$taxonomy['id']}, 1)" : '' );
							}
						}
					}

					// Custom Post Types
					$tax = __( 'Custom Post Types', SAM_PRO_DOMAIN );
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_ct'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'cpt_all-cpt', 1, '{$tax}', '{$name}', 'all-cpt', {$zone['z_ct']}, 2)" : '');
					$values .=
						(((int)$zone['z_cts'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'cpt_all-cpt', 0, '{$tax}', '{$name}', 'all-cpt', {$zone['z_cts']}, 2)" : '');
					if(!empty($singleCT)) {
						foreach ( $singleCT as $cpt => $val ) {
							$cptObj = get_post_type_object( $cpt );
							$tax    = __( 'Custom Post Type', SAM_PRO_DOMAIN );
							$name   = $cptObj->label;
							$values .=
								( ( (int) $val > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, 'cpt_{$cpt}', 1, '{$tax}', '{$name}', '{$cpt}', {$val}, 1)" : '' );
						}
					}
					if(!empty($arcCT)) {
						foreach ( $arcCT as $cpt => $val ) {
							$cptObj = get_post_type_object( $cpt );
							$tax    = __( 'Custom Post Type', SAM_PRO_DOMAIN );
							$name   = $cptObj->label;
							$values .=
								( ( (int) $val > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, 'cpt_{$cpt}', 0, '{$tax}', '{$name}', '{$cpt}', {$val}, 1)" : '' );
						}
					}

					// Authors
					$tax = __('Authors', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_author'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'author_all-authors', 1, '{$tax}', '{$name}', 'all-authors', {$zone['z_author']}, 2)" : '');
					$values .=
						(((int)$zone['z_author'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'author_all-authors', 0, '{$tax}', '{$name}', 'all-authors', {$zone['z_author']}, 2)" : '');
					if(!empty($authors)) {
						foreach ( $authors as $author => $val ) {
							$displayName = get_the_author_meta( 'display_name', $author );
							$niceName    = get_the_author_meta( 'user_nicename', $author );
							$tax         = __( 'Author', SAM_PRO_DOMAIN );
							$name        = $displayName;
							$values .=
								( ( (int) $val > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, 'author_{$niceName}', 1, '{$tax}', '{$name}', '{$niceName}', {$val}, 1)" : '' );
							$values .=
								( ( (int) $val > 0 ) ? ( ( ! empty( $values ) ) ? ', ' : '' ) . "({$zone['id']}, 'author_{$niceName}', 0, '{$tax}', '{$name}', '{$niceName}', {$val}, 1)" : '' );
						}
					}

					// Date Archives
					$tax = __('Any Date Archive Page', SAM_PRO_DOMAIN);
					$name = __('All', SAM_PRO_DOMAIN);
					$values .=
						(((int)$zone['z_date'] > 0) ? ((!empty($values)) ? ', ' : '' ) . "({$zone['id']}, 'page_all-date', 0, '{$tax}', '{$name}', 'page_date', {$zone['z_date']}, 90)" : '');
				}

				$sql = "INSERT IGNORE INTO {$zrTable} (zid, slug, single, tax, name, term_slug, pid, priority) VALUES {$values};";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$zrTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $zrTable),
						$sql
					);
				}
				if(false === $res) $out = false;
			}

			// Blocks
			if(is_array($data['blocks']) && !empty($data['blocks'])) {
				$types = array('place' => 0, 'ad' => 1, 'zone' => 2);
				$blocks = $data['blocks'];
				$values = '';
				foreach($blocks as $block) {
					$oldData = unserialize($block['block_data']);
					$newData = array();
					foreach($oldData as $i => $vv) {
						foreach($vv as $j => $vvv) {
							$newData[$i][$j] = "{$types[$vvv['type']]}_{$vvv['id']}";
						}
					}
					$blockData = serialize($newData);
					$blockStyle = (!empty($block['b_margin'])) ? "margin: {$block['b_margin']};" : '';
					$blockStyle .= (!empty($block['b_padding'])) ? "padding: {$block['b_padding']};" : '';
					$blockStyle .= (!empty($block['b_background'])) ? "background: {$block['b_background']};" : '';
					$blockStyle .= (!empty($block['b_border'])) ? "border: {$block['b_border']};" : '';
					$itemStyle = (!empty($block['i_margin'])) ? "margin: {$block['i_margin']};" : '';
					$itemStyle .= (!empty($block['i_padding'])) ? "padding: {$block['i_padding']};" : '';
					$itemStyle .= (!empty($block['i_background'])) ? "background: {$block['i_background']};" : '';
					$itemStyle .= (!empty($block['i_border'])) ? "border: {$block['i_border']};" : '';

					$values .= ((!empty($values)) ? ", " : '') . "({$block['id']}, '{$block['name']}', '{$block['description']}', {$block['b_lines']}, {$block['b_cols']}, '{$blockData}', '{$blockStyle}', '{$itemStyle}', {$block['trash']})";
				}

				$sql = "INSERT IGNORE INTO {$bTable} (bid, title, description, b_rows, b_columns, b_data, b_style, i_style, trash) VALUES {$values};";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$bTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $bTable),
						$sql
					);
				}
				if(false === $res) $out = false;
			}

			// Statistics
			if($this->stats && is_array($data['stats']) && !empty($data['stats'])) {
				$sql = "INSERT INTO {$sTable} (pid, aid, edate, hits)
SELECT ss.pid, ss.id AS aid, DATE(ss.event_time), COUNT(ss.event_type = 0) AS hits
  FROM {$osTable} ss
  WHERE ss.event_type = 0
  GROUP BY ss.id, ss.pid, DATE(ss.event_time)
ON DUPLICATE KEY UPDATE hits = VALUES(hits);";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$sTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $sTable),
						$sql
					);
				}
				if(false === $res) $out = false;

				$sql = "INSERT INTO {$sTable} (pid, aid, edate, clicks)
SELECT ss.pid, ss.id AS aid, DATE(ss.event_time), COUNT(ss.event_type = 1) AS clicks
  FROM {$osTable} ss
  WHERE ss.event_type = 1
  GROUP BY ss.id, ss.pid, DATE(ss.event_time)
ON DUPLICATE KEY UPDATE clicks = VALUES(clicks);";

				$res = $wpdb->query($sql);
				if($this->eLog) {
					self::writeErrorLog(
						$sTable,
						(($res === false) ? false : $res),
						(($res === false) ? $wpdb->last_error : (sprintf( _n( 'Migrating: %s record added to the table ', 'Migrating: %s records added to the table ', $res, SAM_PRO_DOMAIN ), $res )) . $sTable),
						$sql
					);
				}
				if(false === $res) $out = false;
			}

			if(!$out)
				$this->message .= __('Data: Something went wrong. More information in the error log...', SAM_PRO_DOMAIN);
			else
				$this->message .= __('The data of Simple Ads Manager is successfully migrated. Now you can remove Simple Ads Manager plugin from your server.', SAM_PRO_DOMAIN);

			return $out;
		}

		public function migrate( $clearTables = false ) {
			$opts = $data = false;
			if($this->opts) $opts = self::migrateOptions();
			if($this->data) $data = self::migrateData($clearTables);

			return ($opts && $data);
		}

		public function show( $mess = false, $error = false ) {
			?>
<div class='ui-sortable sam-section'>
	<div class='postbox opened'>
		<h3 class='hndle'><?php _e('Migrate Data', SAM_PRO_DOMAIN); ?></h3>
		<div class="inside">
			<p><?php _e('Migrate data from', SAM_PRO_DOMAIN); ?> <a href="http://" target="_blank">Simple Ads Manager</a></p>
			<?php if($mess && !empty($this->message)) { ?>
			<div id="migrate-tools" class="<?php echo ($error) ? 'sam-pro-warning' : 'sam-pro-info' ?>">
				<p><?php echo $this->message; ?></p>
			</div>
			<?php } ?>
			<p>
				<button class="button-secondary" id="submit-button" name="Migrate" type="submit"><?php _e('Migrate Data', SAM_PRO_DOMAIN); ?></button>
			</p>
			<p>
				<input type="checkbox" name="migrate_options" id="migrate_options" value="1" checked="checked">
				<label for="migrate_options"><?php _e('Migrate options', SAM_PRO_DOMAIN) ?></label>
			</p>
			<p>
				<input type="checkbox" name="migrate_data" id="migrate_data" value="1" checked="checked">
				<label for="migrate_data"><?php _e('Migrate data', SAM_PRO_DOMAIN) ?></label>
			</p>
			<div style="padding: 0 10px;">
				<p>
					<input type="checkbox" name="migrate_stats" id="migrate_stats" value="1" checked="checked">
					<label for="migrate_stats"><?php _e('Migrate statistical data', SAM_PRO_DOMAIN) ?></label>
				</p>
				<p>
					<input type="checkbox" name="clear_tables" id="clear_tables" value="1" checked="checked">
					<label for="clear_tables"><?php _e('Clear target tables before migrating data', SAM_PRO_DOMAIN) ?></label>
				</p>
			</div>
			<p><?php _e('It is highly recommended make migration of data into an empty tables of the plugin. Some part of source data can be lost during transfer process if target tables are not empty.', SAM_PRO_DOMAIN); ?></p>
		</div>
	</div>
</div>
			<?php
		}
	}
}