<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 27.07.2015
 * Time: 17:03
 */
include_once('sam-pro-functions.php');
if( ! class_exists( 'SamProMailer' ) ) {
	class SamProMailer {
		private $settings;
		private $aList;
		private $error;
		private $month;
		private $year;
		private $first;
		private $last;

		public function __construct( $settings ) {
			$this->settings = $settings;
			$this->aList = self::getAdvertisersList();
		}

		private function getAdvertisersList() {
			global $wpdb;
			$aTable = $wpdb->prefix . 'sampro_ads';
			$sql = "SELECT DISTINCT sa.owner, sa.owner_name, sa.owner_mail FROM {$aTable} sa WHERE sa.owner IS NOT NULL AND sa.owner <> '';";
			$list = $wpdb->get_results($sql, ARRAY_A);
			return $list;
		}

		private function writeResult( $input = null ) {
			if(is_null($input)) return;

			global $wpdb;
			$eTable = $wpdb->prefix . "sampro_errors";

			$wpdb->insert(
				$eTable,
				array(
					'edate' => current_time('mysql'),
					'tname' => "Mailer",
					'etype' => 1,
					'emsg' => __('Mails were sent...', SAM_PRO_DOMAIN),
					'esql' => (
						(($input['success'] > 0) ? sprintf(_n('One mail was successfully sent. ', '%s mails were successfully sent. ', $input['success'], SAM_PRO_DOMAIN), $input['success']) : '') .
						(($input['errors'] > 0) ? sprintf(_n('There is one error during sending mails.', 'There are %s errors during sending mails.', $input['errors'], SAM_PRO_DOMAIN), $input['errors']) : '') .
						(__(' The success message does not automatically mean that the user received the email successfully. It just only means that the SAM plugin was able to process the request without any errors.', SAM_PRO_DOMAIN))
					),
					'solved' => 0
				),
				array('%s', '%s', '%d', '%s', '%s', '%d')
			);
		}

		private function getSiteInfo( $info = 'name' ) {
			$infos = array(
				'name' => 'blogname',
				'url' => 'siteurl',
				'admin_email' => 'admin_email'
			);

			if(function_exists('get_bloginfo')) $out = get_bloginfo($info);
			else {
				global $wpdb;
				$oTable = $wpdb->prefix . 'options';

				$oSql = "SELECT wo.option_value FROM $oTable wo WHERE wo.option_name = %s  LIMIT 1;";
				$out = $wpdb->get_var($wpdb->prepare($oSql, $infos[$info]));
			}
			return $out;
		}

		private function parseText( $text, $advert ) {
			$out = str_replace('[name]', $advert['owner_name'], $text);
			$out = str_replace('[site]', self::getSiteInfo(), $out);
			$out = str_replace('SAM Pro (Free Edition)', "<a href='http://uncle-sam.info' target='_blank'>SAM Pro (Free Edition)</a>", $out);
			$out = str_replace('[month]', $this->month, $out);
			$out = str_replace('[first]', $this->first, $out);
			$out = str_replace('[last]', $this->last, $out);
			$out = str_replace('[year]', $this->year, $out);

			$out = apply_filters('sam_pro_mailer_parse', $out, $advert);

			return $out;
		}

		private function getMailStyle() {
			return "
  <style type='text/css'>
    .sam-table {
      border-collapse: separate;
      border-spacing: 1px;
      background-color: #CDCDCD;
      margin: 10px 0 15px 0;
      font-size: 9pt;
      font-family: Arial,sans-serif;
      width: 100%;
      text-align: left;
      line-height: 20px;
    }
    .sam-table th {
      background-color: #E6EEEE;
      border: 1px solid #FFFFFF;
      padding: 4px;
      color: #3D3D3D!important;
    }
    .sam-table td {
      color: #3D3D3D;
      padding: 4px;
      background-color: #FFFFFF;
      vertical-align: top;
    }
    .even {border: 1px solid #ddd;}
    .even td {background-color: #FFFFFF;}
    .odd td {background-color: #FFFFE8;}
    .w25 {
      width: 25%;
    }
    .w10 {
      width: 10%;
    }
    .td-num {
      text-align: right;
    }
    .mess {
      font-family: Arial, Helvetica, Tahoma, sans-serif;
      font-size: 11px;
    }
    .total {font-size: 13px}
  </style>
      ";
		}

		private function buildMessage( $user ) {
			global $wpdb;

			$options = $this->settings;
			$pTable = $wpdb->prefix . 'sampro_places';
			$aTable = $wpdb->prefix . 'sampro_ads';
			$paTable = $wpdb->prefix . 'sampro_places_ads';
			$sTable = $wpdb->prefix . 'sampro_stats';

			$columns = array(
				'mail_hits' => 'Hits',
				'mail_clicks' => 'Clicks',
				'mail_cpm' => 'CPM',
				'mail_cpc' => 'CPC',
				'mail_ctr' => 'CTR'
			);

			$date = new DateTime('now');
			if($options['mail_period'] === 'monthly') {
				$date->modify('-1 month');
				$first = $date->format('Y-m-01 00:00:00');
				$last = $date->format('Y-m-t 23:59:59');
				$this->first = $first;
				$this->last = $last;
			}
			else {
				$date->modify('-1 week');
				$dd = 7 - ((integer) $date->format('N'));
				if($dd > 0) $date->modify("+{$dd} day");
				$last = $date->format('Y-m-d 23:59:59');
				$date->modify('-6 day');
				$first = $date->format('Y-m-d 00:00:00');

				$this->first = $first;
				$this->last = $last;
			}
			$this->month = $date->format('M');
			$this->year = $date->format('Y');

			$greeting = self::parseText($options['mail_greeting'], $user);
			$textBefore = self::parseText($options['mail_text_before'], $user);
			$textAfter = self::parseText($options['mail_text_after'], $user);
			$warning = self::parseText($options['mail_warning'], $user);
			$message = self::parseText($options['mail_message'], $user);

			$sql = "SELECT sss.pid, sss.aid, sss.title, sss.description, sss.hits, sss.clicks, sss.income,
  CAST(IF(sss.hits = 0, 0, (sss.income/sss.hits)*1000) AS DECIMAL(11,2)) AS cpm,
  CAST(IF(sss.hits = 0, 0, sss.clicks/sss.hits) AS DECIMAL(7,3)) AS ctr,
  CAST(IF(sss.clicks = 0, 0, sss.income/sss.clicks) AS DECIMAL(11,2)) AS cpc
  FROM
  (SELECT sms.pid, sms.aid, sms.title, sms.description,
    SUM(sms.hits) AS hits,
    SUM(sms.clicks) AS clicks,
    SUM(sms.income) AS income
    FROM
    (SELECT DATE_FORMAT(ss.edate, '%m') AS mdate, ss.pid, ss.aid, sa.title, sa.description,
      SUM(ss.hits) AS hits,
      SUM(ss.clicks) AS clicks,
      sp.price AS income
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid
      INNER JOIN {$aTable} sa ON ss.aid = sa.aid
      WHERE  ss.edate BETWEEN '{$first}' AND '{$last}' AND sp.sale AND sp.sale_mode = 1 AND sa.owner_mail = '{$user['owner_mail']}'
      GROUP BY mdate, ss.pid, ss.aid
    UNION
    SELECT DATE_FORMAT(ss.edate, '%m') AS mdate, ss.pid, ss.aid, sa.title, sa.description,
      SUM(ss.hits) AS hits,
      SUM(ss.clicks) AS clicks,
      CAST((sp.price / (SELECT COUNT(*) FROM {$paTable} spa INNER JOIN {$aTable} sa ON spa.aid = sa.aid WHERE spa.pid = ss.pid AND sa.owner_mail = '{$user['owner_mail']}')) AS DECIMAL(11,2)) AS income
      FROM {$sTable} ss
      INNER JOIN {$pTable} sp ON ss.pid = sp.pid
      INNER JOIN {$aTable} sa ON ss.aid = sa.aid
      WHERE  ss.edate BETWEEN '{$first}' AND '{$last}' AND sp.sale AND sp.sale_mode = 0 AND sa.owner_mail = '{$user['owner_mail']}'
      GROUP BY mdate, ss.pid, ss.aid) sms
    GROUP BY sms.pid, sms.aid
    ORDER BY sms.aid) sss
  ORDER BY sss.hits DESC;";
			$ads = $wpdb->get_results($sql, ARRAY_A);

			$this->error = $sql;

			$mess = '';

			if(!empty($ads) && is_array($ads)) {
				$sql = "SELECT SUM(ss.hits) AS hits, SUM(ss.clicks) AS clicks
  FROM {$sTable} ss
  INNER JOIN {$pTable} sp ON ss.pid = sp.pid
  INNER JOIN {$aTable} sa ON ss.aid = sa.aid
  WHERE ss.edate BETWEEN '{$first}' AND '{$last}'
    AND sp.sale AND ss.aid <> 0 AND sa.owner_mail = '{$user['owner_mail']}'";

				$stats = $wpdb->get_row($sql, ARRAY_A);
				$hits = $stats['hits'];
				$clicks = $stats['clicks'];

				$style = self::getMailStyle();
				$ths = '';
				foreach($columns as $key => $column)
					$ths .= (($options[$key]) ? "<th class='w10'>{$column}</th>" : '');
				$mess .= "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
  <title>Ad campaign report</title>
  {$style}
</head>
<body>
<p>{$greeting}</p>
<p>{$textBefore}</p>
<table class='sam-table'>
  <thead>
    <tr>
      <th class='w25'>Name</th>
      <th class='w25'>Description</th>
      {$ths}
    </tr>
  </thead>
  <tbody>";
				$k = 0;
				foreach($ads as $ad) {
					$cpm = number_format($ad['cpm'], 2);
					$cpc = number_format($ad['cpc'], 2);
					$ctr = number_format($ad['ctr'], 3) . '%';

					$class = ( ($k % 2) == 1 ) ? 'odd' : 'even';
					$mess .= "<tr class='{$class}'><td>{$ad['title']}</td><td>{$ad['description']}</td>";
					$mess .= (($options['mail_hits']) ? "<td class='td-num'>{$ad['hits']}</td>" : '');
					$mess .= (($options['mail_clicks']) ? "<td class='td-num'>{$ad['clicks']}</td>" : '');
					$mess .= (($options['mail_cpm']) ? "<td class='td-num'>{$cpm}</td>" : '');
					$mess .= (($options['mail_cpc']) ? "<td class='td-num'>{$cpc}</td>" : '');
					$mess .= (($options['mail_ctr']) ? "<td class='td-num'>{$ctr}</td>" : '');
					$mess .= "</tr>";
					$k++;
				}
				$mess .= "</tbody></table>";
				$mess .= "
<p class='total'>Impressions: {$hits}</p>
<p class='total'>Clicks: {$clicks}</p>
<p>{$textAfter}</p>
<p class='mess'>{$warning}</p>
<p class='mess'>{$message}</p>
</body>
</html>";
			}

			return $mess;
		}

		public function setContentType() {
			return 'text/html';
		}

		public function sendMail($user, $key = 'nick') {
			$column = ($key == 'nick') ? 'owner' : 'owner_' . $key;
			$advKey = array_search($user, array_column($this->aList, $column));
			$adv = $this->aList[$advKey];
			$success = false;

			if(!is_null($adv) && $adv !== false) {
				$headers = 'Content-type: text/html; charset=UTF-8' . "\r\n";
				$message = self::buildMessage( $adv );
				$subject = self::parseText( $this->settings['mail_subject'], $adv );
				if ( ! empty( $message ) ) {
					if ( function_exists( 'wp_mail' ) ) {
						$success = wp_mail( $adv['owner_mail'], $subject, $message, $headers );
					} else {
						$samAdminMail = self::getSiteInfo( 'admin_email' );
						$headers .= "From: SAM Pro (Free Edition) Info <{$samAdminMail}>" . "\r\n";
						$success = mail( $adv['owner_mail'], $subject, $message, $headers );
					}
				}
			}

			return $success;
		}

		public function sendMails() {
			$k = 0; $s = 0; $e = 0;
			$advertisers = $this->aList;
			if(!empty($advertisers) && is_array($advertisers)) {
				$headers = 'Content-type: text/html; charset=UTF-8' . "\r\n";
				//$headers .= 'From: Tests <wordpress@simplelib.com>' . "\r\n";
				foreach($advertisers as $adv) {
					$success = false;
					$message = self::buildMessage($adv);
					$subject = self::parseText($this->settings['mail_subject'], $adv);
					if(!empty($message)) {
						if(function_exists('wp_mail')) $success = wp_mail($adv['owner_mail'], $subject, $message, $headers);
						else {
							$samAdminMail = self::getSiteInfo('admin_email');
							$headers .= "From: SAM Pro Lite Info <{$samAdminMail}>" . "\r\n";
							$success = mail($adv['owner_mail'], $subject, $message, $headers);
						}
						($success) ? $s++ : $e++;
						$k++;
					}
				}
				self::writeResult(array('success' => $s, 'errors' => $e));
			}
			return ($k == 0) ? $this->error : $k;
		}

		public function buildPreview($user) {
			$date = new DateTime('now');
			if($this->settings['mail_period'] === 'monthly') {
				$date->modify('-1 month');
				$first = $date->format('Y-m-01 00:00:00');
				$last = $date->format('Y-m-t 23:59:59');
				$this->first = $first;
				$this->last = $last;
			}
			else {
				$date->modify('-1 week');
				$dd = 7 - ((integer) $date->format('N'));
				if($dd > 0) $date->modify("+{$dd} day");
				$last = $date->format('Y-m-d 23:59:59');
				$date->modify('-6 day');
				$first = $date->format('Y-m-d 00:00:00');

				$this->first = $first;
				$this->last = $last;
			}

			$this->month = $date->format('M');
			$this->year = $date->format('Y');

			$options = $this->settings;
			$greeting = self::parseText($options['mail_greeting'], $user);
			$textBefore = self::parseText($options['mail_text_before'], $user);
			$textAfter = self::parseText($options['mail_text_after'], $user);
			$warning = self::parseText($options['mail_warning'], $user);
			$message = self::parseText($options['mail_message'], $user);

			$ads = array(
				array(
					'name' => 'Header Ad',
					'description' => 'Ad in the header of blog.',
					'ad_hits' => 10000,
					'ad_clicks' => 10,
					'e_cpm' => 95.36,
					'e_cpc' => 15.00,
					'e_ctr' => 0.1
				),
				array(
					'name' => 'Sidebar Ad',
					'description' => 'Ad in the sidebar of blog.',
					'ad_hits' => 5000,
					'ad_clicks' => 1,
					'e_cpm' => 99.99,
					'e_cpc' => 10.00,
					'e_ctr' => 0.02
				),
				array(
					'name' => 'Footer Ad',
					'description' => 'Ad in the footer of blog.',
					'ad_hits' => 8000,
					'ad_clicks' => 5,
					'e_cpm' => 9.9936,
					'e_cpc' => 5.00,
					'e_ctr' => 0.0625
				)
			);
			$hits = 23000;
			$clicks = 16;

			$columns = array(
				'mail_hits' => 'Hits',
				'mail_clicks' => 'Clicks',
				'mail_cpm' => 'CPM',
				'mail_cpc' => 'CPC',
				'mail_ctr' => 'CTR'
			);
			$ths = '';
			foreach($columns as $key => $column)
				$ths .= (($options[$key]) ? "<th class='w10'>{$column}</th>" : '');

			$mess = "<p>{$greeting}</p>
<p>{$textBefore}</p>
<table class='sam-table'>
  <thead>
    <tr>
      <th class='w25'>Name</th>
      <th class='w25'>Description</th>
      {$ths}
    </tr>
  </thead>
  <tbody>";
			$k = 0;
			foreach($ads as $ad) {
				$cpm = number_format($ad['e_cpm'], 2);
				$cpc = number_format($ad['e_cpc'], 2);
				$ctr = number_format($ad['e_ctr'], 3) . '%';

				$class = ( ($k % 2) == 1 ) ? 'odd' : 'even';
				$mess .= "<tr class='{$class}'><td>{$ad['name']}</td><td>{$ad['description']}</td>";
				$mess .= (($options['mail_hits']) ? "<td class='td-num'>{$ad['ad_hits']}</td>" : '');
				$mess .= (($options['mail_clicks']) ? "<td class='td-num'>{$ad['ad_clicks']}</td>" : '');
				$mess .= (($options['mail_cpm']) ? "<td class='td-num'>{$cpm}</td>" : '');
				$mess .= (($options['mail_cpc']) ? "<td class='td-num'>{$cpc}</td>" : '');
				$mess .= (($options['mail_ctr']) ? "<td class='td-num'>{$ctr}</td>" : '');
				$mess .= "</tr>";
				$k++;
			}
			$mess .= "</tbody></table>";
			$mess .= "
<p class='total'>Impressions: {$hits}</p>
<p class='total'>Clicks: {$clicks}</p>
<p>{$textAfter}</p>
<p class='mess'>{$warning}</p>
<p class='mess'>{$message}</p>";

			return $mess;
		}
	}
}