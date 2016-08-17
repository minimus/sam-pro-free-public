<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 07.08.2016
 * Time: 18:47
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProInfoPointers' ) ) {
	class SamProInfoPointers {
		private $options = array( 'adSlider' => true );
		private $settings;
		private $pointers;
		public $pointer;

		public function __construct( $pointers = null ) {
			$this->pointers = $pointers;
			$this->settings = self::getOptions( true );
			$this->pointer  = self::getContent();
			if ( ! is_null( $this->pointer ) ) {
				add_action( 'wp_ajax_close_sam_pro_pointer', array( &$this, 'closePointerHandler' ) );
			}
		}

		public function getOptions( $force = false ) {
			if ( $force ) {
				$pts      = get_option( 'sam_pro_pointers', array() );
				$pointers = wp_parse_args( $pts, $this->options );
				if ( empty( $pts ) ) {
					update_option( 'sam_pro_pointers', $pointers );
				}
				$this->settings = $pointers;
			} else {
				$pointers = $this->settings;
			}

			return $pointers;
		}

		private function getCurrentPointer() {
			$out = null;
			foreach ( $this->settings as $key => $value ) {
				if ( $value ) {
					$out = $key;
					break;
				}
			}

			return $out;
		}

		private function getContent() {
			$pointer = self::getCurrentPointer();
			$content = null;
			if ( ! is_null( $pointer ) ) {
				switch ( $pointer ) {
					case 'adSlider':
						$image    = SAM_PRO_URL . 'images/ad-slider-addon-350.jpg';
						$title    = __( 'Ad Slider for SAM Pro', SAM_PRO_DOMAIN );
						$intro    = __( 'The Ad Slider is an addon for the SAM Pro (Free and Lite editions) plugin that provides possibility of rotating ads as slider for the ads that are rotating by timer.', SAM_PRO_DOMAIN );
						$intro2   = __( 'Purchase Ad Slider', SAM_PRO_DOMAIN );
						$moreInfo = __( 'More Info...', SAM_PRO_DOMAIN );
						$features = __( 'Features', SAM_PRO_DOMAIN );
						$f1       = __( 'Possibility of rotating ads by timer', SAM_PRO_DOMAIN );
						$f2       = __( 'Possibility of rotating ads as slider', SAM_PRO_DOMAIN );
						$f3       = __( 'Two sliding effects (horizontal and fade)', SAM_PRO_DOMAIN );
						$f4       = __( 'Possibility of adjusting parameters of slider', SAM_PRO_DOMAIN );
						$f5       = __( 'Possibility of sliding any type of ads', SAM_PRO_DOMAIN );
						$f6       = __( 'Responsive', SAM_PRO_DOMAIN );
						$f7       = __( 'Ready for localizing', SAM_PRO_DOMAIN );
						$content  = array(
							'title'   => $title,
							'content' => "<div style='text-align: center; margin: 20px 15px 0;'>" .
							             "<a href='https://codecanyon.net/item/ad-slider-for-sam-pro/17399741?ref=minimus_simplelib' target='_blank'>" .
							             "<img src='{$image}' alt='{$title}'>" .
							             "</a>" .
							             "</div>" .
							             "<p>{$intro}</p>" .
							             "<h4 style='padding: 0 15px;'>{$features}</h4>" .
							             "<ul style='padding: 0 15px;'>" .
							             " 	<li style='padding: 0 15px;'>{$f1}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f2}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f3}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f4}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f5}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f6}</li>" .
							             " 	<li style='padding: 0 15px;'>{$f7}</li>" .
							             "</ul>" .
							             "<p><a href='http://uncle-sam.info/addons/ad-slider/' target='_blank'><strong>{$moreInfo}</strong></a><br>" .
							             "<a href='https://codecanyon.net/item/ad-slider-for-sam-pro/17399741?ref=minimus_simplelib' target='_blank'><strong>{$intro2}</strong></a></p>",
							'key'     => $pointer
						);
						break;
					default:
						$content = null;
						break;
				}
			}

			return $content;
		}

		public function closePointerHandler() {
			$pointer  = null;
			$settings = self::getOptions();
			$charset  = get_bloginfo( 'charset' );
			@header( "Content-Type: application/json; charset={$charset}" );
			if ( isset( $_REQUEST['pointerKey'] ) ) {
				$pointer              = $_REQUEST['pointerKey'];
				$settings[ $pointer ] = false;
				update_option( 'sam_pro_pointers', $settings );
				wp_send_json_success( array( 'pointer' => $pointer, 'options' => $settings ) );
			} else {
				wp_send_json_error( array( 'pointer' => $pointer, 'options' => $settings ) );
			}
		}
	}
}