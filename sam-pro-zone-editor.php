<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 09.03.2015
 * Time: 10:56
 */

if( ! class_exists( 'SamProZoneEditor' ) ) {
	class SamProZoneEditor {
		private $settings;
		private $item;
		private $update;
		private $action = '';
		private $message = '';

		public function __construct( $options ) {
			$this->settings = $options;
			$this->update = (isset($_POST['update_item']));
			if ( isset( $_GET['item'] ) ) $this->item = (integer) ( $_GET['item'] );
			else $this->item = 0;
		}

		private function getItemData( $id ) {
			global $wpdb;
			$zTable = $wpdb->prefix . 'sampro_zones';

			if($id > 0) {
				$sql = "SELECT * FROM {$zTable} sz WHERE sz.zid = %d;";
				$row = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
			}
			else {
				$row = apply_filters('sam_pro_admin_zone_default_data', array(
					'zid' => 0,
					'title' => '',
					'description' => '',
					'single_id' => 0,
					'arc_id' => 0,
					'trash' => 0
				));
			}

			return $row;
		}

		private function updateItemData( $id ) {
			global $wpdb;
			$zTable = $wpdb->prefix . 'sampro_zones';
			$out = $id;

			$updateRow = apply_filters('sam_pro_admin_zone_save_data', array(
				'title' => (isset($_POST['item_name'])) ? $_POST['item_name'] : '',
				'description' => (isset($_POST['item_description'])) ? $_POST['item_description'] : '',
				'single_id' => (isset($_POST['single_id'])) ? $_POST['single_id'] : 0,
				'arc_id' => (isset($_POST['arc_id'])) ? $_POST['arc_id'] : 0,
				'trash' => (isset($_POST['trash'])) ? $_POST['trash'] : 0
			));
			$formatRow = apply_filters('sam_pro_admin_zone_save_format', array('%s', '%s', '%d', '%d', '%d'));
			if($id == 0) {
				$wpdb->insert($zTable, $updateRow, $formatRow);
				$out = $wpdb->insert_id;
			}
			else {
				$wpdb->update($zTable, $updateRow, array('zid' => $id), $formatRow, array('%d'));
			}
			$this->message = "<div class='updated'><p><strong>" . __("Zone Data Updated.", SAM_PRO_DOMAIN) . "</strong></p></div>";

			return $out;
		}

		private function getPlaces() {
			global $wpdb;
			$pTable = $wpdb->prefix . 'sampro_places';
			$sql = "SELECT sp.pid, sp.title FROM {$pTable} sp;";
			$places = $wpdb->get_results($sql, ARRAY_A);
			$out = array_merge(array(array('pid' => 0, 'title' => __('None', SAM_PRO_DOMAIN))) , $places);

			return $out;
		}

		public function show() {
			global $wp_post_types;

			if ( $this->item == 0 ) {
				$header = __( 'New Zone', SAM_PRO_DOMAIN );
			} else {
				$header = __( 'ID:', SAM_PRO_DOMAIN ) . $this->item;
			}
			$this->action = $_SERVER['REQUEST_URI'];

			$places = self::getPlaces();

			if($this->update) {
				$oldItem = $this->item;
				$this->item = self::updateItemData($oldItem);
				$this->action = ($oldItem != $this->item) ?
					esc_url(add_query_arg(array('item' => $this->item), $_SERVER['REQUEST_URI'])) :
					$_SERVER['REQUEST_URI'];
			}

			$row = self::getItemData($this->item);
			?>
			<div class="wrap">
				<form method="post" action="<?php echo $this->action; ?>">
					<h1><?php echo __( 'Zone Editor', SAM_PRO_DOMAIN ) . ' (' . $header . ')' ?></h1>
					<?php if(!empty($this->message)) echo $this->message; ?>
					<div class="metabox-holder has-right-sidebar" id="poststuff">
						<div id="side-info-column" class="inner-sidebar">
							<div class="meta-box-sortables ui-sortable">
								<div id="submitdiv" class="postbox ">
									<h3 class="hndle"><span><?php _e('Status', SAM_PRO_DOMAIN);?></span></h3>
									<div class="inside">
										<div id="submitpost" class="submitbox">
											<div id="minor-publishing">
												<div id="minor-publishing-actions">
													<div id="save-action"> </div>
													<div id="preview-action">
														<a id="back-button" class="button-secondary"
														   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-zones'>
															<?php _e( 'Back to Zones List', SAM_PRO_DOMAIN ) ?>
														</a>
													</div>
													<div class="clear"></div>
												</div>
												<div id="misc-publishing-actions">
													<div class="misc-pub-section">
														<label for="zone_id_stat"><?php echo __('Zone ID', SAM_PRO_DOMAIN).':'; ?></label>
														<span id="zone_id_stat" class="post-status-display"><?php echo $row['zid']; ?></span>
														<input type="hidden" id="zone_id" name="zone_id" value="<?php echo $this->item; ?>" >
													</div>
													<div class="misc-pub-section">
														<label for="trash_no">
															<input type="checkbox" id="trash" value="1" name="trash" <?php checked(1, $row['trash']); ?>>
															<?php _e('Is in trash', SAM_PRO_DOMAIN); ?>
														</label>
													</div>
												</div>
												<div class="clear"></div>
											</div>
											<div id="major-publishing-actions">
												<div id="delete-action">
													<!--<a class="submitdelete deletion" href='<?php echo admin_url('admin.php'); ?>?page=sam-list'><?php _e('Cancel', SAM_PRO_DOMAIN) ?></a>-->
												</div>
												<div id="publishing-action">
													<a id="cancel-button" class="button-secondary"
													   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-zones'>
														<?php _e( 'Cancel', SAM_PRO_DOMAIN ) ?>
													</a>
													<button
														id="submit-button"
														class="button-primary"
														name="update_item"
														type="submit"<?php if(!empty($this->action)) echo " formaction='{$this->action}'" ?>>
														<?php _e( 'Save', SAM_PRO_DOMAIN ) ?>
													</button>
												</div>
												<div class="clear"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="post-body">
							<div id="post-body-content">
								<div id="titlediv">
									<div id="titlewrap">
										<label class="screen-reader-text" for="title"><?php _e( 'Title', SAM_PRO_DOMAIN ); ?></label>
										<input id="title" type="text" autocomplete="off" tabindex="1" size="30" name="item_name"
										       value="<?php echo $row['title']; ?>"
										       title="<?php echo __( 'Name of Ads Zone', SAM_PRO_DOMAIN ) . '. ' . __( 'Required for SAM widgets.', SAM_PRO_DOMAIN ); ?>"
										       placeholder="<?php _e( 'Enter Ads Zone Name Here', SAM_PRO_DOMAIN ); ?>">
									</div>
								</div>
								<div class="meta-box-sortables ui-sortable">
									<div id="descdiv" class="postbox ">
										<h3 class="hndle"><span><?php _e( 'Ads Zone Description', SAM_PRO_DOMAIN ); ?></span></h3>
										<div class="inside">
											<p>
												<label for="item_description"><strong><?php echo __( 'Description', SAM_PRO_DOMAIN ) . ':' ?></strong></label>
												<textarea rows='3' id="item_description" class="code" tabindex="2" name="item_description"
												          style="width:100%; height: 100px;"><?php echo $row['description']; ?></textarea>
											</p>
											<p>
												<?php _e( 'This description is not used anywhere and is added solely for the convenience of managing advertisements.', SAM_PRO_DOMAIN ); ?>
											</p>
										</div>
									</div>
								</div>
								<div class="meta-box-sortables ui-sortable">
									<div id="arcdiv" class="postbox">
										<h3 class="hndle"><span><?php _e('Rules', SAM_PRO_DOMAIN); ?></span></h3>
										<div class="inside">
											<p>
												<label for="arc_id"><?php _e('Default Place', SAM_PRO_DOMAIN); ?>: </label>
												<select id="arc_id" name="arc_id">
													<?php
													foreach($places as $place) {
														$sel = selected((int)$place['pid'], (int)$row['arc_id'], false);
														echo "<option value='{$place['pid']}' {$sel}>{$place['title']}</option>";
													}
													?>
												</select>
											</p>
											<?php if($this->item > 0) { ?>
												<div id='aGrid'></div>
											<?php } ?>
										</div>
									</div>
								</div>
								<div class="meta-box-sortables ui-sortable">
									<div id="singlediv" class="postbox">
										<h3 class="hndle"><span><?php _e('Singular Page Rules', SAM_PRO_DOMAIN); ?></span></h3>
										<div class="inside">
											<p>
												<label for="single_id"><?php _e('Default Place', SAM_PRO_DOMAIN); ?>: </label>
												<select id="single_id" name="single_id">
													<?php
													foreach($places as $place) {
														$sel = selected((int)$place['pid'], (int)$row['single_id'], false);
														echo "<option value='{$place['pid']}' {$sel}>{$place['title']}</option>";
													}
													?>
												</select>
											</p>
											<?php if($this->item > 0) echo "<div id='sGrid'></div>"; ?>
										</div>
									</div>
								</div>
							</div>
							<?php do_action('sam_pro_admin_zone_editor', $row); ?>
						</div>
					</div>
				</form>
				<?php if($this->item > 0) { ?>
				<div id="rulesDialog" title="<?php _e('Select Rules', SAM_PRO_DOMAIN); ?>">
					<div id="rulesGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-rules" value="<?php _e('Select', SAM_PRO_DOMAIN); ?>" class="button-secondary">&nbsp;
						<input type="button" id="cancel-rules" value="<?php _e('Cancel', SAM_PRO_DOMAIN); ?>" class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="singleDialog" title="<?php _e('Select Rules for Singular Pages', SAM_PRO_DOMAIN); ?>">
					<div id="singleGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-single" value="<?php _e('Select', SAM_PRO_DOMAIN); ?>" class="button-secondary">&nbsp;
						<input type="button" id="cancel-single" value="<?php _e('Cancel', SAM_PRO_DOMAIN); ?>" class="button-secondary cancel-dialog">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php
		}
	}
}