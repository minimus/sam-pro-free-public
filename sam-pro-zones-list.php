<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 09.03.2015
 * Time: 8:10
 */

if( ! class_exists( 'SamProZonesList' ) ) {
	class SamProZonesList {
		private $settings;

		public function __construct( $options ) {
			$this->settings = $options;
		}

		public function show() {
			$newLink = admin_url('admin.php') . '?page=sam-pro-zone-editor';
			$newZ = "<script id='NewZone' type='text/x-jsrender'><a class='e-toolbaricons e-icon new-zone' href='{$newLink}'/></script>";
			$ddList = '<script id="viewMode" type="text/x-jsrender"><select id="view-mode">
        		<option value="10">'. __('All', SAM_PRO_DOMAIN) .'</option>
        		<option value="0">' . __('Active', SAM_PRO_DOMAIN) . '</option>
        		<option value="1">' . __('In Trash', SAM_PRO_DOMAIN) . '</option>
    			</select></script>';
			?>
			<?php echo $ddList; ?>
			<script id="TrashZone" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon zone-to-trash"/>
			</script>
			<script id="UnTrashZone" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon zone-from-trash"/>
			</script>
			<?php echo $newZ ?>
			<div class="wrap">
				<h2><?php _e('Manage Zones', SAM_PRO_DOMAIN); ?></h2>
				<div id="grid"></div>
				<h4>Legend</h4>
				<ul>
					<li><i class="icon-leaf"></i>/<i class="icon-trash"></i> - <?php _e('Is active / Is in trash.', SAM_PRO_DOMAIN); ?></li>
				</ul>
			</div>
			<?php
		}
	}
}