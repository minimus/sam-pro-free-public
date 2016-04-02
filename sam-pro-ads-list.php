<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 25.12.2014
 * Time: 14:39
 */

if( ! class_exists( 'SamProAdsList' ) ) {
	class SamProAdsList {
		private $settings;

		public function __construct( $settings ) {
			$this->settings = $settings;
		}

		public function show() {
			$newLink = admin_url('admin.php') . '?page=sam-pro-ad-editor';
			$newA = "<script id='NewAd' type='text/x-jsrender'><a class='e-toolbaricons e-icon new-ad' href='{$newLink}'/></script>";
			$ddList = '<script id="viewMode" type="text/x-jsrender"><select id="view-mode">
        		<option value="10">'. __('All', SAM_PRO_DOMAIN) .'</option>
        		<option value="0">' . __('Active', SAM_PRO_DOMAIN) . '</option>
        		<option value="1">' . __('In Trash', SAM_PRO_DOMAIN) . '</option>
    			</select></script>';
			?>
			<?php echo $ddList; ?>
			<script id="TrashPlace" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon place-to-trash"/>
			</script>
			<script id="UnTrashPlace" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon place-from-trash"/>
			</script>
			<script id="ModerateAd" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon ad-moderate"/>
			</script>
			<script id="UnModerateAd" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon ad-unmoderate"/>
			</script>
			<?php echo $newA ?>
			<div class="wrap">
				<h2><?php _e('Manage Ads', SAM_PRO_DOMAIN); ?></h2>
				<div id="grid"></div>
				<h4><?php _e('Legend', SAM_PRO_DOMAIN); ?></h4>
				<ul>
					<li><i class="icon-ok"></i>/<i class="icon-traffic-cone"></i> - <?php _e('Approved / Awaiting approval.', SAM_PRO_DOMAIN); ?></li>
					<li><i class="icon-leaf"></i>/<i class="icon-trash"></i> - <?php _e('Is active / Is in trash.', SAM_PRO_DOMAIN); ?></li>
					<li><i class="icon-eye"></i>/<i class="icon-eye-off"></i> - <?php _e('Potentially visible / Always invisible.', SAM_PRO_DOMAIN); ?></li>
				</ul>
			</div>
		<?php
			/*include_once('sam-pro-updater.php');
			$upd = new SamProUpdater(SAM_PRO_DB_VERSION, $this->settings);
			$struct = $upd->getTableStruct('wp_sampro_zones_rules', true);
			echo $struct;*/
		}
	}
}