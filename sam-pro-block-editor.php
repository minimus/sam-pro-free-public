<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 15.03.2015
 * Time: 7:06
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SamProBlockEditor' ) ) {
	class SamProBlockEditor {
		private $settings;
		private $item;
		private $update;
		private $action = '';
		private $message = '';
		private $items;

		public function __construct( $options ) {
			$this->settings = $options;
			$this->update   = ( isset( $_POST['update_item'] ) );
			if ( isset( $_GET['item'] ) ) {
				$this->item = (integer) ( $_GET['item'] );
			} else {
				$this->item = 0;
			}
		}

		private function getData( $data = null ) {
			if ( is_null( $data ) ) {
				return array();
			} else {
				return unserialize( $data );
			}
		}

		private function setData( $lines, $columns ) {
			$out = array();
			if ( $lines > 0 && $columns > 0 ) {
				for ( $line = 1; $line <= $lines; $line ++ ) {
					for ( $column = 1; $column <= $columns; $column ++ ) {
						if ( isset( $_POST[ 'item-' . $line . '-' . $column ] ) ) {
							$out[ $line ][ $column ] = sanitize_text_field( $_POST[ 'item-' . $line . '-' . $column ] );
						}
					}
				}
			}

			return serialize( $out );
		}

		private function drawItem( $line, $column, $places, $ads, $zones, $data = null ) {
			?>
      <div class="block-editor-item">
        <div class="meta-box-sortables ui-sortable">
          <div id="headdiv-<?php echo $line . '-' . $column ?>" class="postbox" style="min-width: 175px;">
            <h3 class="hndle"><span><?php echo __( 'Item', SAM_PRO_DOMAIN ) . " $line-$column"; ?></span></h3>
            <div class="inside">
              <!--<input type='hidden' name='val-<?php echo $line . '-' . $column; ?>' id='val-<?php echo $line . '-' . $column; ?>' value="<?php echo( ( isset( $data[ $line ][ $column ] ) ) ? $data[ $line ][ $column ] : - 1 ); ?>">-->
              <label for="item-<?php echo $line . '-' . $column; ?>"><?php _e( 'Select Ad Object', SAM_PRO_DOMAIN ); ?>
                :</label>
              <!--<input type='text' name='item-<?php echo $line . '-' . $column; ?>' class="block-item" id='item-<?php echo $line . '-' . $column; ?>'>-->
              <select class="block-item" id="item-<?php echo $line . '-' . $column; ?>"
                      name="item-<?php echo $line . '-' . $column; ?>">
                <optgroup label="<?php _e( 'Places', SAM_PRO_DOMAIN ); ?>">
									<?php foreach ( $places as $place ) { ?>
                    <option
                      value="<?php echo $place['ival']; ?>" <?php selected( $data[ $line ][ $column ], $place['ival'] ); ?>><?php echo $place['title'] ?></option>
									<?php } ?>
                </optgroup>
                <optgroup label="<?php _e( 'Ads', SAM_PRO_DOMAIN ); ?>">
									<?php foreach ( $ads as $ad ) { ?>
                    <option
                      value="<?php echo $ad['ival']; ?>" <?php selected( $data[ $line ][ $column ], $ad['ival'] ); ?>><?php echo $ad['title'] ?></option>
									<?php } ?>
                </optgroup>
                <optgroup label="<?php _e( 'Zones', SAM_PRO_DOMAIN ); ?>">
									<?php foreach ( $zones as $zone ) { ?>
                    <option
                      value="<?php echo $zone['ival']; ?>" <?php selected( $data[ $line ][ $column ], $zone['ival'] ); ?>><?php echo $zone['title'] ?></option>
									<?php } ?>
                </optgroup>
              </select>
							<?php do_action( 'sam_pro_admin_block_editor_item' ); ?>
            </div>
          </div>
        </div>
      </div>
			<?php
		}

		private function buildEditorItems( $lines, $columns, $data = null ) {
			global $wpdb;
			$pTable = $wpdb->prefix . 'sampro_places';
			$aTable = $wpdb->prefix . 'sampro_ads';
			$zTable = $wpdb->prefix . 'sampro_zones';

			$sql    = "SELECT CONCAT(0, '_', pid) AS ival, title FROM {$pTable};";
			$places = $wpdb->get_results( $sql, ARRAY_A );
			$sql    = "SELECT CONCAT(1, '_', aid) AS ival, title FROM {$aTable}";
			$ads    = $wpdb->get_results( $sql, ARRAY_A );
			$sql    = "SELECT CONCAT(2, '_', zid) AS ival, title FROM {$zTable}";
			$zones  = $wpdb->get_results( $sql, ARRAY_A );

			for ( $i = 1; $i <= $lines; $i ++ ) {
				echo "<div id='line-$i' class='block-editor-line'>";
				for ( $j = 1; $j <= $columns; $j ++ ) {
					$this->drawItem( $i, $j, $places, $ads, $zones, $data );
				}
				echo "</div>";
			}
		}

		private function getItemData( $item ) {
			global $wpdb, $current_user;

			get_current_user();
			$bTable = $wpdb->prefix . 'sampro_blocks';
			$row    = apply_filters( 'sam_pro_admin_block_default_data', array(
				'bid'         => 0,
				'title'       => '',
				'description' => '',
				'b_rows'      => 1,
				'b_columns'   => 1,
				'b_data'      => '',
				'b_style'     => 'display: flex; flex-direction: column; justify-content: center;',
				'l_style'     => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; margin: 0; padding: 0;',
				'i_style'     => '',
				'trash'       => 0
			) );

			if ( $this->item > 0 ) {
				$sql = "SELECT * FROM {$bTable} bt WHERE bt.bid = %d";
				$row = $wpdb->get_row( $wpdb->prepare( $sql, $item ), ARRAY_A );
			}

			return $row;
		}

		private function updateItemData( $id ) {
			global $wpdb;
			$bTable = $wpdb->prefix . 'sampro_blocks';
			$out    = $id;

			$updateRow = apply_filters( 'sam_pro_admin_block_save_data', array(
				'title'       => stripslashes( sanitize_text_field( $_POST['item_name'] ) ),
				'description' => stripslashes( sanitize_text_field( $_POST['item_description'] ) ),
				'b_rows'      => ( ( isset( $_POST['b_rows'] ) ) ? (int) $_POST['b_rows'] : 0 ),
				'b_columns'   => ( ( isset( $_POST['b_columns'] ) ) ? (int) $_POST['b_columns'] : 0 ),
				'b_data'      => self::setData( (integer) ( ( isset( $_POST['b_rows'] ) ) ? (int) $_POST['b_rows'] : 0 ), ( ( isset( $_POST['b_columns'] ) ) ? (int) $_POST['b_columns'] : 0 ) ),
				'b_style'     => stripslashes( sanitize_text_field( $_POST['b_style'] ) ),
				'l_style'     => stripslashes( sanitize_text_field( $_POST['l_style'] ) ),
				'i_style'     => stripslashes( sanitize_text_field( $_POST['i_style'] ) ),
				'trash'       => ( ( isset( $_POST['trash'] ) ) ? (int) $_POST['trash'] : 0 )
			) );

			$formatRow = apply_filters( 'sam_pro_admin_block_save_format', array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d'
			) );

			if ( $id == 0 ) {
				$wpdb->insert( $bTable, $updateRow, $formatRow );
				$out = $wpdb->insert_id;
			} else {
				$wpdb->update( $bTable, $updateRow, array( 'bid' => $id ), $formatRow, array( '%d' ) );
			}
			$this->message = "<div class='updated'><p><strong>" . __( "Ads Block Data Updated.", SAM_PRO_DOMAIN ) . "</strong></p></div>";

			return $out;
		}

		public function show() {
			if ( $this->item == 0 ) {
				$header = __( 'New Block', SAM_PRO_DOMAIN );
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

			$row  = self::getItemData( $this->item );
			$data = self::getData( $row['b_data'] );
			?>
      <div class="wrap">
        <form method="post" action="<?php echo $this->action; ?>">
          <h1><?php echo __( 'Ads Block Editor', SAM_PRO_DOMAIN ) . ' (' . $header . ')' ?></h1>
					<?php if ( ! empty( $this->message ) ) {
						echo $this->message;
					} ?>
          <div class="metabox-holder has-right-sidebar" id="poststuff">
            <div id="side-info-column" class="inner-sidebar">
              <div class="meta-box-sortables ui-sortable">
                <div id="submitdiv" class="postbox ">
                  <h3 class="hndle"><span><?php _e( 'Status', SAM_PRO_DOMAIN ); ?></span></h3>
                  <div class="inside">
                    <div id="submitpost" class="submitbox">
                      <div id="minor-publishing">
                        <div id="minor-publishing-actions">
                          <div id="save-action"></div>
                          <div id="preview-action">
                            <a id="back-button" class="button-secondary"
                               href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-blocks'>
															<?php _e( 'Back to Blocks List', SAM_PRO_DOMAIN ) ?>
                            </a>
                          </div>
                          <div class="clear"></div>
                        </div>
                        <div id="misc-publishing-actions">
                          <div class="misc-pub-section">
                            <label for="place_id_stat"><?php echo __( 'Ads Block ID', SAM_PRO_DOMAIN ) . ':'; ?></label>
                            <span id="place_id_stat" class="post-status-display"><?php echo $row['bid']; ?></span>
                            <input type="hidden" id="place_id" name="place_id" value="<?php echo $this->item; ?>">
                          </div>
                          <div class="misc-pub-section">
                            <label for="place_size_info"><?php echo __( 'Size', SAM_PRO_DOMAIN ) . ':'; ?></label>
                            <span id="place_size_info"
                                  class="post-status-display"><?php echo $row['b_rows'] . ' x ' . $row['b_columns']; ?></span>
                          </div>
                          <div class="misc-pub-section">
                            <label for="trash_no">
                              <input type="checkbox" id="trash" value="1"
                                     name="trash" <?php checked( 1, $row['trash'] ); ?>>
															<?php _e( 'In Trash', SAM_PRO_DOMAIN ); ?>
                            </label>
                          </div>
                        </div>
                        <div class="clear"></div>
                      </div>
                      <div id="major-publishing-actions">
                        <div id="publishing-action">
                          <a id="cancel-button" class="button-secondary"
                             href='<?php echo admin_url( 'admin.php' ); ?>?page=sam-pro-blocks'>
														<?php _e( 'Cancel', SAM_PRO_DOMAIN ) ?>
                          </a>
                          <button
                            id="submit-button"
                            class="button-primary"
                            name="update_item"
                            type="submit"<?php if ( ! empty( $this->action ) )
														echo " formaction='{$this->action}'" ?>>
														<?php _e( 'Save', SAM_PRO_DOMAIN ) ?>
                          </button>
                          <!--<input type="submit" class='button-primary' name="update_place" value="<?php _e( 'Save', SAM_PRO_DOMAIN ) ?>" >-->
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
                           title="<?php echo __( 'Name of Ads Block', SAM_PRO_DOMAIN ) . '. ' . __( 'Required for SAM widgets.', SAM_PRO_DOMAIN ); ?>"
                           placeholder="<?php _e( 'Enter Ads Block Name Here', SAM_PRO_DOMAIN ); ?>">
                  </div>
                </div>
                <div class="meta-box-sortables ui-sortable">
                  <div id="descdiv" class="postbox ">
                    <h3 class="hndle"><span><?php _e( 'Ads Block Description', SAM_PRO_DOMAIN ); ?></span></h3>
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
                <div class="meta-box-sortables ui-sortable">
                  <div id="structdiv" class="postbox">
                    <h3 class="hndle"><span><?php _e( 'Block Structure', SAM_PRO_DOMAIN ); ?></span></h3>
                    <div class="inside">
                      <p>
                        <label for="b_rows"><?php _e( 'Rows', SAM_PRO_DOMAIN ); ?>: </label>
                        <input id="b_rows" name="b_rows" type="number" value="<?php echo $row['b_rows']; ?>">
                      </p>
                      <p>
                        <label for="b_columns"><?php _e( 'Items per Row', SAM_PRO_DOMAIN ); ?>: </label>
                        <input id="b_columns" name="b_columns" type="number" value="<?php echo $row['b_columns']; ?>">
                      </p>
                      <p><?php _e( 'After changing these properties you must save the Block settings before using Items Editor.' ) ?></p>
                    </div>
                  </div>
                </div>
                <div class="meta-box-sortables ui-sortable">
                  <div class="postbox" id="block-style">
                    <h3 class="hndle"><span><?php _e( 'Styles', SAM_PRO_DOMAIN ); ?></span></h3>
                    <div class="inside">
                      <p>
                        <label for="b_style"><?php _e( 'Block Style', SAM_PRO_DOMAIN ); ?>:</label>
                        <textarea id="b_style" name="b_style" placeholder="value_name: value;" style="width:100%;"
                                  rows="5"><?php echo htmlspecialchars( stripslashes( $row['b_style'] ) ); ?></textarea>
                      </p>
                      <p>
                        <label for="l_style"><?php _e( 'Block Line Style', SAM_PRO_DOMAIN ); ?>:</label>
                        <textarea id="l_style" name="l_style" placeholder="value_name: value;" style="width:100%;"
                                  rows="5"><?php echo htmlspecialchars( stripslashes( $row['l_style'] ) ); ?></textarea>
                      </p>
                      <div class="helpers">
                        <input id="line-center" type="button" class="button-secondary cnt-helper"
                               value="<?php _e( 'Center', SAM_PRO_DOMAIN ); ?>">
                        <input id="line-left" type="button" class="button-secondary cnt-helper"
                               value="<?php _e( 'Left', SAM_PRO_DOMAIN ); ?>">
                        <input id="line-right" type="button" class="button-secondary cnt-helper"
                               value="<?php _e( 'Right', SAM_PRO_DOMAIN ); ?>">
                      </div>
                      <p>
                        <label for="i_style"><?php _e( "Block Item Style", SAM_PRO_DOMAIN ); ?>:</label>
                        <textarea id="i_style" name="i_style" placeholder="value_name: value;" style="width:100%;"
                                  rows="5"><?php echo htmlspecialchars( stripslashes( $row['i_style'] ) ); ?></textarea>
                      </p>
                      <p><?php _e( 'Use <strong>Stylesheet rules</strong> for defining these properties.<br><strong>For example:</strong> <code>background: url(sheep.png) center bottom no-repeat;</code> for background or/and <code>border: 5px solid red;</code> for border property, etc. Use the semicolon to separate parameters.', SAM_PRO_DOMAIN ); ?></p>
                      <p><?php _e( "<strong>Important Note</strong>: As the Ads Block is the regular structure, predefined styles of individual items for drawing Ads Block's elements aren't used. Define styles for Ads Block Items here!", SAM_PRO_DOMAIN ); ?></p>
                    </div>
                  </div>
                </div>
              </div>
							<?php do_action( 'sam_pro_admin_block_editor', $row ); ?>
              <div class='block-editor'>
                <div class="meta-box-sortables ui-sortable">
                  <div id="descdiv" class="postbox ">
                    <h3 class="hndle"><span><?php _e( 'Items Editor', SAM_PRO_DOMAIN ); ?></span></h3>
                    <div class="inside">
                      <p><?php _e( 'Adjust items settings of this Ads Block.', SAM_PRO_DOMAIN ); ?></p>
											<?php $this->buildEditorItems( $row['b_rows'], $row['b_columns'], $data ); ?>
                      <p><?php _e( 'Block Editor.', SAM_PRO_DOMAIN ); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
			<?php
		}
	}
}