<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 14.03.2015
 * Time: 6:13
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists( 'SamProBlocksList' ) ) {
	class SamProBlocksList {
		private $settings;

		public function __construct( $options ) {
			$this->settings = $options;
		}

		public function show() {
			$newLink = admin_url('admin.php') . '?page=sam-pro-block-editor';
			$newB = "<script id='NewBlock' type='text/x-jsrender'><a class='e-toolbaricons e-icon new-block' href='{$newLink}'/></script>";
			$ddList = '<script id="viewMode" type="text/x-jsrender"><select id="view-mode">
        		<option value="10">'. __('All', SAM_PRO_DOMAIN) .'</option>
        		<option value="0">' . __('Active', SAM_PRO_DOMAIN) . '</option>
        		<option value="1">' . __('In Trash', SAM_PRO_DOMAIN) . '</option>
    			</select></script>';
			?>
			<?php echo $ddList; ?>
			<script id="TrashBlock" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon block-to-trash"/>
			</script>
			<script id="UnTrashBlock" type="text/x-jsrender">
       	<a class="e-toolbaricons e-icon block-from-trash"/>
			</script>
			<?php echo $newB ?>
			<div class="wrap">
				<h2><?php _e('Manage Blocks', SAM_PRO_DOMAIN); ?></h2>
				<div id="grid"></div>
				<h4><?php _e('Legend', SAM_PRO_DOMAIN); ?></h4>
				<ul>
					<li><i class="icon-leaf"></i>/<i class="icon-trash"></i> - <?php _e('Is active / Is in trash.', SAM_PRO_DOMAIN); ?></li>
				</ul>
			</div>
		<?php
		}
	}
}