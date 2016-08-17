<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 26.07.2015
 * Time: 19:04
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( ! class_exists( 'SamProAdvertisersList' ) ) {
	class SamProAdvertisersList {
		private $settings;
		private $owner;
		private $force;
		private $mailSent = false;

		public function __construct( $settings ) {
			$this->settings = $settings;
			$this->force = isset($_POST['mail_to']);
			$this->owner = ($this->force) ? $_POST['mail_to'] : '';
		}

		public function show() {
			if($this->force) {
				include_once('tools/sam-pro-mailer.php');
				$mailer = new SamProMailer($this->settings);
				$this->mailSent = $mailer->sendMail($this->owner);
			}
			?>
			<div class="wrap">
				<h2><?php _e( 'Advertisers', SAM_PRO_DOMAIN ); ?></h2>
				<?php
				if($this->mailSent) {
					$message = __('Mail successfully sent.', SAM_PRO_DOMAIN);
					echo "<div class='updated'><p>{$message}</p></div>";
				}
				elseif($this->force) {
					$message = __('An error has occurred. Mail not sent.', SAM_PRO_DOMAIN);
					echo "<div class='error'><p>{$message}</p></div>";
				}
				?>
				<form id="stats" name="stats" method="post" action="<?php echo admin_url('admin.php') . '?page=sam-pro-statistics'; ?>">
					<input id="owner" name="owner" type="hidden" value="">
				</form>
				<form id="send_mail" name="send_mail" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
					<input id="mail_to" name="mail_to" type="hidden" value="">
				</form>
				<div id="grid"></div>
			</div>
			<?php
		}
	}
}