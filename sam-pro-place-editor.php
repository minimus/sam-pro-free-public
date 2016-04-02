<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 24.02.2015
 * Time: 6:18
 */

if( ! class_exists( 'SamProPlaceEditor' ) ) {
	class SamProPlaceEditor {
		private $settings;
		private $item;
		private $update;
		private $updated = false;
		private $action = '';
		private $message = '';

		public function __construct( $options ) {
			$this->settings = $options;
			$this->update = (isset($_POST['update_item']));
			if ( isset( $_GET['item'] ) ) {
				$this->item = (integer) ( $_GET['item'] );
			} else {
				$this->item = 0;
			}
		}

		private function getItemData( $item ) {
			global $wpdb, $current_user;

			get_current_user();
			$pTable = $wpdb->prefix . 'sampro_places';
			$row = array();

			if($this->item > 0) {
				$sql = "SELECT * FROM {$pTable} WHERE pid = %d;";
				$row = $wpdb->get_row( $wpdb->prepare( $sql, $item ), ARRAY_A );
			}
			else {
				$row = apply_filters('sam_pro_admin_place_default_data', array(
					'pid' => '',
					'aid' => 0,
					'title' => '',
					'description' => '',
					'sale' => 0,
					'sale_mode' => 0,
					'price' => 0.00,
					'sdate' => date('Y-m-d H:i:s'),
					'fdate' => date('Y-m-d H:i:s'),
					'code_before' => '',
					'code_after' => '',
					'asize' => '',
					'width' => 0,
					'height' => 0,
					'img' => '',
					'link' => '',
					'alt' => '',
					'acode' => '',
					'php' => 0,
					'ad_server' => 0,
					'dfp' => '',
					'amode' => 0,
					'hits' => 0,
					'clicks' => 0,
					'trash' => 0
				));
			}

			return $row;
		}

		private function updateItemData( $id ) {
			global $wpdb;
			$pTable = $wpdb->prefix . 'sampro_places';

			include_once('sam-pro-sizes.php');
			$width = ((isset($_POST['width'])) ? $_POST['width'] : 0);
			$height = ((isset($_POST['height'])) ? $_POST['height'] : 0);
			$aSize = new SamProSizes($_POST['asize'], $width, $height);
			$out = $id;

			$updateRow = apply_filters('sam_pro_admin_place_save_data', array(
				'aid' => 0,
				'title' => stripslashes($_POST['item_name']),
				'description' => stripslashes($_POST['item_description']),
				'sale' => ((isset($_POST['sale'])) ? 1 : 0),
				'sale_mode' => ((isset($_POST['sale_mode'])) ? $_POST['sale_mode'] : 0),
				'price' => $_POST['price'],
				'sdate' => $_POST['sdate'],
				'fdate' => $_POST['fdate'],
				'code_before' => stripslashes($_POST['code_before']),
				'code_after' => stripslashes($_POST['code_after']),
				'asize' => $aSize->size,
				'width' => $aSize->width,
				'height' => $aSize->height,
				'img' => stripslashes($_POST['img']),
				'link' => stripslashes($_POST['link']),
				'alt' => stripslashes($_POST['alt']),
				'acode' => stripslashes($_POST['acode']),
				'php' => ((isset($_POST['php'])) ? 1 : 0),
				'ad_server' => ((isset($_POST['ad_server'])) ? 1 : 0),
				'dfp' => stripslashes($_POST['dfp']),
				'amode' => ((isset($_POST['amode'])) ? $_POST['amode'] : 0),
				'hits' => 0,
				'clicks' => 0,
				'trash' => ((isset($_POST['trash'])) ? $_POST['trash'] : 0)
			));

			$formatRow = apply_filters('sam_pro_admin_place_save_format', array(
				'%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', // aid - height
				'%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d'  // img - trash
			));

			if($id == 0) {
				$wpdb->insert($pTable, $updateRow, $formatRow);
				$out = $wpdb->insert_id;
			}
			else {
				$wpdb->update($pTable, $updateRow, array('pid' => $id), $formatRow, array('%d'));
			}
			$this->message = "<div class='updated'><p><strong>" . __("Ads Place Data Updated.", SAM_PRO_DOMAIN) . "</strong></p></div>";

			return $out;
		}

		public function show() {
			if ( $this->item == 0 ) {
				$header = __( 'New Place', SAM_PRO_DOMAIN );
			} else {
				$header = __( 'ID:', SAM_PRO_DOMAIN ) . $this->item;
			}

			if($this->update) {
				$oldItem = $this->item;
				$this->item = self::updateItemData($oldItem);
				if($oldItem != $this->item) $this->action = esc_url(add_query_arg(array('item' => $this->item)));
			}

			$row = self::getItemData($this->item);
			include_once('sam-pro-sizes.php');
			$apSize = new SamProSizes($row['asize'], $row['width'], $row['height']);
			?>
			<div class="wrap">
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
					<h1><?php echo __( 'Place Editor', SAM_PRO_DOMAIN ) . ' (' . $header . ')' ?></h1>
					<?php if(!empty($this->message)) echo $this->message; ?>
					<div class="metabox-holder has-right-sidebar" id="poststuff">
						<div id="side-info-column" class="inner-sidebar">
							<div class="meta-box-sortables ui-sortable">
								<div id="submitdiv" class="postbox ">
									<div class="handlediv" title="<?php _e('Click to toggle', SAM_PRO_DOMAIN); ?>"><br></div>
									<h3 class="hndle"><span><?php _e('Status', SAM_PRO_DOMAIN);?></span></h3>
									<div class="inside">
										<div id="submitpost" class="submitbox">
											<div id="minor-publishing">
												<div id="minor-publishing-actions">
													<div id="save-action"> </div>
													<div id="preview-action">
														<a id="back-button" class="button-secondary"
														   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-places'>
															<?php _e( 'Back to Places List', SAM_PRO_DOMAIN ) ?>
														</a>
													</div>
													<div class="clear"></div>
												</div>
												<div id="misc-publishing-actions">
													<div class="misc-pub-section">
														<label for="place_id_stat"><?php echo __('Ads Place ID', SAM_PRO_DOMAIN).':'; ?></label>
														<span id="place_id_stat" class="post-status-display"><?php echo $row['pid']; ?></span>
														<input type="hidden" id="place_id" name="place_id" value="<?php echo $this->item; ?>" >
													</div>
													<div class="misc-pub-section">
														<label for="place_size_info"><?php echo __('Size', SAM_PRO_DOMAIN).':'; ?></label>
														<span id="place_size_info" class="post-status-display"><?php echo $apSize->name; ?></span><br>
														<label for="place_width"><?php echo __('Width', SAM_PRO_DOMAIN).':'; ?></label>
														<span id="place_width" class="post-status-display"><?php echo $apSize->width; ?></span><br>
														<label for="place_height"><?php echo __('Height', SAM_PRO_DOMAIN).':'; ?></label>
														<span id="place_height" class="post-status-display"><?php echo $apSize->height; ?></span>
													</div>
													<div class="misc-pub-section">
														<label for="trash_no">
															<input type="radio" id="trash_no" value="0" name="trash" <?php checked(0, $row['trash']); ?>>&nbsp;
															<?php _e('Is Active', SAM_PRO_DOMAIN); ?>
														</label><br>
														<label for="trash_yes">
															<input type="radio" id="trash_yes" value="1" name="trash" <?php checked(1, $row['trash']); ?>>&nbsp;
															<?php _e('Is In Trash', SAM_PRO_DOMAIN); ?>
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
													   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-places'>
														<?php _e( 'Cancel', SAM_PRO_DOMAIN ) ?>
													</a>
													<button
														id="submit-button"
														class="button-primary"
														name="update_item"
														type="submit"<?php if(!empty($this->action)) echo " formaction='{$this->action}'" ?>>
														<?php _e( 'Save', SAM_PRO_DOMAIN ) ?>
													</button>
													<!--<input type="submit" class='button-primary' name="update_place" value="<?php _e('Save', SAM_PRO_DOMAIN) ?>" >-->
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
										       title="<?php echo __( 'Name of Ads Place', SAM_PRO_DOMAIN ) . '. ' . __( 'Required for SAM widgets.', SAM_PRO_DOMAIN ); ?>"
										       placeholder="<?php _e( 'Enter Ads Place Name Here', SAM_PRO_DOMAIN ); ?>">
									</div>
								</div>
								<div class="meta-box-sortables ui-sortable">
									<div id="descdiv" class="postbox ">
										<h3 class="hndle"><span><?php _e( 'Ads Place Description', SAM_PRO_DOMAIN ); ?></span></h3>
										<div class="inside">
											<p>
												<label for="item_description"><strong><?php echo __( 'Description', SAM_PRO_DOMAIN ) . ':' ?></strong></label>
												<textarea rows='3' id="item_description" class="code" tabindex="2" name="item_description"
												          style="width:100%; height: 100px;"><?php echo $row['description']; ?></textarea>
											</p>
											<p>
												<?php _e( 'If you want to sell this place, you have to describe it in details. Potential advertisers must know what they buy.', SAM_PRO_DOMAIN ); ?>
											</p>
										</div>
									</div>
								</div>
								<div id="tabs">
									<ul>
										<li><a href="#tabs-1"><?php _e( 'General', SAM_PRO_DOMAIN ); ?></a></li>
										<li><a href="#tabs-2"><?php _e( 'Default Ad', SAM_PRO_DOMAIN ); ?></a></li>
										<li><a href="#tabs-3"><?php _e( 'Selling', SAM_PRO_DOMAIN ); ?></a></li>
										<?php do_action('sam_pro_admin_place_editor_tabs'); ?>
									</ul>
									<div id="tabs-1">
										<div class="meta-box-sortables ui-sortable">
											<div id="sizediv" class="postbox ">
												<h3 class="hndle"><?php _e('Ads Place Size', SAM_PRO_DOMAIN); ?></h3>
												<div class="inside">
													<p><?php _e('Select size of this Ads Place.', SAM_PRO_DOMAIN); ?></p>
													<?php $apSize->drawList(); ?>
													<p>
														<label for="width"><?php echo __('Width', SAM_PRO_DOMAIN) . ':  '; ?></label>
														<input id="width" name="width" type="number" value="<?php echo $row['width']; ?>">
													</p>
													<p>
														<label for="height"><?php echo __('Height', SAM_PRO_DOMAIN) . ': '; ?></label>
														<input id="height" name="height" type="number" value="<?php echo $row['height']; ?>">
													</p>
												</div>
											</div>
										</div>
										<div class="meta-box-sortables ui-sortable">
											<div id="codesdiv" class="postbox ">
												<h3 class="hndle"><?php _e('Codes', SAM_PRO_DOMAIN); ?></h3>
												<div class="inside">
													<p><?php _e('Here you can to define leading and trailing parts of tag that you want to place around Ads Place tags.', SAM_PRO_DOMAIN); ?></p>
													<p>
														<label for="code_before"><?php _e('Code Before', SAM_PRO_DOMAIN); ?></label>
														<input id="code_before" name="code_before" class="code" type="text" value="<?php echo htmlspecialchars(stripslashes($row['code_before'])); ?>" style="width: 100%;">
													</p>
													<p>
														<label for="code_after"><?php _e('Code After', SAM_PRO_DOMAIN); ?></label>
														<input id="code_after" name="code_after" class="code" type="text" value="<?php echo htmlspecialchars(stripslashes($row['code_after'])); ?>" style="width: 100%;">
													</p>
													<p>
														<button id="sam-pro-center" class="button-secondary"><?php _e('Center', SAM_PRO_DOMAIN); ?></button>&nbsp;
														<button id="sam-pro-left" class="button-secondary"><?php _e('Float Left', SAM_PRO_DOMAIN); ?></button>&nbsp;
														<button id="sam-pro-right" class="button-secondary"><?php _e('Float Right', SAM_PRO_DOMAIN); ?></button>
													</p>
													<p><?php _e('You can enter any HTML codes here for the further placing of their before and after the code of the Ads Place.', SAM_PRO_DOMAIN); ?></p>
												</div>
											</div>
										</div>
										<?php do_action('sam_pro_admin_place_editor_tabs_general', $row); ?>
									</div>
									<div id="tabs-2">
										<div class="meta-box-sortables ui-sortable">
											<div id="codediv" class="postbox ">
												<h3 class="hndle"><?php _e('Default Ad') ?></h3>
												<div class="inside">
													<p><?php _e('Select type of the code for the Default Ad and fill data entry fields with the appropriate data.', SAM_PRO_DOMAIN); ?></p>
													<p>
														<label for="amode_img">
															<input id="amode_img" name="amode" type="radio" value="0" <?php checked(0, $row['amode']); ?>><?php _e('Image', SAM_PRO_DOMAIN); ?>
														</label>
													</p>
													<div class="sub-content" id="rc-ami"<?php if($row['amode'] != 0) echo ' style="display: none;"'; ?>>
														<p>
															<label for="img"><?php echo __('Image', SAM_PRO_DOMAIN) . ':'; ?></label><br>
															<input id="img" name="img" type="url" value="<?php echo $row['img'] ?>" style="width: 85%;">&nbsp;
															<button id="banner-media" class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ) ?></button>
															<input id="img_id" name="img_id" type="hidden" value="<?php echo 438; ?>">
														</p>
														<p>
															<label for="link"><?php echo __('Target', SAM_PRO_DOMAIN) . ':'; ?></label><br>
															<input id="link" name="link" type="url" value="<?php echo $row['link'] ?>" style="width: 100%;">
														</p>
														<p>
															<label for="alt"><?php echo __('Alternative Text', SAM_PRO_DOMAIN) . ':'; ?></label><br>
															<input id="alt" name="alt" type="text" value="<?php echo $row['alt'] ?>" style="width: 100%;">
														</p>
													</div>
													<p>
														<label for="amode_code">
															<input id="amode_code" name="amode" type="radio" value="1" <?php checked(1, $row['amode']); ?>><?php _e('Code', SAM_PRO_DOMAIN); ?>
														</label>
													</p>
													<div class="sub-content" id="rc-amc"<?php if($row['amode'] != 1) echo ' style="display: none;"'; ?>>
														<p>
															<label for="acode"><?php echo __('Default Ad Code', SAM_PRO_DOMAIN).':'; ?></label>
															<textarea id="acode" class="code" rows='10' name="acode" style="width:100%" ><?php echo htmlspecialchars(stripslashes($row['acode'])); ?></textarea>
															<input type='checkbox' name='php' id='php' value='1' <?php checked( 1, $row['php'] ); ?>>
															<label for='php' style='vertical-align: middle;'> <?php _e( 'This code of ad contains PHP script', SAM_PRO_DOMAIN ); ?></label><br>
															<input type='checkbox' name='ad_server' id='ad_server' value='1' <?php checked(1, $row['ad_server']); ?>>
															<label for='ad_server'><?php _e('This is one-block code of third-party AdServer rotator. Selecting this checkbox prevents displaying contained ads.', SAM_PRO_DOMAIN); ?></label>
														</p>
													</div>
													<p>
														<label for="amode_dfp">
															<input id="amode_dfp" name="amode" type="radio" value="2" <?php checked(2, $row['amode']); ?>><?php _e('Google DFP', SAM_PRO_DOMAIN); ?>
														</label>
													</p>
													<div class="sub-content" id="rc-amd"<?php if($row['amode'] != 2) echo ' style="display: none;"'; ?>>
														<p>
															<label for="dfp"><?php echo __('DFP Block Name', SAM_PRO_DOMAIN) . ':'; ?></label>
															<input id="dfp" name="dfp" type="text" value="<?php echo $row['dfp']; ?>">
														</p>
														<p><?php _e('This is name of Google DFP block!', SAM_PRO_DOMAIN); ?></p>
													</div>
													<p><?php _e('The default ad will be shown only if the logic of the plugin can not allow to show any of the contained ads on the current page of the document.', SAM_PRO_DOMAIN); ?></p>
												</div>
											</div>
										</div>
										<?php do_action('sam_pro_admin_place_editor_tabs_default_ad', $row); ?>
									</div>
									<div id="tabs-3">
										<div class="meta-box-sortables ui-sortable">
											<div id="codediv" class="postbox ">
												<h3 class="hndle"><?php _e('Selling Parameters', SAM_PRO_DOMAIN); ?></h3>
												<div class="inside">
													<p>
														<input id="sale" name="sale" type="checkbox" value="<?php echo $row['sale']; ?>" <?php checked(1, $row['sale']); ?>>
														<label for="sale"><?php _e('For Sale', SAM_PRO_DOMAIN); ?></label>
													</p>
													<div id="rc-sale" class="sub-content"<?php if($row['sale'] == 0) echo ' style="display: none;"'; ?>>
														<label for="sale_mode_0">
															<input id="sale_mode_0" name="sale_mode" type="radio" value="0" <?php checked(0, $row['sale_mode']); ?>>
															<?php _e('Space', SAM_PRO_DOMAIN); ?>
														</label>&nbsp;&nbsp;
														<label for="sale_mode_1">
															<input id="sale_mode_1" name="sale_mode" type="radio" value="1" <?php checked(1, $row['sale_mode']); ?>>
															<?php _e('Each Ad', SAM_PRO_DOMAIN); ?>
														</label>
														<ul>
															<li><?php _e('<strong>Space</strong> - You sale space of Ads Place. Advertiser can place here so much ads as he wants.', SAM_PRO_DOMAIN); ?></li>
															<li><?php _e('<strong>Each Ad</strong> - You sell each ad individually. The advertiser pays for each ad that appears in this advertising space.', SAM_PRO_DOMAIN); ?></li>
														</ul>
														<p>
															<label for="price"><?php _e('Selling Price', SAM_PRO_DOMAIN); ?>:</label>
															<input id="price" name="price" type="number" value="<?php echo $row['price']; ?>">
														</p>
														<div id="rc-sale-mode" class="radio-content" <?php if((integer)$row['sale_mode'] != 0) echo 'style="display:none;"'; ?>>
															<p>
																<label for='startDate'><?php echo __('Campaign Start Date', SAM_PRO_DOMAIN).':' ?></label>
																<input type="text" id="startDate">
																<input type='hidden' name='sdate' id='sdate' value='<?php echo $row['sdate']; ?>'>
															</p>
															<p>
																<label for='finishDate'><?php echo __('Campaign End Date', SAM_PRO_DOMAIN).':' ?></label>
																<input id="finishDate" type="text">
																<input type='hidden' name='fdate' id='fdate' value='<?php echo $row['fdate']; ?>'>
															</p>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php do_action('sam_pro_admin_place_editor_tabs_selling', $row); ?>
									</div>
									<?php do_action('sam_pro_admin_place_editor_tabs_body', $row); ?>
								</div>
								<div class="meta-box-sortables ui-sortable" style="margin-top: 20px;<?php if($this->item == 0) echo ' display: none;'; ?>">
									<div id="griddiv" class="postbox ">
										<h3 class="hndle"><?php _e('Linked Ads', SAM_PRO_DOMAIN); ?></h3>
										<div class="inside">
											<p><?php _e('These are the ads linked to this Ads Place. You can link any number of ads to this Ads Place, unlink any ad and edit weight of each ad.', SAM_PRO_DOMAIN); ?></p>
											<div id="grid"></div>
											<h4><?php _e('Legend', SAM_PRO_DOMAIN); ?></h4>
											<ul>
												<li><i class="icon-ok"></i>/<i class="icon-traffic-cone"></i> - <?php _e('Approved / Awaiting approval.', SAM_PRO_DOMAIN); ?></li>
												<li><i class="icon-leaf"></i>/<i class="icon-trash"></i> - <?php _e('Is active / Is in trash.', SAM_PRO_DOMAIN); ?></li>
												<li><i class="icon-eye"></i>/<i class="icon-eye-off"></i> - <?php _e('Potentially visible / Always invisible.', SAM_PRO_DOMAIN); ?></li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
				<?php if($this->item > 0) { ?>
				<div id="adsDialog" title="<?php _e('Select Ads', SAM_PRO_DOMAIN); ?>">
					<div id="adsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-ads" value="<?php _e('Select', SAM_PRO_DOMAIN); ?>" class="button-secondary">&nbsp;
						<input type="button" id="cancel-ads" value="<?php _e('Cancel', SAM_PRO_DOMAIN); ?>" class="button-secondary cancel-dialog">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php
		}
	}
}