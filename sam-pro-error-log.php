<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 26.04.2015
 * Time: 10:44
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists( 'SamProErrorLog' ) ) {
	class SamProErrorLog {
		private $settings;

		public function __construct( $settings ) {
			$this->settings = $settings;
		}

		public function show() {
			$ddList = '<script id="viewMode" type="text/x-jsrender"><select id="view-mode">
        		<option value="10">'. __('All', SAM_PRO_DOMAIN) .'</option>
        		<option value="0">' . __('Errors', SAM_PRO_DOMAIN) . '</option>
        		<option value="1">' . __('Info', SAM_PRO_DOMAIN) . '</option>
    			</select></script>';
			echo $ddList;
			?>
			<script id="MoreInfo" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon more-info"/>
			</script>
			<script id="ErrorSolved" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon error-solved"/>
			</script>
			<script id="ClearErrors" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon clear-errors"/>
			</script>
			<div class="wrap">
				<h2><?php _e('Error Log', SAM_PRO_DOMAIN); ?></h2>
				<div id="grid"></div>
				<h4><?php _e('Legend', SAM_PRO_DOMAIN); ?></h4>
				<ul>
					<li><i class="icon-ok"></i> - <?php _e('Info Message', SAM_PRO_DOMAIN); ?></li>
					<li><i class="icon-warning-empty"></i>/<i class="icon-shield"></i> - <?php _e('Error / Error (resolved).', SAM_PRO_DOMAIN); ?></li>
				</ul>
				<div id="info-dialog" title="<?php _e('More Info', SAM_PRO_DOMAIN); ?>">
					<div id="info-body"></div>
					<div class="sam-centered"><button id="close-dialog"><?php _e('Close', SAM_PRO_DOMAIN); ?></button></div>
				</div>
			</div>
			<?php
		}
	}
}