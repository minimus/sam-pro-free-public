<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 28.07.2015
 * Time: 17:29
 */
if( ! class_exists( 'SamProStatisticsCleaner' ) ) {
	class SamProStatisticsCleaner {
		private $settings;

		public function __construct( $settings ) {
			$this->settings = $settings;
		}

		private function errorWrite($eTable, $rTable, $eSql = null, $eResult = null, $lastError = null, $date = null) {
			global $wpdb;

			$errorMsg = '';
			$error = false;

			if(!is_null($eResult)) {
				if($eResult === false) {
					$errorMsg = (empty($lastError)) ? __('An error occurred during clearing process...', SAM_PRO_DOMAIN) : $lastError;
					$error = true;
					$wpdb->insert(
						$eTable,
						array(
							'edate' => current_time('mysql'),
							'tname' => $rTable,
							'etype' => 0,
							'emsg' => $errorMsg,
							'esql' => $eSql,
							'solved' => 0
						),
						array('%s', '%s', '%d', '%s', '%s', '%d')
					);
				}
				else {
					$errorMsg = (empty($lastError)) ? sprintf( __('All statistical data before %s is cleared...', SAM_PRO_DOMAIN), $date ) : $lastError;
					$wpdb->insert(
						$eTable,
						array(
							'edate' => current_time('mysql'),
							'tname' => $rTable,
							'etype' => 1,
							'emsg' => $errorMsg,
							'esql' => $eSql,
							'solved' => 0
						),
						array('%s', '%s', '%d', '%s', '%s', '%d')
					);
				}
			}

			return array('error' => $error, 'msg' => $errorMsg);
		}

		public function clear($date = null) {
			if($this->settings['keepStats'] == 0) return '';

			if($date == null) {
				$nowDate = new DateTime('now');
				$modify = ($this->settings['keepStats'] < 12) ? '-' . $this->settings['keepStats'] . ' month' : '-1 year';
				$nowDate->modify($modify);
				$date = ($this->settings['keepStats'] < 12) ? $nowDate->format('Y-m-01 00:00') : $nowDate->format('Y-01-01 00:00');
				if($this->settings['keepStats'] == 12) $nowDate->setDate((int)$nowDate->format('Y'), 1, 1);
				$sDate = $nowDate->format(str_replace(array('d', 'j'), array('01', '1'), get_option('date_format')));
			}
			else $sDate = $date;

			global $wpdb;
			$dbResult = null;
			$el = (integer)$this->settings['errorlog'];
			$error = false;

			$sTable = $wpdb->prefix . 'sampro_stats';
			$eTable = $wpdb->prefix . "sampro_errors";

			$sql = "DELETE FROM {$sTable} WHERE edate < %s;";
			$dbResult = $wpdb->query($wpdb->prepare($sql, $date));
			if($el) {
				$out = self::errorWrite($eTable, $sTable, $wpdb->prepare($sql, $date), $dbResult, $wpdb->last_error, $sDate);
				$dbResult = null;
			}
			else $out = array('error' => $error, 'msg' => __('Statistical data were cleared.', SAM_PRO_DOMAIN));

			return $out;
		}

		public function kill() {
			global $wpdb;
			$dbResult = null;
			$el = (integer)$this->settings['errorlog'];
			$error = false;

			$nowDate = new DateTime('now');
			$sDate = $nowDate->format(str_replace(array('d', 'j'), array('01', '1'), get_option('date_format')));

			$sTable = $wpdb->prefix . 'sampro_stats';
			$eTable = $wpdb->prefix . "sampro_errors";

			$sql = "DELETE FROM {$sTable};";
			$dbResult = $wpdb->query($sql);
			if($el) {
				$out = self::errorWrite($eTable, $sTable, $sql, $dbResult, $wpdb->last_error, $sDate);
				$dbResult = null;
			}
			else $out = array('error' => $error, 'msg' => __('All statistical data are completely removed.', SAM_PRO_DOMAIN));

			if(!$out['error']) $out['msg'] = __('All statistical data are completely removed.', SAM_PRO_DOMAIN);

			return $out;
		}

		public function show($result) {
			$error = (!is_null($result)) ? (isset($result['error']) ? $result['error'] : false) : false;
			$msg = (!is_null($result)) ? $result['msg'] : '';
			?>
			<div class='ui-sortable sam-section'>
				<div class='postbox opened'>
					<h3 class='hndle'><?php _e('Clear Statistical Data', SAM_PRO_DOMAIN); ?></h3>
					<div class="inside">
						<?php if(!empty($msg)) { ?>
							<div id="stats-tools" class="<?php echo ($error) ? 'sam-pro-warning' : 'sam-pro-info' ?>">
								<p><?php echo $msg; ?></p>
							</div>
						<?php } ?>
						<p><strong><?php _e('Reset Statistical Data', SAM_PRO_DOMAIN); ?></strong></p>
						<p>
							<button id="clear-stats" name="ClearStats" type="submit" class="button-secondary">
								<?php _e('Reset Statistics', SAM_PRO_DOMAIN); ?>
							</button>
						</p>
						<p><?php _e('Clearing statistics outside the keeping period. Use this button in case of problems with the automatic cleaning of the statistics table. The data within the keeping period specified by the plugin parameters will not be affected by this action.', SAM_PRO_DOMAIN); ?></p>
						<p><strong><?php _e('Remove Statistical Data', SAM_PRO_DOMAIN); ?></strong></p>
						<p>
							<button id="kill-em-all" name="KillEmAll" type="submit" class="button-secondary">
								<?php _e('Remove Statistics', SAM_PRO_DOMAIN); ?>
							</button>
						</p>
						<p><?php _e('Complete removal of all statistical data from the statistics table.', SAM_PRO_DOMAIN); ?></p>
					</div>
				</div>
			</div>
			<?php
		}
	}
}