<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 12.06.2015
 * Time: 15:26
 */

if(!class_exists( 'sam_pro_place_widget' ) && class_exists('WP_Widget')) {
	include_once('sam-pro-place.php');
	class sam_pro_place_widget extends WP_Widget {
		protected $crawler = false;
		protected $aTitle = '';
		protected $wTable = '';
		private $disableAdServing = false;

		public function __construct() {
			if(!defined('SAM_PRO_OPTIONS_NAME')) define('SAM_PRO_OPTIONS_NAME', 'samProOptions');
			$this->crawler = $this->isCrawler();
			$this->aTitle = __('Ads Place:', SAM_PRO_DOMAIN);
			$this->wTable = 'sampro_places';

			$widget_ops = array( 'classname' => 'sam_pro_place_widget', 'description' => __('Ads Place rotator serviced by Simple Ads Manager (Pro Edition).', SAM_PRO_DOMAIN));
			$control_ops = array( 'id_base' => 'sam_pro_place_widget' );
			parent::__construct( 'sam_pro_place_widget', __('SAM Pro Place', SAM_PRO_DOMAIN), $widget_ops, $control_ops );
		}

		public function getSettings() {
			$options = get_option(SAM_PRO_OPTIONS_NAME, '');
			return $options;
		}

		private function isCrawler() {
			$options = $this->getSettings();
			$crawler = false;

			if((int)$options['detectBots'] == 1) {
				switch($options['detectingMode']) {
					case 'inexact':
						if(((!isset($_SERVER["HTTP_USER_AGENT"])) ? true : ($_SERVER["HTTP_USER_AGENT"] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT'])) ? true : ($_SERVER['HTTP_ACCEPT'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? true : ($_SERVER['HTTP_ACCEPT_ENCODING'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? true : ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == '')) ||
						   ((!isset($_SERVER['HTTP_CONNECTION'])) ? true : $_SERVER['HTTP_CONNECTION'] == '') ||
						   is_admin())
							$crawler = true;
						break;

					case 'exact':
						include_once('tools/CrawlerDetect.php');
						$browser = new CrawlerDetect();
						$crawler = $browser->isCrawler() || is_admin();
						break;

					case 'more':
						if(ini_get("browscap")) {
							$browser = get_browser(null, true);
							$crawler = $browser['crawler'] || is_admin();
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

		private function adsEnabled() {
			global $post;
			if(is_single() || is_page()) {
				$postId = $post->ID;
				$val = get_post_meta( $postId, 'sam_pro_disable_ad_serving', true );
				$this->disableAdServing = (!empty($val));
			}
		}

		function widget( $args, $instance ) {
			self::adsEnabled();
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
			$adp_id = $instance['adp_id'];
			$hide_style = $instance['hide_style'];
			$place_codes = $instance['place_codes'];

			if(!is_admin() && !$this->disableAdServing) {
				$ad = new SamProPlace($adp_id, array('id' => $adp_id), $place_codes, $this->crawler);
				$content = $ad->ad;
			}
			else $content = '';
			if(!empty($content)) {
				if ( !$hide_style ) {
					echo $args['before_widget'];
					if ( !empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];
					echo $content;
					echo $args['after_widget'];
				}
				else echo $content;
			}
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['adp_id'] = $new_instance['adp_id'];
			$instance['hide_style'] = isset($new_instance['hide_style']);
			$instance['place_codes'] = isset($new_instance['place_codes']);
			return $instance;
		}

		function form( $instance ) {
			global $wpdb;
			$pTable = $wpdb->prefix . $this->wTable;

			$ids = $wpdb->get_results("SELECT sp.pid, sp.title FROM $pTable sp WHERE sp.trash IS FALSE", ARRAY_A);

			$instance = wp_parse_args((array) $instance,
				array(
					'title'       => '',
					'adp_id'      => '',
					'parse'       => false,
					'hide_style'  => 0,
					'place_codes' => 0
				)
			);
			$title = strip_tags($instance['title']);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', SAM_PRO_DOMAIN); ?></label>
				<input class="widefat"
				       id="<?php echo $this->get_field_id('title'); ?>"
				       name="<?php echo $this->get_field_name('title'); ?>"
				       type="text" value="<?php echo esc_attr($title); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('adp_id'); ?>"><?php echo $this->aTitle; ?></label>
				<select class="widefat"
				        id="<?php echo $this->get_field_id('adp_id'); ?>"
				        name="<?php echo $this->get_field_name('adp_id'); ?>">
					<?php
					foreach ($ids as $option) {
						$selected = ( ( $instance['adp_id'] === $option['pid'] ) ? ' selected' : '' );
						echo "<option value='{$option['pid']}'{$selected}>{$option['title']}</option>";
					}
					?>
				</select>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('hide_style'); ?>"
					name="<?php echo $this->get_field_name('hide_style'); ?>"
					type="checkbox" <?php checked($instance['hide_style']); ?>>&nbsp;
				<label for="<?php echo $this->get_field_id('hide_style'); ?>">
					<?php _e('Hide widget style.', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('place_codes'); ?>"
					name="<?php echo $this->get_field_name('place_codes'); ?>"
					type="checkbox" <?php checked($instance['place_codes']); ?>>&nbsp;
				<label for="<?php echo $this->get_field_id('place_codes'); ?>">
					<?php _e('Allow using previously defined "before" and "after" codes of Ads Place..', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
		<?php
		}
	}
}

if(!class_exists('sam_pro_ad_widget') && class_exists('WP_Widget')) {
	include_once('sam-pro-ad.php');
	class sam_pro_ad_widget extends WP_Widget {
		private $crawler = false;
		private $aTitle = '';
		private $wTable = '';
		private $disableAdServing = false;

		function __construct() {
			if(!defined('SAM_PRO_OPTIONS_NAME')) define('SAM_PRO_OPTIONS_NAME', 'samProOptions');
			$this->crawler = self::isCrawler();
			$this->aTitle = __('Ad', SAM_PRO_DOMAIN).':';
			$this->wTable = 'sampro_ads';

			$widget_ops = array( 'classname' => 'sam_pro_ad_widget', 'description' => __('Non-rotating single ad serviced by Simple Ads Manager (Pro Edition).', SAM_PRO_DOMAIN));
			$control_ops = array( 'id_base' => 'sam_pro_ad_widget' );
			parent::__construct( 'sam_pro_ad_widget', __('SAM Pro Single Ad', SAM_PRO_DOMAIN), $widget_ops, $control_ops );
		}

		public function getSettings() {
			$options = get_option(SAM_PRO_OPTIONS_NAME, '');
			return $options;
		}

		private function isCrawler() {
			$options = self::getSettings();
			$crawler = false;

			if($options['detectBots'] == 1) {
				switch($options['detectingMode']) {
					case 'inexact':
						if(((!isset($_SERVER["HTTP_USER_AGENT"])) ? true : ($_SERVER["HTTP_USER_AGENT"] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT'])) ? true : ($_SERVER['HTTP_ACCEPT'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? true : ($_SERVER['HTTP_ACCEPT_ENCODING'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? true : ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == '')) ||
						   ((!isset($_SERVER['HTTP_CONNECTION'])) ? true : $_SERVER['HTTP_CONNECTION'] == '') ||
						   is_admin())
							$crawler = true;
						break;

					case 'exact':
						include_once('tools/CrawlerDetect.php');
						$browser = new CrawlerDetect();
						$crawler = $browser->isCrawler() || is_admin();
						break;

					case 'more':
						if(ini_get("browscap")) {
							$browser = get_browser(null, true);
							$crawler = $browser['crawler'] || is_admin();
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

		private function adsEnabled() {
			global $post;
			if(is_single() || is_page()) {
				$postId = $post->ID;
				$val = get_post_meta( $postId, 'sam_pro_disable_ad_serving', true );
				$this->disableAdServing = (!empty($val));
			}
		}

		public function widget( $args, $instance ) {
			self::adsEnabled();
			extract($args);
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
			$adp_id = $instance['adp_id'];
			$hide_style = $instance['hide_style'];
			//$ad_codes = $instance['ad_codes'];

			if($this->disableAdServing) $content = '';
			else {
				$ad = new SamProAd($adp_id, array('id' => $adp_id), false, $this->crawler);
				$content = $ad->ad;
			}
			if(!empty($content)) {
				if ( !$hide_style ) {
					echo $args['before_widget'];
					if ( !empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];
					echo $content;
					echo $args['after_widget'];
				}
				else echo $content;
			}
		}

		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['adp_id'] = $new_instance['adp_id'];
			$instance['hide_style'] = isset($new_instance['hide_style']);
			//$instance['ad_codes'] = isset($new_instance['ad_codes']);
			return $instance;
		}

		public function form( $instance ) {
			global $wpdb;
			$aTable = $wpdb->prefix . $this->wTable;

			$ids = $wpdb->get_results("SELECT sa.aid, sa.title FROM $aTable sa WHERE sa.trash = 0;", ARRAY_A);

			$instance = wp_parse_args((array) $instance,
				array(
					'title'       => '',
					'adp_id'      => '',
					'parse'       => false,
					'hide_style'  => 0
				)
			);
			$title = strip_tags($instance['title']);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', SAM_PRO_DOMAIN); ?></label>
				<input class="widefat"
				       id="<?php echo $this->get_field_id('title'); ?>"
				       name="<?php echo $this->get_field_name('title'); ?>"
				       type="text" value="<?php echo esc_attr($title); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('adp_id'); ?>"><?php echo $this->aTitle; ?></label>
				<select class="widefat"
				        id="<?php echo $this->get_field_id('adp_id'); ?>"
				        name="<?php echo $this->get_field_name('adp_id'); ?>">
					<?php
					foreach ($ids as $option)
						echo '<option value='.$option['aid'].(($instance['adp_id'] === $option['aid']) ? ' selected' : '' ).'>'.$option['title'].'</option>';
					?>
				</select>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('hide_style'); ?>"
					name="<?php echo $this->get_field_name('hide_style'); ?>"
					type="checkbox" <?php checked($instance['hide_style']); ?>>&nbsp;
				<label for="<?php echo $this->get_field_id('hide_style'); ?>">
					<?php _e('Hide widget style.', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
		<?php
		}
	}
}

if( ! class_exists('sam_pro_zone_widget') && class_exists('WP_Widget') ) {
	include_once('sam-pro-zone.php');
	class sam_pro_zone_widget extends WP_Widget {
		private $crawler;
		private $aTitle;
		private $wTable;
		private $disableAdServing = false;

		public function __construct() {
			if(!defined('SAM_PRO_OPTIONS_NAME')) define('SAM_PRO_OPTIONS_NAME', 'samProOptions');
			$this->crawler = self::isCrawler();
			$this->aTitle = __('Zone', SAM_PRO_DOMAIN).':';
			$this->wTable = 'sampro_zones';

			$widget_ops = array( 'classname' => 'sam_pro_zone_widget', 'description' => __('Zone selector serviced by Simple Ads Manager (Pro Edition).', SAM_PRO_DOMAIN));
			$control_ops = array( 'id_base' => 'sam_pro_zone_widget' );
			parent::__construct( 'sam_pro_zone_widget', __('SAM Pro Zone', SAM_PRO_DOMAIN), $widget_ops, $control_ops );
		}

		public function getSettings() {
			$options = get_option(SAM_PRO_OPTIONS_NAME, '');
			return $options;
		}

		private function isCrawler() {
			$options = self::getSettings();
			$crawler = false;

			if($options['detectBots'] == 1) {
				switch($options['detectingMode']) {
					case 'inexact':
						if(((!isset($_SERVER["HTTP_USER_AGENT"])) ? true : ($_SERVER["HTTP_USER_AGENT"] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT'])) ? true : ($_SERVER['HTTP_ACCEPT'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? true : ($_SERVER['HTTP_ACCEPT_ENCODING'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? true : ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == '')) ||
						   ((!isset($_SERVER['HTTP_CONNECTION'])) ? true : $_SERVER['HTTP_CONNECTION'] == '') ||
						   is_admin())
							$crawler = true;
						break;

					case 'exact':
						include_once('tools/CrawlerDetect.php');
						$browser = new CrawlerDetect();
						$crawler = $browser->isCrawler() || is_admin();
						break;

					case 'more':
						if(ini_get("browscap")) {
							$browser = get_browser(null, true);
							$crawler = $browser['crawler'] || is_admin();
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

		private function adsEnabled() {
			global $post;
			if(is_single() || is_page()) {
				$postId = $post->ID;
				$val = get_post_meta( $postId, 'sam_pro_disable_ad_serving', true );
				$this->disableAdServing = (!empty($val));
			}
		}

		function widget( $args, $instance ) {
			self::adsEnabled();
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
			$adp_id = $instance['adp_id'];
			$hide_style = $instance['hide_style'];
			$place_codes = $instance['place_codes'];

			if($this->disableAdServing) $content = '';
			else {
				$ad = new SamProZone($adp_id, array('id' => $adp_id), $place_codes, $this->crawler);
				$content = $ad->ad;
			}
			if(!empty($content)) {
				if ( !$hide_style ) {
					echo $args['before_widget'];
					if ( !empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];
					echo $content;
					echo $args['after_widget'];
				}
				else echo $content;
			}
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['adp_id'] = $new_instance['adp_id'];
			$instance['hide_style'] = isset($new_instance['hide_style']);
			$instance['place_codes'] = isset($new_instance['place_codes']);
			return $instance;
		}

		function form( $instance ) {
			global $wpdb;
			$zTable = $wpdb->prefix . $this->wTable;

			$ids = $wpdb->get_results("SELECT sz.zid, sz.title FROM $zTable sz WHERE sz.trash IS FALSE", ARRAY_A);

			$instance = wp_parse_args((array) $instance,
				array(
					'title'       => '',
					'adp_id'      => '',
					'parse'       => false,
					'hide_style'  => 0,
					'place_codes' => 0
				)
			);
			$title = strip_tags($instance['title']);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', SAM_PRO_DOMAIN); ?></label>
				<input class="widefat"
				       id="<?php echo $this->get_field_id('title'); ?>"
				       name="<?php echo $this->get_field_name('title'); ?>"
				       type="text" value="<?php echo esc_attr($title); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('adp_id'); ?>"><?php echo $this->aTitle; ?></label>
				<select class="widefat"
				        id="<?php echo $this->get_field_id('adp_id'); ?>"
				        name="<?php echo $this->get_field_name('adp_id'); ?>" >
					<?php
					foreach ($ids as $option)
						echo '<option value='.$option['zid'].(($instance['adp_id'] === $option['zid']) ? ' selected' : '' ).'>'.$option['title'].'</option>';
					?>
				</select>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('hide_style'); ?>"
					name="<?php echo $this->get_field_name('hide_style'); ?>"
					type="checkbox" <?php checked($instance['hide_style']); ?> />&nbsp;
				<label for="<?php echo $this->get_field_id('hide_style'); ?>">
					<?php _e('Hide widget style.', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('place_codes'); ?>"
					name="<?php echo $this->get_field_name('place_codes'); ?>"
					type="checkbox" <?php checked($instance['place_codes']); ?> />&nbsp;
				<label for="<?php echo $this->get_field_id('place_codes'); ?>">
					<?php _e('Allow using previously defined "before" and "after" codes of Ads Place..', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
		<?php
		}
	}
}

if( ! class_exists('sam_pro_block_widget') && class_exists('WP_Widget') ) {
	include_once('sam-pro-block.php');
	class sam_pro_block_widget extends WP_Widget {
		private $crawler = false;
		private $aTitle = '';
		private $wTable = '';
		private $disableAdServing = false;

		public function __construct() {
			if(!defined('SAM_PRO_OPTIONS_NAME')) define('SAM_PRO_OPTIONS_NAME', 'samProOptions');
			$this->crawler = self::isCrawler();
			$this->aTitle = __('Block', SAM_PRO_DOMAIN).':';
			$this->wTable = 'sampro_blocks';

			$widget_ops = array(
				'classname' => 'sam_pro_block_widget',
				'description' => __('The grid of the Ad Objects serviced by Simple Ads Manager (Pro Edition).', SAM_PRO_DOMAIN)
			);
			$control_ops = array( 'id_base' => 'sam_pro_block_widget' );
			parent::__construct( 'sam_pro_block_widget', __('SAM Pro Block', SAM_PRO_DOMAIN), $widget_ops, $control_ops );
		}

		public function getSettings() {
			$options = get_option(SAM_PRO_OPTIONS_NAME, '');
			return $options;
		}

		private function isCrawler() {
			$options = self::getSettings();
			$crawler = false;

			if($options['detectBots'] == 1) {
				switch($options['detectingMode']) {
					case 'inexact':
						if(((!isset($_SERVER["HTTP_USER_AGENT"])) ? true : ($_SERVER["HTTP_USER_AGENT"] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT'])) ? true : ($_SERVER['HTTP_ACCEPT'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? true : ($_SERVER['HTTP_ACCEPT_ENCODING'] == '')) ||
						   ((!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? true : ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == '')) ||
						   ((!isset($_SERVER['HTTP_CONNECTION'])) ? true : $_SERVER['HTTP_CONNECTION'] == '') ||
						   is_admin())
							$crawler = true;
						break;

					case 'exact':
						include_once('tools/CrawlerDetect.php');
						$browser = new CrawlerDetect();
						$crawler = $browser->isCrawler() || is_admin();
						break;

					case 'more':
						if(ini_get("browscap")) {
							$browser = get_browser(null, true);
							$crawler = $browser['crawler'] || is_admin();
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

		private function adsEnabled() {
			global $post;
			if(is_single() || is_page()) {
				$postId = $post->ID;
				$val = get_post_meta( $postId, 'sam_pro_disable_ad_serving', true );
				$this->disableAdServing = (!empty($val));
			}
		}

		public function widget( $args, $instance ) {
			self::adsEnabled();
			extract($args);
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
			$adp_id = $instance['adp_id'];
			$hide_style = $instance['hide_style'];

			if($this->disableAdServing) $content = '';
			else {
				$block = new SamProBlock($adp_id, array('id' => $adp_id), $this->crawler);
				$content = $block->ad;
			}
			if(!empty($content)) {
				if ( !$hide_style ) {
					echo $args['before_widget'];
					if ( !empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];
					echo $content;
					echo $args['after_widget'];
				}
				else echo $content;
			}
		}

		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['adp_id'] = $new_instance['adp_id'];
			$instance['hide_style'] = isset($new_instance['hide_style']);
			return $instance;
		}

		public function form( $instance ) {
			global $wpdb;
			$bTable = $wpdb->prefix . $this->wTable;

			$ids = $wpdb->get_results("SELECT sb.bid, sb.title FROM {$bTable} sb WHERE sb.trash = 0;", ARRAY_A);

			$instance = wp_parse_args((array) $instance,
				array(
					'title'       => '',
					'adp_id'      => '',
					'parse'       => false,
					'hide_style'  => 0
				)
			);
			$title = strip_tags($instance['title']);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', SAM_PRO_DOMAIN); ?></label>
				<input class="widefat"
				       id="<?php echo $this->get_field_id('title'); ?>"
				       name="<?php echo $this->get_field_name('title'); ?>"
				       type="text" value="<?php echo esc_attr($title); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('adp_id'); ?>"><?php echo $this->aTitle; ?></label>
				<select class="widefat"
				        id="<?php echo $this->get_field_id('adp_id'); ?>"
				        name="<?php echo $this->get_field_name('adp_id'); ?>" >
					<?php
					foreach ($ids as $option)
						echo '<option value='.$option['bid'].(($instance['adp_id'] === $option['bid']) ? ' selected' : '' ).'>'.$option['title'].'</option>';
					?>
				</select>
			</p>
			<p>
				<input
					id="<?php echo $this->get_field_id('hide_style'); ?>"
					name="<?php echo $this->get_field_name('hide_style'); ?>"
					type="checkbox" <?php checked($instance['hide_style']); ?> />&nbsp;
				<label for="<?php echo $this->get_field_id('hide_style'); ?>">
					<?php _e('Hide widget style.', SAM_PRO_DOMAIN); ?>
				</label>
			</p>
		<?php
		}
	}
}