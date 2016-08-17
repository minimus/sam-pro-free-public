<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 20.07.2015
 * Time: 13:23
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( ! class_exists( 'SamProStatistics' ) ) {
	class SamProStatistics {
		private $period;
		private $owner;
		private $view;
		private $item;
		private $settings;

		public function __construct( $settings, $item = 0, $period = 0, $owner = 'all', $view = 'sold' ) {
			$this->settings = $settings;
			$this->item = $item;
			$this->period = $period;
			$this->owner = $owner;
			$this->view = $view;
		}

		private function getMonthName( $month ) {
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
			return $months[$month - 1];
		}

		private function getIntervals() {
			$out = array();
			$m = (int)date('m');
			$mName = self::getMonthName($m);

			$out[0] = array('start' => date('Y-m-01'), 'end' => date('Y-m-t'), 'title' => __('This month', SAM_PRO_DOMAIN)." ({$mName}, ". date('Y').')');
			for($i = 1; $i < $m; $i++) {
				$cm = $m - $i;
				$mName = self::getMonthName($cm);
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

		private function getOwners() {
			global $wpdb;
			$aTable = $wpdb->prefix . 'sampro_ads';
			$sql = "SELECT DISTINCT sa.owner FROM {$aTable} sa WHERE sa.owner <> '' AND sa.owner IS NOT NULL;";
			$out = $wpdb->get_results($sql, ARRAY_A);

			return $out;
		}

		public function show() {
			global $wpdb;
			$sTable = $wpdb->prefix . 'sampro_stats';
			$pTable = $wpdb->prefix . 'sampro_places';
			$aTable = $wpdb->prefix . 'sampro_ads';
			$paTable = $wpdb->prefix . 'sampro_places_ads';

			$periods = self::getIntervals();
			$owners = self::getOwners();

			$ownerData = ($this->owner === 'all') ? '' : " INNER JOIN {$aTable} sa ON ss.aid = sa.aid";
			$ownerClause = ($this->owner === 'all') ? '' : " AND sa.owner = '{$this->owner}'";

			$sql = "SELECT sss.hits, sss.clicks, sss.price,
  CAST(IF(sss.hits = 0, 0, (sss.price/sss.hits)*1000) AS DECIMAL(11,2)) AS cpm,
  CAST(IF(sss.hits = 0, 0, (sss.clicks/sss.hits)*100) AS DECIMAL(7,3)) AS ctr,
  CAST(IF(sss.clicks = 0, 0, sss.price/sss.clicks) AS DECIMAL(11,2)) AS cpc
  FROM
  (SELECT IFNULL(SUM(sms.hits), 0) AS hits, IFNULL(SUM(sms.clicks), 0) AS clicks, IFNULL(SUM(sms.price), 0) AS price
    FROM
    ((SELECT
      ss.pid, ss.aid, DATE_FORMAT(ss.edate, '%m') AS mdate, IFNULL(SUM(ss.hits), 0) AS hits, IFNULL(SUM(ss.clicks), 0) AS clicks, IFNULL(sp.price, 0) AS price
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
      WHERE ss.edate BETWEEN '{$periods[$this->period]['start']}' AND '{$periods[$this->period]['end']}' AND sp.sale AND sp.sale_mode = 1 AND ss.aid <> 0{$ownerClause}
      GROUP BY mdate, ss.pid, ss.aid)
    UNION
    (SELECT
      ss.pid, ss.aid, DATE_FORMAT(ss.edate, '%m') AS mdate, IFNULL(SUM(ss.hits), 0) AS hits, IFNULL(SUM(ss.clicks), 0) AS clicks, IFNULL(sp.price, 0) AS price
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid{$ownerData}
      WHERE ss.edate BETWEEN '{$periods[$this->period]['start']}' AND '{$periods[$this->period]['end']}' AND sp.sale AND sp.sale_mode = 0{$ownerClause}
      GROUP BY mdate, ss.pid)) sms
  ) sss;";

			$totalStats = $wpdb->get_row($sql, ARRAY_A);

			$win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

			if(!$win) {
				switch($this->settings['currency']) {
					case 'auto': $lang = str_replace('-', '_', get_bloginfo('language')); break;
					case 'usd' : $lang = 'en_US'; break;
					case 'euro': $lang = 'de_DE'; break;
					default: $lang = str_replace('-', '_', get_bloginfo('language'));
				}
				$codeset = get_bloginfo('charset');
				setlocale(LC_MONETARY, $lang.'.'.$codeset);
				$totalStats['cpm'] = money_format('%.2n', $totalStats['cpm']);
				$totalStats['cpc'] = money_format('%.2n', $totalStats['cpc']);
				$totalStats['price'] = money_format('%.2n', $totalStats['price']);
			}
			?>
<div class="wrap">
	<h1><?php _e( 'SAM Pro Statistics', SAM_PRO_DOMAIN ); ?></h1>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<div id="tools" class="stats-tools">
			<input id="item" name="item" type="hidden" value="0">
			<label for="view"><?php _e('View', SAM_PRO_DOMAIN); ?>: </label>
			<select id="view" name="view">
				<option value="all" <?php selected('all', $this->view); ?>><?php _e('All', SAM_PRO_DOMAIN); ?></option>
				<option value="sold" <?php selected('sold', $this->view); ?>><?php _e('For Sale', SAM_PRO_DOMAIN); ?></option>
			</select>&nbsp;&nbsp;&nbsp;
			<label for="period"><?php _e('Period', SAM_PRO_DOMAIN); ?>: </label>
			<select id="period" name="period">
				<?php
				foreach($periods as $key => $value) {
					$chd = selected($this->period, $key, false);
					echo "<option value='{$key}' {$chd}>{$value['title']}</option>";
				}
				?>
			</select>
			<?php if($this->view == 'sold') { ?>
			&nbsp;&nbsp;&nbsp;
			<label for="owner"><?php _e('Advertiser', SAM_PRO_DOMAIN); ?>: </label>
			<select id="owner" name="owner">
				<option value="all"<?php selected('all', $this->owner); ?>><?php _e('All', SAM_PRO_DOMAIN) ?></option>
				<?php foreach($owners as $value) { ?>
				<option value="<?php echo $value['owner']; ?>"<?php selected($value['owner'], $this->owner); ?>>
					<?php echo $value['owner']; ?>
				</option>
				<?php } ?>
			</select>
			<?php } ?>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<button id="submit" name="redraw_stats" class="button-secondary" type="submit">
				<?php _ex('Show', 'Show Statistics', SAM_PRO_DOMAIN); ?>
			</button>
		</div>
	</form>
	<?php if($this->view == 'sold') { ?>
	<div id="stats">
		<div id="income" class="stats-item">
			<div class="stats-title"><?php _e('Income', SAM_PRO_DOMAIN) ?></div>
			<div id="income-val" class="stats-val"><?php echo $totalStats['price']; ?></div>
		</div>
		<div id="impr" class="stats-item">
			<div class="stats-title"><?php _e('Impressions', SAM_PRO_DOMAIN) ?></div>
			<div id="impr-val" class="stats-val"><?php echo (int)$totalStats['hits']; ?></div>
		</div>
		<div id="clicks" class="stats-item">
			<div class="stats-title"><?php _e('Clicks', SAM_PRO_DOMAIN) ?></div>
			<div id="clicks-val" class="stats-val"><?php echo (int)$totalStats['clicks']; ?></div>
		</div>
		<div id="cpm" class="stats-item">
			<div class="stats-title"><?php _e('CPM', SAM_PRO_DOMAIN) ?></div>
			<div id="cpm-val" class="stats-val"><?php echo $totalStats['cpm']; ?></div>
		</div>
		<div id="cpc" class="stats-item">
			<div class="stats-title"><?php _e('CPC', SAM_PRO_DOMAIN) ?></div>
			<div id="cpc-val" class="stats-val"><?php echo $totalStats['cpc']; ?></div>
		</div>
		<div id="ctr" class="stats-item">
			<div class="stats-title"><?php _e('CTR', SAM_PRO_DOMAIN) ?></div>
			<div id="ctr-val" class="stats-val"><?php echo $totalStats['ctr'] . '%'; ?></div>
		</div>
	</div>
	<?php } ?>
	<div id="chart-container">
		<div id="chart"></div>
	</div>
	<div id="details">
		<div id="grid-container">
			<div id="grid"></div>
		</div>
		<div id="pie-chart-container">
			<div class="pieItem"><div id="pieChartHits"></div></div>
			<div class="pieItem"><div id="pieChartClicks"></div></div>
		</div>
	</div>
	<div id="chart-dialog" title="<?php _e('Statistical Data of Advertisement', SAM_PRO_DOMAIN); ?>">
		<div id="ad-chart"></div>
		<div class="sam-centered">
			<input type="button" id="close-chart" value="<?php _e('Close', SAM_PRO_DOMAIN); ?>" class="button-secondary">
		</div>
	</div>
</div>
			<?php
		}
	}
}