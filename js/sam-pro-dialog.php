<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 10.07.2015
 * Time: 14:09
 */

$wap = (isset($_REQUEST['wap'])) ? base64_decode($_REQUEST['wap']) : null;
$wpLoadPath = (is_null($wap)) ? false : $wap;
if(!$wpLoadPath) die('-1');
require_once($wpLoadPath);

function samProGetAdsData() {
	global $wpdb;
	$pTable = $wpdb->prefix . 'sampro_places';
	$aTable = $wpdb->prefix . 'sampro_ads';
	$zTable = $wpdb->prefix . 'sampro_zones';
	$bTable = $wpdb->prefix . 'sampro_blocks';

	$sql = "SELECT CONCAT(0, '_', pid) AS ival, title FROM {$pTable} WHERE trash = 0;";
	$places = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT CONCAT(1, '_', aid) AS ival, title FROM {$aTable} WHERE trash = 0;";
	$ads = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT CONCAT(2, '_', zid) AS ival, title FROM {$zTable} WHERE trash = 0;";
	$zones = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT CONCAT(3, '_', bid) AS ival, title FROM {$bTable} WHERE trash = 0;";
	$blocks = $wpdb->get_results($sql, ARRAY_A);

	return array(
		'places' => array(
			'title' => __('Places', SAM_PRO_DOMAIN),
			'data' => $places
		),
		'ads' => array(
			'title' => __('Ads', SAM_PRO_DOMAIN),
			'data' => $ads
		),
		'zones' => array(
			'title' => __('Zones', SAM_PRO_DOMAIN),
			'data' => $zones
		),
		'blocks' => array(
			'title' => __('Blocks', SAM_PRO_DOMAIN),
			'data' => $blocks
		)
	);
}

$ads = samProGetAdsData();
$url = get_option('siteurl') . '/wp-includes/js/tinymce/';
$jqUrl = get_option('siteurl') . '/wp-includes/js/jquery/';

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e('Insert Ad Object', SAM_PRO_DOMAIN); ?></title>
	<meta charset="<?php bloginfo('charset'); ?>">
	<script language="javascript" type="text/javascript" src="<?php echo $url; ?>tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $url; ?>utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $url; ?>utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $url; ?>utils/editable_selects.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $jqUrl; ?>jquery.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo SAM_PRO_URL ?>js/sam.pro.dialog.js"></script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';" style="display: none">
<form name="spb" onsubmit="insertSamProCode(jQuery);return false;" action="#">
	<div style="height: 220px;">
		<div>
			<div style="margin: 10px;">
				<label for="sam_id"><?php echo __('Ad Object', SAM_PRO_DOMAIN).':'; ?></label>
					<select name='sam_id' id='sam_id' style="font-size: 12px">
						<?php
						foreach($ads as $group) {
							echo "<optgroup label='{$group['title']}'>";
							foreach($group['data'] as $val) {
								echo "<option value='{$val['ival']}'>{$val['title']}</option>";
							}
							echo "</optgroup>";
						}
						?>
					</select>
			</div>
			<div style="margin: 10px;">
				<input type='checkbox' name='sam_codes' id='sam_codes' checked='checked'>
				<label for="sam_codes">
					<?php _e('Allow predefined tags', SAM_PRO_DOMAIN); ?>
				</label>
			</div>
		</div>
	</div>
	<div class="mceActionPanel" style="border-top: 1px solid #dfdfdf; padding: 8px 16px;">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", SAM_PRO_DOMAIN); ?>" onclick="tinyMCEPopup.close();" />
		</div>
		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php _e("Insert", SAM_PRO_DOMAIN); ?>" onclick="insertSamProCode(jQuery);" />
		</div>
	</div>
</form>
</body>
