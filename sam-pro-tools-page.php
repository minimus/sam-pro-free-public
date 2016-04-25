<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 05.07.2015
 * Time: 8:45
 */

if( ! class_exists( 'SamProToolsPage' ) ) {
	class SamProToolsPage {
		private $migrate;
		private $migrateData;
		private $migrateOptions;
		private $settings;
		private $clearTables;
		private $migrateStats;

		private $clearStats;
		private $killEmAll;

		public function __construct( $settings ) {
			$this->settings = $settings;

			$this->migrate = isset($_POST['Migrate']);
			$this->migrateOptions = (isset($_POST['migrate_options'])) ? $_POST['migrate_options'] : 0;
			$this->migrateData = (isset($_POST['migrate_data'])) ? $_POST['migrate_data'] : 0;
			$this->migrateStats = (isset($_POST['migrate_stats'])) ? $_POST['migrate_stats'] : 0;
			$this->clearTables = (isset($_POST['clear_tables']) ? $_POST['clear_tables'] : 0);

			$this->clearStats = isset($_POST['ClearStats']);
			$this->killEmAll = isset($_POST['KillEmAll']);
		}

		public function show() {
			global $wpdb, $wp_version;

			$row = $wpdb->get_row('SELECT VERSION() AS ver', ARRAY_A);
			$sqlVersion = $row['ver'];
			$mem = ini_get('memory_limit');
			$phpStyle = ((int)$mem < 128) ? 'red' : 'green';
			$exeTime = ini_get('max_execution_time');
			$timeStyle = ((int)$exeTime < 30) ? 'red' : ( ((int)$exeTime < 50) ? 'orange' : 'green' );
			$wpMem = WP_MEMORY_LIMIT;
			$wpStyle = ((int)$wpMem < 128) ? 'red' : 'green';
			$edition = 0;
			$editions = array('Free', 'Lite', 'Full', 'Ultimate');
			?>
<div class="wrap">
	<h1><?php  _e('SAM Tools', SAM_PRO_DOMAIN); ?></h1>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="side-info-column" class="inner-sidebar">
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('System Info', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<p>
							<?php
							echo __('SAM Pro Edition', SAM_PRO_DOMAIN).': <strong>'.$editions[$edition].'</strong><br>';
							echo __('SAM Pro Version', SAM_PRO_DOMAIN).': <strong>'.SAM_PRO_VERSION.'</strong><br>';
							echo __('SAM Pro DB Version', SAM_PRO_DOMAIN).': <strong>'.SAM_PRO_DB_VERSION.'</strong><br>';
							echo __('Wordpress Version', SAM_PRO_DOMAIN).': <strong>'.$wp_version.'</strong><br>';
							echo __('PHP Version', SAM_PRO_DOMAIN).': <strong>'.PHP_VERSION.'</strong><br>';
							echo __('MySQL Version', SAM_PRO_DOMAIN).': <strong>'.$sqlVersion.'</strong><br>';
							echo __('PHP Max Execution Time', SAM_PRO_DOMAIN).": <strong style='color:{$timeStyle};'>{$exeTime}</strong><br>";
							echo __('PHP Memory Limit', SAM_PRO_DOMAIN).": <strong style='color:{$phpStyle};'>{$mem}</strong><br>";
							echo __('WP Memory Limit', SAM_PRO_DOMAIN).": <strong style='color:{$wpStyle};'>{$wpMem}</strong>";
							?>
						</p>
						<?php
						global $samProAddonsList;

						if(!empty($samProAddonsList)) {
							$strAddons = __('Addons', SAM_PRO_DOMAIN) . ':';
							echo "<p><strong>{$strAddons}</strong><ul style='list-style: inherit !important;margin-left: 20px;'>";
							foreach($samProAddonsList as $addon) {
								$aVersion = (isset($addon['version'])) ? "({$addon['version']})" : '';
								echo "<li>{$addon['name']} {$aVersion}</li>";
							}
							echo "</ul></p>";
						}
						?>
						<p>
							<?php _e('Note! If you have detected a bug, include this data to bug report.', SAM_PRO_DOMAIN); ?>
						</p>
					</div>
				</div>
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('SAM Pro (Lite Edition)', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<a href="http://codecanyon.net/item/sam-pro-lite/12721925?ref=minimus_simplelib" target="_blank">
							<img src="<?php echo SAM_PRO_URL . 'images/upgrade-sidebar.jpg'; ?>">
						</a>
						<p><?php _e('Get more features:', SAM_PRO_DOMAIN); ?></p>
						<ul style="list-style: inherit !important;margin-left: 20px;">
							<li><?php _e("ads rotation by timer", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("online advertiser statistics", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("an advertising request form", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("geo targeting", SAM_PRO_DOMAIN); ?></li>
							<li><?php _e("extended ALE (Ads Linking and Embedding)", SAM_PRO_DOMAIN); ?></li>
						</ul>
						<p><?php _e('and', SAM_PRO_DOMAIN); ?> <a href="http://uncle-sam.info/sam-pro-lite/sam-pro-lite-info/features/" target="_blank" title="<?php _e('Features List', SAM_PRO_DOMAIN); ?>"><?php _ex('more', 'SAM Pro Lite', SAM_PRO_DOMAIN); ?></a> ...</p>
						<p style="text-align: center;">
							<a href="http://codecanyon.net/item/sam-pro-lite/12721925?ref=minimus_simplelib" target="_blank" class="button-primary" style="width: 100%;">
								<?php _e('Purchase SAM Pro (Lite Edition)', SAM_PRO_DOMAIN); ?>
							</a>
						</p>
					</div>
				</div>
				<div class="postbox opened">
					<h3 class="hndle"><?php _e('Available Addons', SAM_PRO_DOMAIN); ?></h3>
					<div class="inside">
						<ul>
							<li>
								<a href="http://uncle-sam.info/addons/advertising-request/" target="_blank">
									<img src="<?php echo SAM_PRO_URL . 'images/ad-request-plugin-ad.jpg'; ?>">
								</a>
							</li>
							<li>
								<a href="http://uncle-sam.info/addons/geo-targeting/" target="_blank">
									<img src="<?php echo SAM_PRO_URL . 'images/geo-targeting-plugin-ad.jpg'; ?>">
								</a>
							</li>
						</ul>
					</div>
				</div>
				<div class='postbox opened'>
					<h3 class="hndle"><?php _e('Resources', SAM_PRO_DOMAIN) ?></h3>
					<div class="inside">
						<ul>
							<li><a target='_blank' href='http://uncle-sam.info'><?php _e("SAM Pro Site", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target="_blank" href="http://uncle-sam.info/sam-pro/getting-started/"><?php _e('Getting Started', SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://uncle-sam.info/category/sam-pro-free/sam-pro-free-docs/'><?php _e("Documentation", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://forum.simplelib.com/index.php?forums/sam-pro-free-edition.21/'><?php _e("Support Forum", SAM_PRO_DOMAIN); ?></a></li>
							<li><a target='_blank' href='http://www.simplelib.com/'><?php _e("Author's Blog", SAM_PRO_DOMAIN); ?></a></li>
						</ul>
					</div>
				</div>
			</div>
			<div id="post-body">
				<div id="post-body-content">
			<?php
			include_once( 'tools/sam-pro-migrate.php' );
			$updater = new SamProMigrate($this->settings, $this->migrateOptions, $this->migrateData, $this->migrateStats);
			$result = true;
			if($this->migrate) $result = $updater->migrate($this->clearTables);
			$updater->show($this->migrate, !$result);

			include_once('tools/sam-pro-stats-tools.php');
			$statsTools = new SamProStatisticsCleaner($this->settings);
			$stats = null;
			if($this->clearStats) $stats = $statsTools->clear();
			if($this->killEmAll) $stats = $statsTools->kill();
			$statsTools->show($stats);

			do_action('sam_pro_admin_tools_page');
			?>
				</div>
			</div>
		</div>
	</form>
</div>
		<?php
		}
	}
}