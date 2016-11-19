<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 31.12.2014
 * Time: 3:24
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProAdEditor' ) ) {
	class SamProAdEditor {
		private $settings;
		private $new;
		private $update;
		private $item;
		private $action;
		private $message = '';

		public function __construct( $options ) {
			$this->settings = $options;
			$this->update   = ( isset( $_POST['update_item'] ) );
			if ( isset( $_GET['item'] ) ) {
				$this->item = (integer) ( $_GET['item'] );
			} else {
				$this->item = 0;
			}
		}

		private function getAdInfo( $item ) {
			global $wpdb;
			$sTable = $wpdb->prefix . 'sampro_stats';
			$sql    = "SELECT SUM(wss.hits) AS hits, SUM(wss.clicks) AS clicks
FROM {$sTable} wss
WHERE wss.aid = {$item} AND EXTRACT(YEAR_MONTH FROM wss.edate) = EXTRACT(YEAR_MONTH FROM NOW())
GROUP BY wss.pid, wss.aid;";
			$info   = $wpdb->get_row( $sql, ARRAY_A );

			if ( $info != null ) {
				return $info;
			} else {
				return array( 'hits' => 0, 'clicks' => 0 );
			}
		}

		private function getAdData( $item ) {
			global $wpdb, $current_user;

			get_current_user();
			$aTable = $wpdb->prefix . 'sampro_ads';
			$row    = array();

			if ( $item > 0 ) {
				$sql = "SELECT * FROM {$aTable} WHERE aid = %d;";
				$row = $wpdb->get_row( $wpdb->prepare( $sql, $item ), ARRAY_A );
			} else {
				$row = apply_filters( 'sam_pro_admin_ad_default_data', array(
					'aid'           => '',
					'title'         => '',
					'description'   => '',
					'moderated'     => ( ( current_user_can( 'manage_options' ) ) ? 1 : 0 ),
					'img'           => '',
					'link'          => '',
					'alt'           => '',
					'swf'           => 0,
					'swf_vars'      => '',
					'swf_params'    => '',
					'swf_attrs'     => '',
					'swf_fallback'  => '',
					'acode'         => '',
					'php'           => 0,
					'inline'        => 0,
					'amode'         => 0,
					'hits'          => 0,
					'clicks'        => 0,
					'rel'           => 0,
					'asize'         => '',
					'width'         => 0,
					'height'        => 0,
					'price'         => 0.00,
					'ptype'         => 0,
					'ptypes'        => 0,
					'eposts'        => 0,
					'xposts'        => 0,
					'posts'         => '',
					'ecats'         => 0,
					'xcats'         => 0,
					'cats'          => '',
					'etags'         => 0,
					'xtags'         => 0,
					'tags'          => '',
					'eauthors'      => 0,
					'xauthors'      => 0,
					'authors'       => '',
					'etax'          => 0,
					'xtax'          => 0,
					'taxes'         => '',
					'etypes'        => 0,
					'xtypes'        => 0,
					'types'         => '',
					'schedule'      => 0,
					'sdate'         => '',
					'fdate'         => '',
					'limit_hits'    => 0,
					'hits_limit'    => 0,
					'hits_period'   => 0,
					'limit_clicks'  => 0,
					'clicks_limit'  => 0,
					'clicks_period' => 0,
					'users'         => 0,
					'users_unreg'   => 0,
					'users_reg'     => 0,
					'xusers'        => 0,
					'xvusers'       => '',
					'advertiser'    => 0,
					'geo'           => 0,
					'xgeo'          => 0,
					'geo_country'   => '',
					'geo_region'    => '',
					'geo_city'      => '',
					'owner'         => $current_user->user_login,
					'owner_name'    => $current_user->display_name,
					'owner_mail'    => $current_user->user_email,
					'ppm'           => 0.00,
					'ppc'           => 0.00,
					'ppi'           => 0.00,
					'trash'         => 0
				) );
			}

			return $row;
		}

		private function updateItemData( $id ) {
			global $wpdb;
			$aTable = $wpdb->prefix . 'sampro_ads';

			include_once( 'sam-pro-sizes.php' );
			$width  = ( ( isset( $_POST['width'] ) ) ? (int) $_POST['width'] : 0 );
			$height = ( ( isset( $_POST['height'] ) ) ? (int) $_POST['height'] : 0 );
			$aSize  = new SamProSizes( sanitize_text_field( $_POST['asize'] ), $width, $height );
			$out    = $id;

			$updateRow = apply_filters( 'sam_pro_admin_ad_save_data', array(
				//'aid'         => '',
				'title'        => stripslashes( sanitize_text_field( $_POST['item_name'] ) ),
				'description'  => stripslashes( sanitize_text_field( $_POST['item_description'] ) ),
				'moderated'    => ( ( isset( $_POST['moderated'] ) ) ? 1 : 0 ),
				'img'          => stripslashes( esc_url( $_POST['img'] ) ),
				'link'         => stripslashes( esc_url_raw( $_POST['link'] ) ),
				'alt'          => stripslashes( sanitize_text_field( $_POST['alt'] ) ),
				'swf'          => ( ( isset( $_POST['swf'] ) ) ? 1 : 0 ),
				'swf_vars'     => stripslashes( sanitize_text_field( $_POST['swf_vars'] ) ),
				'swf_params'   => stripslashes( sanitize_text_field( $_POST['swf_params'] ) ),
				'swf_attrs'    => stripslashes( sanitize_text_field( $_POST['swf_attrs'] ) ),
				'swf_fallback' => stripslashes( sanitize_text_field( $_POST['swf_fallback'] ) ),
				'acode'        => stripslashes( $_POST['acode'] ),
				'php'          => ( ( isset( $_POST['php'] ) ) ? 1 : 0 ),
				'inline'       => ( ( isset( $_POST['inline'] ) ) ? 1 : 0 ),
				'amode'        => ( ( isset( $_POST['amode'] ) ) ? (int) $_POST['amode'] : 0 ),
				'hits'         => 0,
				'clicks'       => ( ( isset( $_POST['clicks'] ) ) ? 1 : 0 ),
				'rel'          => ( ( isset( $_POST['rel'] ) ) ? (int) $_POST['rel'] : 0 ),
				'asize'        => $aSize->size,
				'width'        => $aSize->width,
				'height'       => $aSize->height,
				'price'        => ( ( isset( $_POST['price'] ) ) ? (int) $_POST['price'] : 0 ),
				'ptype'        => ( ( isset( $_POST['ptype'] ) ) ? (int) $_POST['ptype'] : 0 ),
				'ptypes'       => ( ( isset( $_POST['ptypes'] ) ) ? (int) $_POST['ptypes'] : 0 ),
				'eposts'       => ( ( isset( $_POST['eposts'] ) ) ? 1 : 0 ),
				'xposts'       => ( ( isset( $_POST['xposts'] ) ) ? (int) $_POST['xposts'] : 0 ),
				'posts'        => stripslashes( sanitize_text_field( $_POST['posts'] ) ),
				'ecats'        => ( ( isset( $_POST['ecats'] ) ) ? 1 : 0 ),
				'xcats'        => ( ( isset( $_POST['xcats'] ) ) ? (int) $_POST['xcats'] : 0 ),
				'cats'         => stripslashes( sanitize_text_field( $_POST['cats'] ) ),
				'etags'        => ( ( isset( $_POST['etags'] ) ) ? 1 : 0 ),
				'xtags'        => ( ( isset( $_POST['xtags'] ) ) ? (int) $_POST['xtags'] : 0 ),
				'tags'         => stripslashes( sanitize_text_field( $_POST['tags'] ) ),
				'eauthors'     => ( ( isset( $_POST['eauthors'] ) ) ? 1 : 0 ),
				'xauthors'     => ( ( isset( $_POST['xauthors'] ) ) ? (int) $_POST['xauthors'] : 0 ),
				'authors'      => stripslashes( sanitize_text_field( $_POST['authors'] ) ),
				'etax'         => ( ( isset( $_POST['etax'] ) ) ? 1 : 0 ),
				'xtax'         => ( ( isset( $_POST['xtax'] ) ) ? (int) $_POST['xtax'] : 0 ),
				'taxes'        => stripslashes( sanitize_text_field( $_POST['taxes'] ) ),
				'etypes'       => ( ( isset( $_POST['etypes'] ) ) ? 1 : 0 ),
				'xtypes'       => ( ( isset( $_POST['xtypes'] ) ) ? (int) $_POST['xtypes'] : 0 ),
				'types'        => stripslashes( sanitize_text_field( $_POST['types'] ) ),
				'schedule'     => ( ( isset( $_POST['schedule'] ) ) ? 1 : 0 ),
				'sdate'        => sanitize_text_field( $_POST['sdate'] ),
				'fdate'        => sanitize_text_field( $_POST['fdate'] ),
				'limit_hits'   => ( ( isset( $_POST['limit_hits'] ) ) ? 1 : 0 ),
				'hits_limit'   => ( ( isset( $_POST['hits_limit'] ) ) ? (int) $_POST['hits_limit'] : 0 ),
				'limit_clicks' => ( ( isset( $_POST['limit_clicks'] ) ) ? 1 : 0 ),
				'clicks_limit' => ( ( isset( $_POST['clicks_limit'] ) ) ? (int) $_POST['clicks_limit'] : 0 ),
				'users'        => ( ( isset( $_POST['users'] ) ) ? (int) $_POST['users'] : 0 ),
				'users_unreg'  => ( ( isset( $_POST['users_unreg'] ) ) ? 1 : 0 ),
				'users_reg'    => ( ( isset( $_POST['users_reg'] ) ) ? 1 : 0 ),
				'xusers'       => ( ( isset( $_POST['xusers'] ) ) ? 1 : 0 ),
				'xvusers'      => stripslashes( sanitize_text_field( $_POST['xvusers'] ) ),
				'advertiser'   => ( ( isset( $_POST['advertiser'] ) ) ? 1 : 0 ),
				'geo'          => ( ( isset( $_POST['geo'] ) ) ? 1 : 0 ),
				'xgeo'         => ( ( isset( $_POST['xgeo'] ) ) ? (int) $_POST['xgeo'] : 0 ),
				'geo_country'  => ( ( isset( $_POST['geo_country'] ) ) ? stripslashes( sanitize_text_field( $_POST['geo_country'] ) ) : '' ),
				'geo_region'   => ( ( isset( $_POST['geo_region'] ) ) ? stripslashes( sanitize_text_field( $_POST['geo_region'] ) ) : '' ),
				'geo_city'     => ( ( isset( $_POST['geo_city'] ) ) ? stripslashes( sanitize_text_field( $_POST['geo_city'] ) ) : '' ),
				'owner'        => stripslashes( sanitize_text_field( $_POST['owner'] ) ),
				'owner_name'   => stripslashes( sanitize_text_field( $_POST['owner_name'] ) ),
				'owner_mail'   => stripslashes( sanitize_text_field( $_POST['owner_mail'] ) ),
				'ppm'          => 0.00,
				'ppc'          => 0.00,
				'ppi'          => 0.00,
				'trash'        => ( ( isset( $_POST['trash'] ) ) ? 1 : 0 )
			) );

			$formatRow = apply_filters( 'sam_pro_admin_ad_save_format', array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',                   // title - acode
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',                               // php - price
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',       // ptype - authors
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',             // etax - clicks_limit
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',       // users - owner_mail
				'%d',
				'%d',
				'%d',
				'%d'                                                                    // ppm - trash
			) );

			if ( $id == 0 ) {
				$wpdb->insert( $aTable, $updateRow, $formatRow );
				$out = $wpdb->insert_id;
			} else {
				$wpdb->update( $aTable, $updateRow, array( 'aid' => $id ), $formatRow, array( '%d' ) );
			}
			$this->message = "<div class='updated'><p><strong>" . __( "Ad Data Updated.", SAM_PRO_DOMAIN ) . "</strong></p></div>";

			return $out;
		}

		private function checkViewPages( $data, $id ) {
			return 0;
		}

		private function checkTable() {
			global $wpdb;
			$rTable = $wpdb->prefix . 'sampro_regions';
			$out    = ( $wpdb->get_var( "SHOW TABLES LIKE '{$rTable}'" ) == $rTable );

			return $out;
		}

		public function show() {
			$help = __( "{name1:value1, name2:value2, ...}", SAM_PRO_DOMAIN );
			if ( $this->item == 0 ) {
				$header = __( 'New Ad', SAM_PRO_DOMAIN );
			} else {
				$header = __( 'ID:', SAM_PRO_DOMAIN ) . $this->item;
			}
			$this->action = $_SERVER['REQUEST_URI'];

			if ( $this->update ) {
				$oldItem      = $this->item;
				$this->item   = self::updateItemData( $oldItem );
				$this->action = ( $oldItem != $this->item ) ?
					esc_url( add_query_arg( array( 'item' => $this->item ), $_SERVER['REQUEST_URI'] ) ) :
					$_SERVER['REQUEST_URI'];
			}

			$row = self::getAdData( $this->item );
			include_once( 'sam-pro-sizes.php' );
			$apSize = new SamProSizes( $row['asize'], $row['width'], $row['height'] );
			$info   = self::getAdInfo( $this->item );
			?>
			<div class="wrap">
				<form method="post" action="<?php echo $this->action; ?>">
					<h1><?php echo __( 'Ad Editor', SAM_PRO_DOMAIN ) . ' (' . $header . ')' ?></h1>
					<?php if ( ! empty( $this->message ) ) {
						echo $this->message;
					} ?>

					<div class="metabox-holder has-right-sidebar" id="poststuff">
						<div id="side-info-column" class="inner-sidebar">
							<div class="meta-box-sortables ui-sortable">
								<div id="submitdiv" class="postbox ">
									<div class="handlediv" title="<?php _e( 'Click to toggle', SAM_PRO_DOMAIN ); ?>"><br></div>
									<h3 class="hndle"><span><?php _e( 'Status', SAM_PRO_DOMAIN ); ?></span></h3>

									<div class="inside">
										<div id="submitpost" class="submitbox">
											<div id="minor-publishing">
												<div id="minor-publishing-actions">
													<div id="save-action"></div>
													<div id="preview-action">
														<a id="back-button" class="button-secondary"
														   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-ads'>
															<?php _e( 'Back to Ads List', SAM_PRO_DOMAIN ) ?>
														</a>
													</div>
													<div class="clear"></div>
												</div>
												<div id="misc-publishing-actions">
													<div class="misc-pub-section">
														<label
															for="item_id_info"><?php echo __( 'Advertisement ID', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="item_id_info" style="font-weight: bold;"><?php echo $row['aid']; ?></span>
														<input type="hidden" id="item_id" name="item_id" value="<?php echo $row['aid']; ?>">
														<input type='hidden' name='editor_mode' id='editor_mode' value='item'>
													</div>
													<div class="misc-pub-section">
														<label for="ad_weight_info"><?php echo __( 'Activity', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_weight_info"
														      style="font-weight: bold;"><?php echo (/*($row['ad_weight'] > 0) && */ ! $row['trash'] && $row['moderated'] ) ? __( 'Ad is Active', SAM_PRO_DOMAIN ) : __( 'Ad is Inactive', SAM_PRO_DOMAIN ); ?></span><br>
														<label for="ad_hits_info"><?php echo __( 'Hits', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_hits_info" style="font-weight: bold;"><?php echo $info['hits']; ?></span><br>
														<label for="ad_clicks_info"><?php echo __( 'Clicks', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_clicks_info"
														      style="font-weight: bold;"><?php echo $info['clicks']; ?></span><br>
														<?php _e( '<strong>Note</strong>: Values of impressions (hits) and clicks are the summary data for all sets containing links to this ad.', SAM_PRO_DOMAIN ); ?>
													</div>
													<div class="misc-pub-section">
														<label for="place_size_info"><?php echo __( 'Size', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_size_info"
														      class="post-status-display"><strong><?php echo $apSize->size; ?></strong></span><br>
														<label for="place_width"><?php echo __( 'Width', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_width"
														      class="post-status-display"><strong><?php echo $apSize->width; ?></strong></span><br>
														<label for="place_height"><?php echo __( 'Height', SAM_PRO_DOMAIN ) . ':'; ?></label>
														<span id="ad_height"
														      class="post-status-display"><strong><?php echo $apSize->height; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<input type="checkbox" id="moderated" value="1"
														       name="moderated" <?php checked( 1, $row['moderated'], true ); ?> > <?php _e( 'Moderated', SAM_PRO_DOMAIN ); ?>
														<br>
														<input type="checkbox" id="trash" value="1"
														       name="trash" <?php checked( 1, $row['trash'], true ); ?> > <?php _e( 'Is In Trash', SAM_PRO_DOMAIN ); ?>
													</div>
												</div>
												<div class="clear"></div>
											</div>
											<div id="major-publishing-actions">
												<div id="publishing-action">
													<a id="cancel-button" class="button-secondary"
													   href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-ads'>
														<?php _e( 'Cancel', SAM_PRO_DOMAIN ) ?>
													</a>
													<button id="submit-button" class="button-primary" name="update_item" type="submit">
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
										       title="<?php echo __( 'Name of Ad', SAM_PRO_DOMAIN ) . '. ' . __( 'Required for SAM widgets.', SAM_PRO_DOMAIN ); ?>"
										       placeholder="<?php _e( 'Enter Ad Name here', SAM_PRO_DOMAIN ); ?>">
									</div>
								</div>
								<div class="meta-box-sortables ui-sortable">
									<div id="descdiv" class="postbox ">
										<h3 class="hndle"><span><?php _e( 'Advertisement Description', SAM_PRO_DOMAIN ); ?></span></h3>

										<div class="inside">
											<p>
												<label
													for="item_description"><strong><?php echo __( 'Description', SAM_PRO_DOMAIN ) . ':' ?></strong></label>
												<textarea rows='3' id="item_description" class="code" tabindex="2" name="item_description"
												          style="width:100%; height: 100px;"><?php echo $row['description']; ?></textarea>
											</p>

											<p>
												<?php _e( 'This description is not used anywhere and is added solely for the convenience of managing advertisements.', SAM_PRO_DOMAIN ); ?>
											</p>
										</div>
									</div>
								</div>
								<div id="tabs">
									<ul>
										<li><a href="#tabs-1"><?php _e( 'General', SAM_PRO_DOMAIN ); ?></a></li>
										<li><a href="#tabs-2"><?php _e( 'Extended Restrictions', SAM_PRO_DOMAIN ); ?></a></li>
										<li><a href="#tabs-3"><?php _e( 'Targeting', SAM_PRO_DOMAIN ); ?></a></li>
										<li><a href="#tabs-4"><?php _e( 'Earnings settings', SAM_PRO_DOMAIN ); ?></a></li>
										<?php do_action( 'sam_pro_admin_ad_editor_tabs' ); ?>
									</ul>
									<div id="tabs-1">
										<div class="meta-box-sortables ui-sortable">
											<div id="sizediv" class="postbox ">
												<h3 class="hndle"><?php _e( 'Ad Size', SAM_PRO_DOMAIN ); ?></h3>
												<div class="inside">
													<p><?php _e( 'Select size of this Ad.', SAM_PRO_DOMAIN ); ?></p>
													<?php $apSize->drawList(); ?>
													<p>
														<label for="width"><?php echo __( 'Width', SAM_PRO_DOMAIN ) . ':  '; ?></label>
														<input id="width" name="width" type="number" value="<?php echo $row['width']; ?>">
													</p>
													<p>
														<label for="height"><?php echo __( 'Height', SAM_PRO_DOMAIN ) . ': '; ?></label>
														<input id="height" name="height" type="number" value="<?php echo $row['height']; ?>">
													</p>
												</div>
											</div>
										</div>
										<div id="sources" class="meta-box-sortables ui-sortable">
											<div id="codediv" class="postbox ">
												<h3 class="hndle"><span><?php _e( 'Ad Code', SAM_PRO_DOMAIN ); ?></span></h3>
												<div class="inside">
													<p>
														<input type='radio' name='amode' id='amode_false'
														       value='0' <?php checked( 0, $row['amode'] ) ?>>
														<label
															for='amode_false'><strong><?php _e( 'Image', SAM_PRO_DOMAIN ); ?></strong></label>
													</p>

													<div id="rc-cmf" class='radio-content' style="<?php if ( (int) $row['amode'] != 0 ) {
														echo 'display: none;';
													} ?>">
														<p>
															<label
																for="img"><strong><?php echo __( 'Ad Image', SAM_PRO_DOMAIN ) . ':' ?></strong></label><br>
															<input id="img" class="code" type="text" tabindex="3" name="img"
															       value="<?php echo $row['img']; ?>" style="width:85%">&nbsp;
															<button id="banner-media"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ) ?></button>
															<input id="img_id" name="img_id" type="hidden" value="<?php echo 438; ?>">
														</p>
														<p>
															<label
																for="link"><strong><?php echo __( 'Ad Target', SAM_PRO_DOMAIN ) . ':' ?></strong></label>
															<input id="link" class="code" type="text" tabindex="3" name="link"
															       value="<?php echo $row['link']; ?>" style="width:100%">
														</p>
														<p>
															<label
																for="alt"><strong><?php echo __( 'Ad Alternative Text', SAM_PRO_DOMAIN ) . ':' ?></strong></label>
															<input id="alt" class="code" type="text" tabindex="3" name="alt"
															       value="<?php echo $row['alt']; ?>" style="width:100%">
														</p>
														<p>
															<input type='checkbox' name='clicks' id='clicks'
															       value='1' <?php checked( 1, $row['clicks'] ); ?> >
															<label
																for='clicks'><?php _e( 'Count clicks for this advertisement', SAM_PRO_DOMAIN ); ?></label>
														</p>
														<!--<p><strong><?php _e( 'Use carefully!', SAM_PRO_DOMAIN ) ?></strong> <?php _e( "Do not use if the wp-admin folder is password protected. In this case the viewer will be prompted to enter a username and password during ajax request. It's not good.", SAM_PRO_DOMAIN ) ?></p>-->
														<p>
															<input type="checkbox" name="swf" id="swf" value="1" <?php checked( 1, $row['swf'] ); ?> >
															<label for="swf"><?php _e( 'This is flash (SWF) banner', SAM_PRO_DOMAIN ); ?></label>
														</p>
														<div id="swf-params" class="radio-content" style="<?php if ( (int) $row['swf'] != 1 ) {
															echo 'display: none;';
														} ?>">
															<label for="swf_vars"><strong><?php _e( 'Flash banner "flashvars"', SAM_PRO_DOMAIN ) ?>
																	:</strong></label>
															<textarea name="swf_vars" id="swf_vars" rows="3" placeholder="<?php echo $help; ?>"
															          style="width:100%;"><?php echo htmlspecialchars( stripslashes( $row['swf_vars'] ) ); ?></textarea>
															<button id="click-tag"
															        class="button-secondary"<?php disabled( true, empty( $row['link'] ) ) ?>><?php _e( 'Insert clickTAG', SAM_PRO_DOMAIN ); ?></button>
															<p><?php _e( 'Insert "flashvars" parameters between braces...', SAM_PRO_DOMAIN ); ?></p>
															<label for="swf_params"><strong><?php _e( 'Flash banner "params"', SAM_PRO_DOMAIN ) ?>
																	:</strong></label>
															<textarea name="swf_params" id="swf_params" rows="3" placeholder="<?php echo $help; ?>"
															          style="width:100%;"><?php echo htmlspecialchars( stripslashes( $row['swf_params'] ) ); ?></textarea>
															<p><?php _e( 'Insert "params" parameters between braces...', SAM_PRO_DOMAIN ); ?></p>
															<label for="swf_attrs"><strong><?php _e( 'Flash banner "attributes"', SAM_PRO_DOMAIN ) ?>
																	:</strong></label>
															<textarea name="swf_attrs" id="swf_attrs" rows="3" placeholder="<?php echo $help; ?>"
															          style="width:100%;"><?php echo htmlspecialchars( stripslashes( $row['swf_attrs'] ) ); ?></textarea>
															<p><?php _e( 'Insert "attributes" parameters between braces...', SAM_PRO_DOMAIN ); ?></p>
															<label for="swf_fallback"><?php _e( 'Flash banner fallback code', SAM_PRO_DOMAIN ); ?>
																:</label>
															<textarea name="swf_fallback" id="swf_fallback" rows="3"
															          style="width:100%;"><?php echo htmlspecialchars( stripslashes( $row['swf_fallback'] ) ); ?></textarea>
															<button id="fallback-code"
															        class="button-secondary"><?php _e( 'Generate Fallback Image Code', SAM_PRO_DOMAIN ); ?></button>
														</div>
														<p>
															<label
																for='rel'><strong><?php echo __( 'Add to ad', SAM_PRO_DOMAIN ) . ':'; ?></strong></label>
															<select name='rel' id='rel'>
																<option
																	value='0' <?php selected( 0, $row['rel'] ); ?>><?php _e( 'Non Selected', SAM_PRO_DOMAIN ) ?></option>
																<option
																	value='1' <?php selected( 1, $row['rel'] ); ?>><?php _e( 'nofollow', SAM_PRO_DOMAIN ) ?></option>
																<option
																	value='2' <?php selected( 2, $row['rel'] ); ?>><?php _e( 'dofollow', SAM_PRO_DOMAIN ) ?></option>
																<option
																	value='3' <?php selected( 3, $row['rel'] ); ?>><?php _e( 'noindex', SAM_PRO_DOMAIN ) ?></option>
																<option
																	value='4' <?php selected( 4, $row['rel'] ); ?>><?php _e( 'nofollow and noindex', SAM_PRO_DOMAIN ) ?></option>
															</select>
														</p>
														<div class="clear"></div>
													</div>
													<div class='clear-line'></div>
													<p>
														<input type='radio' name='amode' id='amode_true'
														       value='1' <?php checked( 1, $row['amode'] ) ?>>
														<label for='amode_true'><strong><?php _e( 'Code', SAM_PRO_DOMAIN ); ?></strong></label>
													</p>
													<div id="rc-cmt" class='radio-content' style="<?php if ( (int) $row['amode'] != 1 ) {
														echo 'display: none;';
													} ?>">
														<p>
															<label
																for="acode"><strong><?php echo __( 'Ad Code', SAM_PRO_DOMAIN ) . ':'; ?></strong></label>
															<textarea name='acode' id='acode' rows='10' title='Ad Code'
															          style='width: 100%;'><?php echo $row['acode'] ?></textarea>
															<input type='checkbox' name='inline' id='inline'
															       value='1' <?php checked( 1, $row['inline'] ); ?>>
															<label for='inline'
															       style='vertical-align: middle;'> <?php _e( 'This is inline ad', 'sam-pro' ); ?></label><br>
															<input type='checkbox' name='php' id='php' value='1' <?php checked( 1, $row['php'] ); ?>>
															<label for='php'
															       style='vertical-align: middle;'> <?php _e( 'This code of ad contains PHP script', SAM_PRO_DOMAIN ); ?></label>
														</p>
													</div>
												</div>
											</div>
										</div>
										<div id="codes" class="meta-box-sortables ui-sortable">
											<div id="codediv" class="postbox ">
												<h3 class="hndle"><span><?php _e( 'General Restrictions', SAM_PRO_DOMAIN ); ?></span></h3>
												<div class="inside">
													<p>
														<input type='radio' name='ptype' id='ptype_0'
														       value='0' <?php checked( 0, $row['ptype'] ); ?>>
														<label
															for='ptype_0'><strong><?php _e( 'Show ad on all pages of blog', SAM_PRO_DOMAIN ); ?></strong></label>
													</p>
													<p>
														<input type='radio' name='ptype' id='ptype_1'
														       value='1' <?php checked( 1, $row['ptype'] ); ?>>
														<label
															for='ptype_1'><strong><?php echo __( 'Show ad only on pages of this type', SAM_PRO_DOMAIN ) . ':'; ?></strong></label>
													</p>
													<div id="rc-vt0" class='radio-content' style="<?php if ( (int) $row['ptype'] != 1 ) {
														echo 'display: none;';
													} ?>">
														<ul id="page-types">
															<li
																id="<?php echo SAM_PRO_IS_HOME; ?>"><?php _e( 'Home Page (Home or Front Page)', SAM_PRO_DOMAIN ); ?></li>
															<li
																id="<?php echo SAM_PRO_IS_SINGULAR; ?>"><?php _e( 'Singular Pages', SAM_PRO_DOMAIN ); ?>
																<ul>
																	<li
																		id="<?php echo SAM_PRO_IS_SINGLE; ?>"><?php _e( 'Single Post', SAM_PRO_DOMAIN ); ?></li>
																	<li id="<?php echo SAM_PRO_IS_PAGE; ?>"><?php _e( 'Page', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_POST_TYPE; ?>"><?php _e( 'Custom Post Type', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_ATTACHMENT; ?>"><?php _e( 'Attachment', SAM_PRO_DOMAIN ); ?></li>
																</ul>
															</li>
															<li
																id="<?php echo SAM_PRO_IS_SEARCH; ?>"><?php _e( 'Search Page', SAM_PRO_DOMAIN ); ?></li>
															<li
																id="<?php echo SAM_PRO_IS_404; ?>"><?php _e( '"Not found" Page (HTTP 404: Not Found)', SAM_PRO_DOMAIN ); ?></li>
															<li id="<?php echo SAM_PRO_IS_ARCHIVE; ?>"><?php _e( 'Archive Pages', SAM_PRO_DOMAIN ); ?>
																<ul>
																	<li
																		id="<?php echo SAM_PRO_IS_TAX; ?>"><?php _e( 'Taxonomy Archive Pages', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_CATEGORY; ?>"><?php _e( 'Category Archive Pages', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_TAG; ?>"><?php _e( 'Tag Archive Pages', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_AUTHOR; ?>"><?php _e( 'Author Archive Pages', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_POST_TYPE_ARCHIVE; ?>"><?php _e( 'Custom Post Type Archive Pages', SAM_PRO_DOMAIN ); ?></li>
																	<li
																		id="<?php echo SAM_PRO_IS_DATE; ?>"><?php _e( 'Date Archive Pages (any date-based archive pages, i.e. a monthly, yearly, daily or time-based archive)', SAM_PRO_DOMAIN ); ?></li>
																</ul>
															</li>
														</ul>
													</div>
													<input type="hidden" id="ptypes" name="ptypes" value="<?php echo $row['ptypes'] ?>">
												</div>
											</div>
										</div>
										<?php do_action( 'sam_pro_admin_ad_editor_tabs_general', $row ); ?>
									</div>
									<div id="tabs-2">
										<div id="xlimits" class="meta-box-sortables ui-sortable">
											<div id="limitsdiv" class="postbox ">
												<h3 class="hndle">
													<span><?php _e( 'Additional restrictions of the advertisement showing', SAM_PRO_DOMAIN ); ?></span>
												</h3>
												<div class="inside">
													<p>
														<input type="checkbox" id="eposts" name="eposts"
														       value="1" <?php checked( 1, $row['eposts'] );
														disabled( 0, (integer) $this->settings['rule_id'] ); ?>>
														<label
															for="eposts"><?php _e( 'Enable restriction for posts/pages', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-posts" class="radio-content" <?php if ( (integer) $row['eposts'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xposts" id="xposts_0"
														       value="0" <?php checked( 0, $row['xposts'] ); ?>>
														<label for="xposts_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xposts" id="xposts_1"
														       value="1" <?php checked( 1, $row['xposts'] ); ?>>
														<label for="xposts_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="posts" name="posts" value="<?php echo $row['posts']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showPosts"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show the ad only on the certain pages. / Do not show the ad on the certain pages.', SAM_PRO_DOMAIN ) ?></p>
													<div class="clear-line"></div>
													<p>
														<input type="checkbox" id="ecats" name="ecats" value="1" <?php checked( 1, $row['ecats'] );
														disabled( 0, (integer) $this->settings['rule_categories'] ); ?>>
														<label
															for="ecats"><?php _e( 'Enable restriction for categories', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-cats" class="radio-content" <?php if ( (integer) $row['ecats'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xcats" id="xcats_0"
														       value="0" <?php checked( 0, $row['xcats'] ); ?>>
														<label for="xcats_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xcats" id="xcats_1"
														       value="1" <?php checked( 1, $row['xcats'] ); ?>>
														<label for="xcats_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="cats" name="cats" value="<?php echo $row['cats']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showCats"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show the ad only on the certain pages of categories (single posts and archives). / Do not show the ad on the certain pages of categories (single posts and archives).', SAM_PRO_DOMAIN ) ?></p>
													<div class="clear-line"></div>
													<p>
														<input type="checkbox" id="etags" name="etags" value="1" <?php checked( 1, $row['etags'] );
														disabled( 0, (integer) $this->settings['rule_tags'] ); ?>>
														<label for="etags"><?php _e( 'Enable restriction for tags', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-tags" class="radio-content" <?php if ( (integer) $row['etags'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xtags" id="xtags_0"
														       value="0" <?php checked( 0, $row['xtags'] ); ?>>
														<label for="xtags_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xtags" id="xtags_1"
														       value="1" <?php checked( 1, $row['xtags'] ); ?>>
														<label for="xtags_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="tags" name="tags" value="<?php echo $row['tags']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showTags"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show the ad only on the certain pages of tags (single posts and archives). / Do not show the ad on the certain pages of tags (single posts and archives).', SAM_PRO_DOMAIN ) ?></p>
													<div class="clear-line"></div>
													<p>
														<input type="checkbox" id="eauthors" name="eauthors"
														       value="1" <?php checked( 1, $row['eauthors'] );
														disabled( 0, (integer) $this->settings['rule_authors'] ); ?>>
														<label
															for="eauthors"><?php _e( 'Enable restriction for authors', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-authors" class="radio-content" <?php if ( (integer) $row['eauthors'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xauthors" id="xauthors_0"
														       value="0" <?php checked( 0, $row['xauthors'] ); ?>>
														<label for="xauthors_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xauthors" id="xauthors_1"
														       value="1" <?php checked( 1, $row['xauthors'] ); ?>>
														<label for="xauthors_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="authors" name="authors" value="<?php echo $row['authors']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showAuthors"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show the ad only on the certain pages of authors (single posts and archives). / Do not show the ad on the certain pages of authors (single posts and archives).', SAM_PRO_DOMAIN ) ?></p>
													<div class="clear-line"></div>
													<p>
														<input type="checkbox" id="etax" name="etax" value="1" <?php checked( 1, $row['etax'] );
														disabled( 0, (integer) $this->settings['rule_taxes'] ); ?>>
														<label
															for="etax"><?php _e( 'Enable restriction for Custom Taxonomies Terms', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-tax" class="radio-content" <?php if ( (integer) $row['etax'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xtax" id="xtax_0" value="0" <?php checked( 0, $row['xtax'] ); ?>>
														<label for="xtax_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xtax" id="xtax_1" value="1" <?php checked( 1, $row['xtax'] ); ?>>
														<label for="xtax_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="taxes" name="taxes" value="<?php echo $row['taxes']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showTaxes"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show the ad only on the certain pages of Custom Taxonomies Terms (single posts and archives). / Do not show the ad on the certain pages of Custom Taxonomies Terms (single posts and archives).', SAM_PRO_DOMAIN ) ?></p>
													<div class="clear-line"></div>
													<p>
														<input type="checkbox" id="etypes" name="etypes"
														       value="1" <?php checked( 1, $row['etypes'] );
														disabled( 0, (integer) $this->settings['rule_types'] ); ?>>
														<label
															for="etypes"><?php _e( 'Enable restriction for Custom Post Types', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-types" class="radio-content" <?php if ( (integer) $row['etypes'] != 1 )
														echo 'style="display:none;"' ?>>
														<input type="radio" name="xtypes" id="xtypes_0"
														       value="0" <?php checked( 0, $row['xtypes'] ); ?>>
														<label for="xtypes_0"><?php _e( 'Show', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="xtypes" id="xtypes_1"
														       value="1" <?php checked( 1, $row['xtypes'] ); ?>>
														<label for="xtypes_1"><?php _e( 'Do not show', SAM_PRO_DOMAIN ); ?></label>
														<p>
															<input type="text" id="types" name="types" value="<?php echo $row['types']; ?>"
															       style="width: 85%;">&nbsp;
															<button id="showTypes"
															        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
														</p>
													</div>
													<p><?php _e( 'Show ad only in custom type single posts or custom post type archives of certain custom post types. / Do not show ad in custom type single posts or custom post type archives of certain custom post types).', SAM_PRO_DOMAIN ) ?></p>
												</div>
											</div>
										</div>
										<div id="x2limits" class="meta-box-sortables ui-sortable">
											<div id="limitsdiv2" class="postbox ">
												<h3 class="hndle"><span><?php _e( 'Limitations of the ad showing', SAM_PRO_DOMAIN ); ?></span>
												</h3>
												<div class="inside">
													<p>
														<input type="checkbox" id="schedule" name="schedule"
														       value="1" <?php checked( 1, $row['schedule'] );
														disabled( 0, (integer) $this->settings['rule_schedule'] ); ?>>
														<label for="schedule"><?php _e( 'Use the schedule for this ad', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-schedule" class="radio-content" <?php if ( (integer) $row['schedule'] != 1 ) {
														echo 'style="display:none;"';
													} ?>>
														<p>
															<label
																for='startDate'><?php echo __( 'Campaign Start Date', SAM_PRO_DOMAIN ) . ':' ?></label>
															<input type="text" id="startDate">
															<input type='hidden' name='sdate' id='sdate' value='<?php echo ((isset($row['sdate']) && $row['sdate'] != '0000-00-00 00:00:00') ? $row['sdate'] : date('Y-m-d H:i:s')); ?>'>
														</p>
														<p>
															<label
																for='finishDate'><?php echo __( 'Campaign End Date', SAM_PRO_DOMAIN ) . ':' ?></label>
															<input id="finishDate" type="text">
															<input type='hidden' name='fdate' id='fdate' value='<?php echo ((isset($row['fdate']) && $row['fdate'] != '0000-00-00 00:00:00') ? $row['fdate'] : date('Y-m-d H:i:s')); ?>'>
														</p>
													</div>
													<p><?php _e( 'Use these parameters for displaying ad during the certain period of time.', SAM_PRO_DOMAIN ); ?></p>
													<div class="clear-line"></div>
													<p>
														<input type='checkbox' name='limit_hits' id='limit_hits'
														       value='1' <?php checked( 1, $row['limit_hits'] );
														disabled( 0, (integer) $this->settings['rule_hits'] ); ?>>
														<label for='limit_hits'><?php _e( 'Use limitation by hits', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-hl" class="radio-content" style="<?php if ( (int) $row['limit_hits'] != 1 ) {
														echo 'display: none;';
													} ?>">
														<p>
															<label for='hits_limit'><?php echo __( 'Hits Limit', SAM_PRO_DOMAIN ) . ':' ?></label>
															<input type='number' name='hits_limit' id='hits_limit'
															       value='<?php echo $row['hits_limit']; ?>'>
														</p>
													</div>
													<p>
														<?php _e( 'Use this parameter for limiting displaying of ad by hits.', SAM_PRO_DOMAIN ); ?>
													</p>
													<div class='clear-line'></div>
													<p>
														<input type='checkbox' name='limit_clicks' id='limit_clicks'
														       value='1' <?php checked( 1, $row['limit_clicks'] );
														disabled( 0, (integer) $this->settings['rule_clicks'] ); ?>>
														<label for='limit_clicks'><?php _e( 'Use limitation by clicks', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="rc-cl" class="radio-content" style="<?php if ( (int) $row['limit_clicks'] != 1 ) {
														echo 'display: none;';
													} ?>">
														<p>
															<label for='clicks_limit'><?php echo __( 'Clicks Limit', SAM_PRO_DOMAIN ) . ':' ?></label>
															<input type='number' name='clicks_limit' id='clicks_limit'
															       value='<?php echo $row['clicks_limit']; ?>'>
														</p>
													</div>
													<p>
														<?php _e( 'Use this parameter for limiting displaying of ad by clicks.', SAM_PRO_DOMAIN ); ?>
													</p>
												</div>
											</div>
										</div>
										<?php do_action( 'sam_pro_admin_ad_editor_tabs_restrictions', $row ); ?>
									</div>
									<div id="tabs-3">
										<div id="targeting" class="meta-box-sortables ui-sortable">
											<div id="limitsusr" class="postbox">
												<h3 class="hndle"><span><?php _e( 'Users', SAM_PRO_DOMAIN ); ?></span></h3>
												<div class="inside">
													<p>
														<?php echo __( 'Show this ad for', SAM_PRO_DOMAIN ) . ':'; ?>
													</p>
													<p>
														<input type="radio" name="users" id="users_0"
														       value="0" <?php checked( 0, $row['users'] ); ?> >
														<label for="users_0"><?php _e( 'all users', SAM_PRO_DOMAIN ); ?></label>&nbsp;&nbsp;
														<input type="radio" name="users" id="users_1"
														       value="1" <?php checked( 1, $row['users'] ); ?> >
														<label for="users_1"><?php _e( 'some users', SAM_PRO_DOMAIN ); ?></label>
													</p>
													<div id="custom-users" class="sub-content" style="<?php if ( (int) $row['users'] != 1 ) {
														echo 'display: none;';
													} ?>">
														<p>
															<input type="checkbox" name="users_unreg" id="users_unreg"
															       value="1" <?php checked( 1, $row['users_unreg'] ); ?> >
															<label for="users_unreg"><?php _e( 'Unregistered Users', SAM_PRO_DOMAIN ); ?></label>
														</p>
														<p>
															<input type="checkbox" name="users_reg" id="users_reg"
															       value="1" <?php checked( 1, $row['users_reg'] ); ?> >
															<label for="users_reg"><?php _e( 'Registered Users', SAM_PRO_DOMAIN ) ?></label>
														</p>
														<div id="x-reg-users" class="sub-content" style="<?php if ( (int) $row['users_reg'] != 1 ) {
															echo 'display: none;';
														} ?>">
															<p>
																<input type="checkbox" name="xusers" id="xusers"
																       value="1" <?php checked( 1, $row['xusers'] ) ?> >
																<label for="xusers"><?php _e( 'Exclude these users', SAM_PRO_DOMAIN ) ?></label>
															</p>
															<div id="x-view-users" class="sub-content" style="<?php if ( (int) $row['xusers'] != 1 ) {
																echo 'display: none;';
															} ?>">
																<label
																	for="xvusers"><?php echo __( 'Registered Users', SAM_PRO_DOMAIN ) . ':'; ?></label>
																<input type="text" name="xvusers" id="xvusers" value="<?php echo $row['xvusers'] ?>"
																       style="width: 85%;">
																<button id="showXUsers"
																        class="button-secondary"><?php _e( 'Select', SAM_PRO_DOMAIN ); ?></button>
															</div>
															<p>
																<input type="checkbox" name="advertiser" id="advertiser"
																       value="1" <?php checked( 1, $row['advertiser'] ); ?> >
																<label
																	for="advertiser"><?php _e( 'Do not show this ad for advertiser', SAM_PRO_DOMAIN ) ?></label>
															</p>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php do_action( 'sam_pro_admin_ad_editor_tabs_targeting', $row ); ?>
									</div>
									<div id="tabs-4">
										<div id="ad-owner" class="meta-box-sortables ui-sortable">
											<div id="adowner" class="postbox">
												<h3 class="hndle"><span><?php _e( 'Advertiser', SAM_PRO_DOMAIN ); ?></span></h3>
												<div class="inside">
													<p>
														<label for="owner"><?php _e( 'Advertiser Nick Name', SAM_PRO_DOMAIN ); ?></label>
														<input id="owner" name="owner" type="text" value="<?php echo $row['owner']; ?>"
														       style="width: 100%;">
													</p>
													<p>
														<label for="owner_name"><?php _e( 'Advertiser Name', SAM_PRO_DOMAIN ); ?></label>
														<input id="owner_name" name="owner_name" type="text"
														       value="<?php echo $row['owner_name']; ?>" style="width: 100%;">
													</p>
													<p>
														<label for="owner_mail"><?php _e( 'Advertiser e-mail', SAM_PRO_DOMAIN ); ?></label>
														<input id="owner_mail" name="owner_mail" type="email"
														       value="<?php echo $row['owner_mail']; ?>" style="width: 100%;">
													</p>
													<p>
														<input id="showAdvertisers" class="button-secondary" type="button"
														       value="<?php echo __( 'Select', SAM_PRO_DOMAIN ); ?>">
													</p>
												</div>
											</div>
										</div>
										<?php do_action( 'sam_pro_admin_ad_editor_tabs_earnings', $row ); ?>
									</div>
									<?php do_action( 'sam_pro_admin_ad_editor_tabs_body', $row ); ?>
									<?php
									if ( $this->item > 0 ) {
										include_once( 'sam-pro-ad.php' );
										$ad = new SamProAd( $this->item );
										if ( ! empty( $ad->ad ) ) {
											?>
											<div class="clear-div"></div>
											<div class="meta-box-sortables ui-sortable">
												<div id="prevdiv" class="postbox ">
													<h3 class="hndle"><span><?php _e( 'Advertisement Preview', SAM_PRO_DOMAIN ); ?></span></h3>
													<div class="inside">
														<?php echo $ad->ad; ?>
													</div>
												</div>
											</div>
										<?php }
									} ?>
								</div>
							</div>
						</div>
					</div>
				</form>
				<div id="postsDialog" title="<?php _e( 'Select Posts', SAM_PRO_DOMAIN ); ?>">
					<div id="postsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-posts" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-posts" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="catsDialog" title="<?php _e( 'Select Categories', SAM_PRO_DOMAIN ); ?>">
					<div id="catsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-cats" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-cats" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="tagsDialog" title="<?php _e( 'Select Tags', SAM_PRO_DOMAIN ); ?>">
					<div id="tagsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-tags" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-tags" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="authorsDialog" title="<?php _e( 'Select Authors', SAM_PRO_DOMAIN ); ?>">
					<div id="authorsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-authors" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-authors" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="taxDialog" title="<?php _e( 'Select Custom Taxonomies Terms', SAM_PRO_DOMAIN ); ?>">
					<div id="taxGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-tax" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-tax" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="typesDialog" title="<?php _e( 'Select Custom Post Types', SAM_PRO_DOMAIN ); ?>">
					<div id="typesGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-types" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-types" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="usersDialog" title="<?php _e( 'Select Users', SAM_PRO_DOMAIN ); ?>">
					<div id="usersGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-users" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-users" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
				<div id="advertsDialog" title="<?php _e( 'Select Advertiser', SAM_PRO_DOMAIN ); ?>">
					<div id="advertsGrid"></div>
					<div class="sam-centered">
						<input type="button" id="select-adverts" value="<?php _e( 'Select', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary">&nbsp;
						<input type="button" id="cancel-adverts" value="<?php _e( 'Cancel', SAM_PRO_DOMAIN ); ?>"
						       class="button-secondary cancel-dialog">
					</div>
				</div>
			</div>
			<?php
			do_action( 'sam_pro_admin_ad_editor_dialogs' );
		}
	}
}