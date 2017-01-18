<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 10.05.2015
 * Time: 7:48
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists( 'SamProAd' ) ) {
	class SamProAd {
		private $aid;
		private $useTags;
		private $crawler;
		private $args;
		private $before;
		private $after;
		private $cntClass;
		private $adClass;
		private $eLog;
		private $settings;

		public $id = null;
		public $pid = null;
		public $cid = null;
		public $ad = '';
		public $img = null;
		public $link = null;
		public $width = 0;
		public $height = 0;

		public function __construct( $aid, $args = null, $tags = false, $crawler = false ) {
			$this->aid = $aid;
			$this->args = $args;
			$this->useTags = $tags;
			$this->crawler = $crawler;
			$this->settings = self::getSettings();

			self::prepareArgs();
			$this->ad = self::buildAd();
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
		$this->eLog = (isset($this->settings['errorlog'])) ? $this->settings['errorlog'] : false;
	}

		private function getData() {
			if(!isset($this->aid) || $this->aid < 0) return null;

			global $wpdb;
			$aTable = $wpdb->prefix . 'sampro_ads';

			$sql = "SELECT
  wsa.aid, wsa.img, wsa.link, wsa.alt, wsa.php, wsa.inline, wsa.hits, wsa.clicks,
  wsa.asize, wsa.width, wsa.height, wsa.acode, wsa.amode,wsa.rel,
  wsa.swf, wsa.swf_vars, wsa.swf_params, wsa.swf_attrs, wsa.swf_fallback
  FROM {$aTable} wsa
  WHERE wsa.aid = %d;";
			$out = $wpdb->get_row($wpdb->prepare($sql, $this->aid), ARRAY_A);

			return $out;
		}

		private function buildAd() {
			$data = self::getData(); //console($data['rel']);
			if(is_null($data)) return '';

			$out = '';
			$rId = rand(1111, 9999);
			$this->id = $data['aid'];
			$this->pid = 0;
			$this->cid = "c{$rId}_{$data['aid']}_0";
			$mode = $data['amode'];
			if ( $mode == 0 ) {
				$this->link = $data['link'];
				$this->img  = $data['img'];
			}
			$this->width  = $data['width'];
			$this->height = $data['height'];

			if($mode == 0) {
				if((integer)$data['swf']) {
					$id = "spa-{$this->aid}-{$rId}";
					$vars = (!empty($data['swf_vars'])) ? $data['swf_vars'] : '{}';
					$params = (!empty($data['swf_params'])) ? $data['swf_params'] : '{}';
					$attrs = (!empty($data['swf_attrs'])) ? $data['swf_attrs'] : '{}';
					$text = (isset($data['swf_fallback'])) ? $data['swf_fallback'] : __('Flash ad', SAM_PRO_DOMAIN).' ID:'.$data['aid'];
					$out = "
					<script type='text/javascript'>
					var flashvars = {$vars}, params = {$params}, attributes = {$attrs};
					attributes.id = '{$id}';
					attributes.styleclass = '{$this->adClass}';
					swfobject.embedSWF('{$data['img']}', '{$id}', '{$data['width']}', '{$data['height']}', '9.0.0', '', flashvars, params, attributes);
					</script>";
					$fallback = "<div id='$id'>$text</div>";
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
					$aStart ='';
					$aEnd ='';
					$robo = (integer)$data['rel'];
					$rel = ((in_array($robo, array(1,2,4))) ? ((in_array($robo, array(1,4))) ? " rel='nofollow'" : " rel='dofollow'") : '');
					$niStart = ((in_array($robo, array(3,4))) ? '<noindex>' : '');
					$niEnd = ((in_array($robo, array(3,4))) ? '</noindex>' : '');
					if(!empty($this->settings['adDisplay'])) $target = '_'.$this->settings['adDisplay'];
					else $target = '_blank';
					if(!empty($data['link'])) {
						$aStart = "{$niStart}<a {$id} href='{$data['link']}' target='{$target}'{$rel}>";
						$aEnd = "</a>{$niEnd}";
					}
					$iAlt = (!empty($data['alt'])) ? "alt='{$data['alt']}'" : '';
					$iTag = (!empty($data['img'])) ? "<img src='{$data['img']}' {$iAlt}>" : '';
					$out = $aStart . $iTag . $aEnd;
				}
			}
			else {
				if($data['php']) {
					ob_start();
					eval('?>'.$data['acode'].'<?');
					$out = ob_get_contents();
					ob_end_clean();
				}
				else $out = $data['acode'];
			}

			if(!empty($out)) {
				$cntTag = (1 === (int)$data['inline']) ? 'span' : 'div';
				$out =  "<{$cntTag} id='c{$rId}_{$data['aid']}_na' class='{$this->cntClass}'>{$out}</{$cntTag}>";
			}

			return $out;
		}
	}
}