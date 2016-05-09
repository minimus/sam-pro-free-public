<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 05.06.2015
 * Time: 8:15
 */

include_once('tools/sam-pro-functions.php');
if( ! class_exists( 'SamProPlace' ) ) {
	class SamProPlace {
		private $useTags;
		private $crawler;
		private $args;
		private $before;
		private $after;
		private $cntClass;
		private $adClass;
		private $placeClass;
		private $eLog;
		private $settings;
		private $clauses;
		private $force;
		private $ajax;
		private $limit;
		private $jsStats;

		public $aid = null;
		public $pid = null;
		public $cid = null;
		public $ad = '';
		public $sql = '';

		public function __construct( $id, $args, $tags = false, $crawler = false, $clauses = null, $ajax = false ) {
			global $SAM_PRO_Query;

			$this->pid = (int)$id;
			$this->args = $args;
			$this->useTags = (bool)$tags;
			$this->crawler = (bool)$crawler;
			$this->clauses = (is_null($clauses)) ? $SAM_PRO_Query : $clauses;
			$this->settings = self::getSettings();
			$this->force = ($this->settings['adShow'] == 'php');
			$this->jsStats = ((int)$this->settings['detectBots'] && $this->settings['detectingMode'] == 'js');
			$this->ajax = (bool)$ajax;
			$this->limit = ' LIMIT 1';

			if(!$this->crawler) {
				self::prepareArgs();
				$data     = self::getData();
				$this->ad = self::buildAds( $data );
			}
		}

		private function getSettings() {
			$options = get_option(SAM_PRO_OPTIONS_NAME, '');
			return $options;
		}

		private function prepareArgs() {
			$this->before = (isset($this->args['before'])) ? $this->args['before'] : '';
			$this->after = (isset($this->args['after'])) ? $this->args['after'] : '';
			$this->cntClass = (isset($this->settings['containerClass']) && $this->settings['samClasses'] == 'custom') ? $this->settings['containerClass'] : 'sam-pro-container';
			$this->adClass = (isset($this->settings['adClass']) && $this->settings['samClasses'] == 'custom') ? $this->settings['adClass'] : 'sam-pro-ad';
			$this->placeClass = ($this->settings['samClasses'] == 'custom' && !empty($this->settings['placeClass'])) ? $this->settings['placeClass'] : 'sam-pro-place';
			$this->eLog = (isset($this->settings['errorlog'])) ? $this->settings['errorlog'] : false;
		}

		private function writeErrorLog( $table, $result, $error, $sql = '' ) {
			global $wpdb;
			$eTable = $wpdb->prefix . 'sampro_errors';
			$data = array(
				'edate' => current_time('mysql'),
				'tname' => $table,
				'etype' => (($result === true) ? 1 : 0),
				'emsg' => ((empty($error)) ? (($result === true) ? 'Updated...' : 'An error occurred during output...') : $error),
				'esql' => $sql,
				'solved' => 0
			);
			$format = array('%s', '%s', '%d', '%s', '%s', '%d');
			$wpdb->insert($eTable, $data, $format);
		}

		private function getData() {
			if(!isset($this->pid) || $this->pid <= 0) return null;

			global $wpdb;
			$pTable  = $wpdb->prefix . 'sampro_places';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$aTable  = $wpdb->prefix . 'sampro_ads';
			$sTable  = $wpdb->prefix . 'sampro_stats';

			$clauses = (!empty($this->clauses['place'])) ? ' AND ' . $this->clauses['place'] : '';
			$adCycle = (isset($this->settings['adCycle'])) ? (int)$this->settings['adCycle'] : 1000;
			$sql = "SELECT IFNULL(AVG(spa.hits*10/(spa.weight*{$adCycle})), 0)
    FROM {$paTable} spa
    INNER JOIN {$aTable} sa ON spa.aid = sa.aid
    WHERE spa.pid = {$this->pid} AND spa.trash < 1 AND sa.moderated > 0 AND spa.weight > 0{$clauses};";
			$aca = $wpdb->get_var($sql);
			if((int)$aca >= 1) {
				$wpdb->update($paTable, array('hits' => 0), array('pid' => $this->pid), array("%d"), array("%d"));
			}

			if($this->ajax) {
				$sql = "SELECT
  ua.pid, ua.aid,
  ua.code_before,
  ua.code_after,
  ua.asize, ua.width, ua.height,
  ua.img, ua.link, ua.alt, ua.acode, ua.rel,
  ua.php, ua.inline, ua.ad_server, ua.dfp, ua.amode, ua.clicks,
  ua.adCycle, 0 AS block_children,
  ua.limit_hits, ua.hits_limit, ua.hits_period,
  ua.limit_clicks, ua.clicks_limit, ua.clicks_period,
  ua.swf, ua.swf_vars, ua.swf_params, ua.swf_attrs, ua.swf_fallback
  FROM
  (SELECT
    spa.pid, spa.aid,
    sp.code_before,
    sp.code_after,
    sa.asize, sa.width, sa.height,
    sa.img, sa.link, sa.alt, sa.acode, sa.rel,
    sa.php, sa.inline, 0 AS ad_server, 0 AS dfp, sa.amode, sa.clicks,
    IF(spa.weight, spa.hits*10/(spa.weight*{$adCycle}), 0) AS adCycle,
    sa.limit_hits, sa.hits_limit, sa.hits_period,
    sa.limit_clicks, sa.clicks_limit, sa.clicks_period,
    sa.swf, sa.swf_vars, sa.swf_params, sa.swf_attrs, sa.swf_fallback
    FROM {$paTable} spa
    INNER JOIN {$aTable} sa ON spa.aid = sa.aid
    INNER JOIN {$pTable} sp ON spa.pid = sp.pid
    WHERE spa.pid = {$this->pid} AND spa.trash < 1 AND sa.moderated > 0 AND spa.weight > 0{$clauses}) ua
  NATURAL LEFT JOIN
    (SELECT wsst.pid, wsst.aid, SUM(wsst.hits) AS thits, SUM(wsst.clicks) AS tclicks
      FROM {$sTable} wsst
      WHERE wsst.pid = {$this->pid}
      GROUP BY wsst.pid, wsst.aid) sst
  WHERE
    IF(ua.limit_hits, (sst.thits IS NULL OR ua.hits_limit >= sst.thits), TRUE) AND
    IF(ua.limit_clicks, (sst.tclicks IS NULL OR ua.clicks_limit >= sst.tclicks), TRUE)
ORDER BY ua.adCycle{$this->limit};";
				$data = $wpdb->get_results($sql, ARRAY_A);
				if($this->eLog && $wpdb->num_rows == 0 && !empty($wpdb->last_error)) self::writeErrorLog($aTable, 0, $wpdb->last_error, $sql);

				//$this->sql = $sql;
				return $data;
			}

			if($this->force) {
				$sql = "
SELECT
  ua.pid, ua.aid,
  ua.code_before,
  ua.code_after,
  ua.asize, ua.width, ua.height,
  ua.img, ua.link, ua.alt, ua.acode, ua.rel,
  ua.php, ua.inline, ua.ad_server, ua.dfp, ua.amode, ua.clicks,
  ua.adCycle, ua.block_children,
  ua.limit_hits, ua.hits_limit, ua.hits_period,
  ua.limit_clicks, ua.clicks_limit, ua.clicks_period,
  ua.swf, ua.swf_vars, ua.swf_params, ua.swf_attrs, ua.swf_fallback
  FROM
  (SELECT
    sp.pid, sp.aid,
    @code_before := sp.code_before AS code_before,
    @code_after := sp.code_after AS code_after,
    sp.asize, sp.width, sp.height,
    sp.img, sp.link, sp.alt, sp.acode, sp.rel,
    sp.php, sp.inline, sp.ad_server, sp.dfp, sp.amode, 0 AS clicks,
    IF(sp.ad_server OR (sp.sale AND sp.sale_mode = 0 AND NOT (NOW() BETWEEN sp.sdate AND sp.fdate)), -1.0, 2.0) AS adCycle,
    (sp.sale AND sp.sale_mode = 0 AND NOT (NOW() BETWEEN sp.sdate AND sp.fdate)) AS block_children,
    0 AS limit_hits, 0 AS hits_limit, 0 AS hits_period,
    0 AS limit_clicks, 0 AS clicks_limit, 0 AS clicks_period,
    0 AS swf, NULL AS swf_vars, NULL AS swf_params, NULL AS swf_attrs, NULL AS swf_fallback
    FROM {$pTable} sp
    WHERE sp.pid = {$this->pid}
  UNION
  SELECT
    spa.pid, spa.aid,
    @code_before AS code_before,
    @code_after AS code_after,
    sa.asize, sa.width, sa.height,
    sa.img, sa.link, sa.alt, sa.acode, sa.rel,
    sa.php, sa.inline, 0 AS ad_server, 0 AS dfp, sa.amode, sa.clicks,
    IF(spa.weight, spa.hits*10/(spa.weight*{$adCycle}), 0) AS adCycle,
    0 AS block_children,
    sa.limit_hits, sa.hits_limit, sa.hits_period,
    sa.limit_clicks, sa.clicks_limit, sa.clicks_period,
    sa.swf, sa.swf_vars, sa.swf_params, sa.swf_attrs, sa.swf_fallback
    FROM {$paTable} spa
    INNER JOIN {$aTable} sa ON spa.aid = sa.aid
    WHERE spa.pid = {$this->pid} AND spa.trash < 1 AND sa.moderated > 0 AND spa.weight > 0{$clauses}) ua
  NATURAL LEFT JOIN
    (SELECT wsst.pid, wsst.aid, SUM(wsst.hits) AS thits, SUM(wsst.clicks) AS tclicks
      FROM {$sTable} wsst
      WHERE wsst.pid = {$this->pid}
      GROUP BY wsst.pid, wsst.aid) sst
  WHERE
    IF(ua.limit_hits, (sst.thits IS NULL OR ua.hits_limit >= sst.thits), TRUE) AND
    IF(ua.limit_clicks, (sst.tclicks IS NULL OR ua.clicks_limit >= sst.tclicks), TRUE)
ORDER BY ua.adCycle{$this->limit};";
				$data = $wpdb->get_results($sql, ARRAY_A);
				if($this->eLog && $wpdb->num_rows == 0 && !empty($wpdb->last_error)) self::writeErrorLog($pTable, 0, $wpdb->last_error, $sql);
			}
			else {
				$sql = "SELECT
  sp.pid, sp.aid,
  sp.code_before, sp.code_after,
  sp.asize, sp.width, sp.height,
  sp.img, sp.link, sp.alt, sp.acode, sp.rel,
  sp.php, sp.inline, sp.ad_server, sp.dfp, sp.amode, 0 AS clicks,
  (sp.sale AND sp.sale_mode = 0 AND NOT (NOW() BETWEEN sp.sdate AND sp.fdate)) AS block_children,
  (SELECT COUNT(*) FROM {$paTable} spa WHERE spa.pid = sp.pid) AS children,
  (SELECT COUNT(*) FROM {$paTable} spa INNER JOIN {$aTable} sa ON spa.aid = sa.aid WHERE spa.pid = sp.pid {$clauses}) AS children_logic
  FROM {$pTable} sp
  WHERE sp.pid = {$this->pid}";
				$data = array($wpdb->get_row($sql, ARRAY_A));
				if($this->eLog && ($data === false)) self::writeErrorLog($pTable, 0, $wpdb->last_error, $sql);
			}
			//$this->sql = $sql;

			return $data;
		}

		private function setStats( $data ) {
			global $wpdb, $aids;
			$sTable = $wpdb->prefix . 'sampro_stats';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$values = '';
			$pid = 0;
			$aids = array();
			$rows = 0;
			if(!empty($data) && is_array($data) && !$this->jsStats) {
				foreach ( $data as $ad ) {
					$values .= ( ( ( empty( $values ) ) ? '' : ', ' ) . "(CURDATE(), {$ad['pid']}, {$ad['aid']}, 1)" );
					array_push( $aids, $ad['aid'] );
					$pid = $ad['pid'];
				}
				if((int)$this->settings['stats'] == 1) {
					$sql  = "INSERT INTO {$sTable}(edate, pid, aid, hits) VALUES {$values} ON DUPLICATE KEY UPDATE hits = hits + 1;";
					$rows = $wpdb->query( $sql );
				}

				$aidsSet = (1 == count($aids)) ? '= ' . $aids[0] : 'IN (' . implode(',', $aids) . ')';
				$sql = "UPDATE {$paTable} SET hits = hits + 1 WHERE pid = {$pid} AND aid {$aidsSet};";
				$wpdb->query($sql);
			}
			return $rows;
		}

		private function buildAd( $data ) {
			if(is_null($data) || count($data) == 0) return '';

			$out = '';
			$useTags = (bool)$this->useTags;
			$before = (($useTags) ? ((!empty($this->before)) ? $this->before : $data['code_before']) : '');
			$after = (($useTags) ? ((!empty($this->after)) ? $this->after : $data['code_after']) : '' );
			$this->pid = (int)$data['pid'];
			$this->aid = (int)$data['aid'];
			$rId = rand(1111, 9999);
			$this->cid = "c{$rId}_{$this->aid}_{$this->pid}";
			$cid0 = "c{$rId}_0_{$this->pid}";
			$mode = $data['amode'];
			$swf = (isset($data['swf'])) ? (int)$data['swf'] : 0;
			$cntTag = (1 === (int)$data['inline']) ? 'span' : 'div';

			// Google DFP
			if($mode == 2) {
				$out = '';
				if($this->settings['dfpMode'] == 'gam') {
					if($this->settings['useDFP'] == 1 && !empty($this->settings['dfpPub'])) {
						$out = "<!-- {$data['dfp']} -->
<script type='text/javascript'>
  GA_googleFillSlot('{$data['dfp']}');
</script>";
						$out = "<div id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$out}{$after}</div>";
					}
				}
				elseif($this->settings['dfpMode'] == 'gpt') {
					if($this->settings['useDFP'] == 1 && !empty($this->settings['dfpNetworkCode'])) {
						$key = array_search($data['dfp'], array_column($this->settings['dfpBlocks2'], 'block'));
						$block = $this->settings['dfpBlocks2'][$key];
						$width = $block['width'] . 'px';
						$height = $block['height'] . 'px';
						$out = "<!-- /{$this->settings['dfpNetworkCode']}/{$block['name']} -->
<div id='{$block['div']}' style='height:{$height}; width:{$width};'>
<script type='text/javascript'>
	googletag.cmd.push(function() { googletag.display('{$block['div']}'); });
</script>
</div>";
					}
				}
				$this->force = true;
				$out = "<div id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$out}{$after}</div>";

				return $out;
			}

			// Third party Ad Server
			if($mode == 1 && $data['ad_server']) {
				$out = "<div id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$data['acode']}{$after}</div>";
				$this->force = true;

				return $out;
			}

			if($this->ajax || $this->force || (int)$data['block_children'] == 1 || (isset($data['children_logic']) && (int)$data['children_logic'] === 0)) {
				switch($mode) {
					case 0:
						$id = "spa-{$this->aid}-{$rId}";
						if($swf) {
							$vars = (!empty($data['swf_vars'])) ? $data['swf_vars'] : '{}';
							$params = (!empty($data['swf_params'])) ? $data['swf_params'] : '{}';
							$attrs = (!empty($data['swf_attrs'])) ? $data['swf_attrs'] : '{}';
							$text = (isset($data['swf_fallback'])) ? $data['swf_fallback'] : (($this->ajax) ? 'Flash Ad' : __('Flash ad', SAM_PRO_DOMAIN)).' ID:'.$data['aid'];
							$out = "
<script type='text/javascript'>
	var flashvars = {$vars}, params = {$params}, attributes = {$attrs};
	attributes.id = '{$id}';
	attributes.styleclass = '{$this->adClass}';
	swfobject.embedSWF('{$data['img']}', '{$id}', '{$data['width']}', '{$data['height']}', '9.0.0', '', flashvars, params, attributes);
</script>";
							$fallback = "<div id='{$id}'>{$text}</div>";
							if(!empty($data['link'])) {
								$id = "id='img-{$this->aid}-{$rId}' class='{$this->adClass}'";
								$target = (!empty($this->settings['adDisplay'])) ? "_{$this->settings['adDisplay']}" : '_blank';
								$robo = (integer)$data['rel'];
								$rel = ((in_array($robo, array(1,2,4))) ? ((in_array($robo, array(1,4))) ? " rel='nofollow'" : " rel='dofollow'") : '');
								$niStart = ((in_array($robo, array(3,4))) ? '<noindex>' : '');
								$niEnd = ((in_array($robo, array(3,4))) ? '</noindex>' : '');
								$aStart = (!empty($data['link'])) ? "{$niStart}<a {$id} href='{$data['link']}' target='{$target}'{$rel}>" : '';
								$aEnd = (!empty($data['link'])) ? "</a>{$niEnd}" : '';
								$iAlt = (!empty($data['alt'])) ? "alt='{$data['alt']}'" : '';
								$blankImage = SAM_PRO_IMG_URL . "blank.gif";
								$iTag = "<img src='{$blankImage}' {$iAlt} style='width:100%;height:100%;padding:0;border:0;background-color:transparent;'>";
								$fallback = "<div class='sam-pro-swf-container' style='position: relative;'>
	<div class='sam-pro-flash-overlay' style='position:absolute;z-index:100;top:0;left:0;bottom:0;right:0;'>
		{$aStart}{$iTag}{$aEnd}
	</div>
	{$fallback}
</div>";
							}
							$out .= $fallback;
						}
						else {
							$id = "id='img-{$this->aid}-{$rId}' class='{$this->adClass}'";
							$target = (!empty($this->settings['adDisplay'])) ? "_{$this->settings['adDisplay']}" : '_blank';
							$robo = (integer)$data['rel'];
							$rel = ((in_array($robo, array(1,2,4))) ? ((in_array($robo, array(1,4))) ? " rel='nofollow'" : " rel='dofollow'") : '');
							$niStart = ((in_array($robo, array(3,4))) ? '<noindex>' : '');
							$niEnd = ((in_array($robo, array(3,4))) ? '</noindex>' : '');
							$aStart = (!empty($data['link'])) ? "{$niStart}<a {$id} href='{$data['link']}' target='{$target}'{$rel}>" : '';
							$aEnd = (!empty($data['link'])) ? "</a>{$niEnd}" : '';
							$iAlt = (!empty($data['alt'])) ? "alt='{$data['alt']}'" : '';
							$iTag = (!empty($data['img'])) ? "<img src='{$data['img']}' {$iAlt}>" : '';
							$out = $aStart . $iTag . $aEnd;
						}
						$out = (!empty($out)) ? "<div id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$out}{$after}</div>" : '';
						break;
					case 1:
						if($data['php'] == 1) {
							ob_start();
							eval( '?>' . $data['acode'] . '<?' );
							$out = ob_get_contents();
							ob_end_clean();
							$out = (!empty($out)) ? "<{$cntTag} id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$out}{$after}</{$cntTag}>" : '';
						}
						else
							$out = (!empty($data['acode'])) ? "<{$cntTag} id='{$this->cid}' class='{$this->cntClass} {$this->placeClass}'>{$before}{$data['acode']}{$after}</{$cntTag}>" : '';
						break;
				}
			}
			else
				$out = "<{$cntTag} id='{$cid0}' class='{$this->cntClass} {$this->adClass}' data-spc='{$useTags}'></{$cntTag}>";

			return $out;
		}

		private function buildAds( $data = null ) {
			if(is_null($data) || empty($data)) return '';

			$out = '';
			$cur = '';
			$stats = array();
			foreach($data as $ad) {
				$cur = self::buildAd($ad);
				if(!empty($cur)) {
					array_push($stats, array('pid' => (int)$ad['pid'], 'aid' => (int)$ad['aid']));
					$out .= $cur;
				}
			}
			if(count($stats) != 0 && ($this->force || $this->ajax)) self::setStats($stats);

			return $out;
		}
	}
}