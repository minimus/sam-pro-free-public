<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 14.11.2016
 * Time: 16:28
 */
if ( ! class_exists( 'SamProAddonsList' ) ) {
	class SamProAddonsList {
		private $addons;

		public function __construct() {
			$this->addons = array(
				array(
					'name' => __( 'XAds', SAM_PRO_DOMAIN ),
					'desc' => __( 'extended visualisation of ads served by SAM Pro plugin.', SAM_PRO_DOMAIN ),
					'img'  => SAM_PRO_URL . 'images/xads-addon-255.jpg',
					'link' => 'http://uncle-sam.info/addons/xads/',
					'purchase' => 'https://codecanyon.net/item/xads-for-sam-pro/19343651?ref=minimus_simplelib'
				),
				array(
					'name' => __( 'Ad Slider', SAM_PRO_DOMAIN ),
					'desc' => __( 'provides possibility of rotating ads as slider for the ads rotating by timer.', SAM_PRO_DOMAIN ),
					'img'  => SAM_PRO_URL . 'images/ad-slider-addon-255.jpg',
					'link' => 'http://uncle-sam.info/addons/ad-slider/',
					'purchase' => 'https://codecanyon.net/item/ad-slider-for-sam-pro/17399741?ref=minimus_simplelib'
				),
				array(
					'name' => __( 'Advertising Request', SAM_PRO_DOMAIN ),
					'desc' => __( 'provides the ability to online order advertising on the site.', SAM_PRO_DOMAIN ),
					'img'  => SAM_PRO_URL . 'images/ad-request-plugin-ad.jpg',
					'link' => 'http://uncle-sam.info/addons/advertising-request/',
					'purchase' => 'https://codecanyon.net/item/advertising-request-for-sam-pro-free-edition/14562108?ref=minimus_simplelib'
				),
				array(
					'name' => __( 'Geo Targeting', SAM_PRO_DOMAIN ),
					'desc' => __( 'enables targeting of advertising depending on the visitor geolocation.', SAM_PRO_DOMAIN ),
					'img'  => SAM_PRO_URL . 'images/geo-targeting-plugin-ad.jpg',
					'link' => 'http://uncle-sam.info/addons/geo-targeting/',
					'purchase' => 'https://codecanyon.net/item/geo-targeting-addon-for-sam-pro-free-edition/14633737?ref=minimus_simplelib'
				),
			);
		}

		public function draw() {
			$out = '';
			$purchase = __('Purchase', SAM_PRO_DOMAIN);
			if ( ! is_null( $this->addons ) && is_array( $this->addons ) && ! empty( $this->addons ) ) {
				$out .= "<div id='addons-list' class='skitter skitter-small with-dots'><ul>";
				foreach ( $this->addons as $addon ) {
					$out .= "<li><a href='{$addon['link']}' target='_blank'><img src='{$addon['img']}'></a>";
					$out .= "<div class='label_text'><p><strong>{$addon['name']}</strong> {$addon['desc']}";
					$out .= " <a href='{$addon['purchase']}' class='btn btn-small btn-green'>{$purchase}</a></p></div></li>";
				}
				$out .= "</ul></div>";
			}
			return $out;
		}
	}
}