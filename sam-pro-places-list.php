<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 24.12.2014
 * Time: 14:19
 */

if ( ! class_exists( 'SamProPlacesList' ) ) {
	class SamProPlacesList {
		private $settings;

		public function __construct( $settings ) {
			$this->settings = $settings;
		}

		public function show() {
			$ddList = '<script id="viewMode" type="text/x-jsrender"><select id="view-mode">
        		<option value="10">'. __('All', SAM_PRO_DOMAIN) .'</option>
        		<option value="0">' . __('Active', SAM_PRO_DOMAIN) . '</option>
        		<option value="1">' . __('In Trash', SAM_PRO_DOMAIN) . '</option>
    			</select></script>';
			$newLink = admin_url('admin.php') . '?page=sam-pro-place-editor';
			$newA = "<script id='NewPlace' type='text/x-jsrender'><a class='e-toolbaricons e-icon new-ad' href='{$newLink}'/></script>";
			?>
			<script id="TrashPlace" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon place-to-trash"/>
			</script>
			<script id="UnTrashPlace" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon place-from-trash"/>
			</script>
			<script id="Trash" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon to-trash"/>
			</script>
			<?php echo $ddList; ?>
			<?php echo $newA ?>
			<div class="wrap">
				<h2><?php _e( 'Manage Ads Places', SAM_PRO_DOMAIN ); ?></h2>
				<div id="grid"></div>
				<h4><?php _e('Legend', SAM_PRO_DOMAIN); ?></h4>
				<ul>
					<li><i class="icon-ok"></i>/<i class="icon-traffic-cone"></i> - <?php _e('Approved / Awaiting approval.', SAM_PRO_DOMAIN); ?></li>
					<li><i class="icon-leaf"></i>/<i class="icon-trash"></i> - <?php _e('Is active / Is in trash.', SAM_PRO_DOMAIN); ?></li>
					<li><i class="icon-eye"></i>/<i class="icon-eye-off"></i> - <?php _e('Potentially visible / Always invisible.', SAM_PRO_DOMAIN); ?></li>
				</ul>
				<div id="adsDialog" title="<?php _e('Select Ads', SAM_PRO_DOMAIN); ?>">
					<div id="adsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-ads" value="<?php _e('Select', SAM_PRO_DOMAIN); ?>" class="button-secondary">&nbsp;
						<input type="button" id="cancel-ads" value="<?php _e('Cancel', SAM_PRO_DOMAIN); ?>" class="button-secondary cancel-dialog">
					</div>
				</div>
			</div>
		<?php
		}
	}
}