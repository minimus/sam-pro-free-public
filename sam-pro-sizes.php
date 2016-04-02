<?php
/**
 * Created by PhpStorm.
 * Author: minimus
 * Date: 27.02.2015
 * Time: 9:10
 */

if( ! class_exists( 'SamProSizes' ) ) {
	class SamProSizes {
		public $size;
		public $name;
		public $width;
		public $height;

		private $aSizes;

		public function __construct( $value = '', $width = null, $height = null ) {
			$this->aSizes = array(
				'800x90' => sprintf('%1$s x %2$s %3$s', 800, 90, __('Large Leaderboard', SAM_PRO_DOMAIN)),
				'728x90' => sprintf('%1$s x %2$s %3$s', 728, 90, __('Leaderboard', SAM_PRO_DOMAIN)),
				'600x90' => sprintf('%1$s x %2$s %3$s', 600, 90, __('Small Leaderboard', SAM_PRO_DOMAIN)),
				'550x250' => sprintf('%1$s x %2$s %3$s', 550, 250, __('Mega Unit', SAM_PRO_DOMAIN)),
				'550x120' => sprintf('%1$s x %2$s %3$s', 550, 120, __('Small Leaderboard', SAM_PRO_DOMAIN)),
				'550x90' => sprintf('%1$s x %2$s %3$s', 550, 90, __('Small Leaderboard', SAM_PRO_DOMAIN)),
				'468x180' => sprintf('%1$s x %2$s %3$s', 468, 180, __('Tall Banner', SAM_PRO_DOMAIN)),
				'468x120' => sprintf('%1$s x %2$s %3$s', 468, 120, __('Tall Banner', SAM_PRO_DOMAIN)),
				'468x90' => sprintf('%1$s x %2$s %3$s', 468, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
				'468x60' => sprintf('%1$s x %2$s %3$s', 468, 60, __('Banner', SAM_PRO_DOMAIN)),
				'450x90' => sprintf('%1$s x %2$s %3$s', 450, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
				'430x90' => sprintf('%1$s x %2$s %3$s', 430, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
				'400x90' => sprintf('%1$s x %2$s %3$s', 400, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
				'234x60' => sprintf('%1$s x %2$s %3$s', 234, 60, __('Half Banner', SAM_PRO_DOMAIN)),
				'200x90' => sprintf('%1$s x %2$s %3$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN)),
				'150x50' => sprintf('%1$s x %2$s %3$s', 150, 50, __('Half Banner', SAM_PRO_DOMAIN)),
				'120x90' => sprintf('%1$s x %2$s %3$s', 120, 90, __('Button', SAM_PRO_DOMAIN)),
				'120x60' => sprintf('%1$s x %2$s %3$s', 120, 60, __('Button', SAM_PRO_DOMAIN)),
				'83x31' => sprintf('%1$s x %2$s %3$s', 83, 31, __('Micro Bar', SAM_PRO_DOMAIN)),
				'728x15x4' => sprintf('%1$s x %2$s %3$s, %4$s', 728, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'728x15x5' => sprintf('%1$s x %2$s %3$s, %4$s', 728, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
				'468x15x4' => sprintf('%1$s x %2$s %3$s, %4$s', 468, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'468x15x5' => sprintf('%1$s x %2$s %3$s, %4$s', 468, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
				'160x600' => sprintf('%1$s x %2$s %3$s', 160, 600, __('Wide Skyscraper', SAM_PRO_DOMAIN)),
				'120x600' => sprintf('%1$s x %2$s %3$s', 120, 600, __('Skyscraper', SAM_PRO_DOMAIN)),
				'200x360' => sprintf('%1$s x %2$s %3$s', 200, 360, __('Wide Half Banner', SAM_PRO_DOMAIN)),
				'240x400' => sprintf('%1$s x %2$s %3$s', 240, 400, __('Vertical Rectangle', SAM_PRO_DOMAIN)),
				'180x300' => sprintf('%1$s x %2$s %3$s', 180, 300, __('Tall Rectangle', SAM_PRO_DOMAIN)),
				'200x270' => sprintf('%1$s x %2$s %3$s', 200, 270, __('Tall Rectangle', SAM_PRO_DOMAIN)),
				'120x240' => sprintf('%1$s x %2$s %3$s', 120, 240, __('Vertical Banner', SAM_PRO_DOMAIN)),
				'336x280' => sprintf('%1$s x %2$s %3$s', 336, 280, __('Large Rectangle', SAM_PRO_DOMAIN)),
				'336x160' => sprintf('%1$s x %2$s %3$s', 336, 160, __('Wide Rectangle', SAM_PRO_DOMAIN)),
				'334x100' => sprintf('%1$s x %2$s %3$s', 334, 100, __('Wide Rectangle', SAM_PRO_DOMAIN)),
				'300x250' => sprintf('%1$s x %2$s %3$s', 300, 250, __('Medium Rectangle', SAM_PRO_DOMAIN)),
				'300x150' => sprintf('%1$s x %2$s %3$s', 300, 150, __('Small Wide Rectangle', SAM_PRO_DOMAIN)),
				'300x125' => sprintf('%1$s x %2$s %3$s', 300, 125, __('Small Wide Rectangle', SAM_PRO_DOMAIN)),
				'300x70' => sprintf('%1$s x %2$s %3$s', 300, 70, __('Mini Wide Rectangle', SAM_PRO_DOMAIN)),
				'250x250' => sprintf('%1$s x %2$s %3$s', 250, 250, __('Square', SAM_PRO_DOMAIN)),
				'200x200' => sprintf('%1$s x %2$s %3$s', 200, 200, __('Small Square', SAM_PRO_DOMAIN)),
				'200x180' => sprintf('%1$s x %2$s %3$s', 200, 180, __('Small Rectangle', SAM_PRO_DOMAIN)),
				'180x150' => sprintf('%1$s x %2$s %3$s', 180, 150, __('Small Rectangle', SAM_PRO_DOMAIN)),
				'160x160' => sprintf('%1$s x %2$s %3$s', 160, 160, __('Small Square', SAM_PRO_DOMAIN)),
				'125x125' => sprintf('%1$s x %2$s %3$s', 125, 125, __('Button', SAM_PRO_DOMAIN)),
				'200x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'200x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
				'180x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 180, 90, __('Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'180x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 180, 90, __('Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
				'160x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 160, 90, __('Tall Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'160x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 160, 90, __('Tall Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
				'120x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 120, 90, __('Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
				'120x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 120, 90, __('Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5))
			);

			self::setSize($value, $width, $height);
		}

		public function setSize( $value = '', $width = null, $height = null ) {
			if($value == '') {
				$this->size = '468x60';
				$this->name = __('Banner', SAM_PRO_DOMAIN);
				$this->width = 468;
				$this->height = 60;
			}
			elseif($value == 'custom') {
				$this->size = $value;
				$this->name = __('Custom size', SAM_PRO_DOMAIN);
				$this->width = $width;
				$this->height = $height;
			}
			else {
				$aSize = explode("x", $value);
				$this->size = $value;
				$this->name = $this->aSizes[$value];
				$this->width = $aSize[0];
				$this->height = $aSize[1];
			}
		}

		public function drawList() {
			$sizes = array(
				'horizontal' => array(
					'800x90' => sprintf('%1$s x %2$s %3$s', 800, 90, __('Large Leaderboard', SAM_PRO_DOMAIN)),
					'728x90' => sprintf('%1$s x %2$s %3$s', 728, 90, __('Leaderboard', SAM_PRO_DOMAIN)),
					'600x90' => sprintf('%1$s x %2$s %3$s', 600, 90, __('Small Leaderboard', SAM_PRO_DOMAIN)),
					'550x250' => sprintf('%1$s x %2$s %3$s', 550, 250, __('Mega Unit', SAM_PRO_DOMAIN)),
					'550x120' => sprintf('%1$s x %2$s %3$s', 550, 120, __('Small Leaderboard', SAM_PRO_DOMAIN)),
					'550x90' => sprintf('%1$s x %2$s %3$s', 550, 90, __('Small Leaderboard', SAM_PRO_DOMAIN)),
					'468x180' => sprintf('%1$s x %2$s %3$s', 468, 180, __('Tall Banner', SAM_PRO_DOMAIN)),
					'468x120' => sprintf('%1$s x %2$s %3$s', 468, 120, __('Tall Banner', SAM_PRO_DOMAIN)),
					'468x90' => sprintf('%1$s x %2$s %3$s', 468, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
					'468x60' => sprintf('%1$s x %2$s %3$s', 468, 60, __('Banner', SAM_PRO_DOMAIN)),
					'450x90' => sprintf('%1$s x %2$s %3$s', 450, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
					'430x90' => sprintf('%1$s x %2$s %3$s', 430, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
					'400x90' => sprintf('%1$s x %2$s %3$s', 400, 90, __('Tall Banner', SAM_PRO_DOMAIN)),
					'234x60' => sprintf('%1$s x %2$s %3$s', 234, 60, __('Half Banner', SAM_PRO_DOMAIN)),
					'200x90' => sprintf('%1$s x %2$s %3$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN)),
					'150x50' => sprintf('%1$s x %2$s %3$s', 150, 50, __('Half Banner', SAM_PRO_DOMAIN)),
					'120x90' => sprintf('%1$s x %2$s %3$s', 120, 90, __('Button', SAM_PRO_DOMAIN)),
					'120x60' => sprintf('%1$s x %2$s %3$s', 120, 60, __('Button', SAM_PRO_DOMAIN)),
					'83x31' => sprintf('%1$s x %2$s %3$s', 83, 31, __('Micro Bar', SAM_PRO_DOMAIN)),
					'728x15x4' => sprintf('%1$s x %2$s %3$s, %4$s', 728, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'728x15x5' => sprintf('%1$s x %2$s %3$s, %4$s', 728, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
					'468x15x4' => sprintf('%1$s x %2$s %3$s, %4$s', 468, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'468x15x5' => sprintf('%1$s x %2$s %3$s, %4$s', 468, 15, __('Thin Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5))
				),
				'vertical' => array(
					'160x600' => sprintf('%1$s x %2$s %3$s', 160, 600, __('Wide Skyscraper', SAM_PRO_DOMAIN)),
					'120x600' => sprintf('%1$s x %2$s %3$s', 120, 600, __('Skyscraper', SAM_PRO_DOMAIN)),
					'200x360' => sprintf('%1$s x %2$s %3$s', 200, 360, __('Wide Half Banner', SAM_PRO_DOMAIN)),
					'240x400' => sprintf('%1$s x %2$s %3$s', 240, 400, __('Vertical Rectangle', SAM_PRO_DOMAIN)),
					'180x300' => sprintf('%1$s x %2$s %3$s', 180, 300, __('Tall Rectangle', SAM_PRO_DOMAIN)),
					'200x270' => sprintf('%1$s x %2$s %3$s', 200, 270, __('Tall Rectangle', SAM_PRO_DOMAIN)),
					'120x240' => sprintf('%1$s x %2$s %3$s', 120, 240, __('Vertical Banner', SAM_PRO_DOMAIN))
				),
				'square' => array(
					'336x280' => sprintf('%1$s x %2$s %3$s', 336, 280, __('Large Rectangle', SAM_PRO_DOMAIN)),
					'336x160' => sprintf('%1$s x %2$s %3$s', 336, 160, __('Wide Rectangle', SAM_PRO_DOMAIN)),
					'334x100' => sprintf('%1$s x %2$s %3$s', 334, 100, __('Wide Rectangle', SAM_PRO_DOMAIN)),
					'300x250' => sprintf('%1$s x %2$s %3$s', 300, 250, __('Medium Rectangle', SAM_PRO_DOMAIN)),
					'300x150' => sprintf('%1$s x %2$s %3$s', 300, 150, __('Small Wide Rectangle', SAM_PRO_DOMAIN)),
					'300x125' => sprintf('%1$s x %2$s %3$s', 300, 125, __('Small Wide Rectangle', SAM_PRO_DOMAIN)),
					'300x70' => sprintf('%1$s x %2$s %3$s', 300, 70, __('Mini Wide Rectangle', SAM_PRO_DOMAIN)),
					'250x250' => sprintf('%1$s x %2$s %3$s', 250, 250, __('Square', SAM_PRO_DOMAIN)),
					'200x200' => sprintf('%1$s x %2$s %3$s', 200, 200, __('Small Square', SAM_PRO_DOMAIN)),
					'200x180' => sprintf('%1$s x %2$s %3$s', 200, 180, __('Small Rectangle', SAM_PRO_DOMAIN)),
					'180x150' => sprintf('%1$s x %2$s %3$s', 180, 150, __('Small Rectangle', SAM_PRO_DOMAIN)),
					'160x160' => sprintf('%1$s x %2$s %3$s', 160, 160, __('Small Square', SAM_PRO_DOMAIN)),
					'125x125' => sprintf('%1$s x %2$s %3$s', 125, 125, __('Button', SAM_PRO_DOMAIN)),
					'200x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'200x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 200, 90, __('Tall Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
					'180x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 180, 90, __('Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'180x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 180, 90, __('Half Banner', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
					'160x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 160, 90, __('Tall Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'160x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 160, 90, __('Tall Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5)),
					'120x90x4' => sprintf('%1$s x %2$s %3$s, %4$s', 120, 90, __('Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 4, SAM_PRO_DOMAIN), 4)),
					'120x90x5' => sprintf('%1$s x %2$s %3$s, %4$s', 120, 90, __('Button', SAM_PRO_DOMAIN), sprintf(_n('%d Link', '%d Links', 5, SAM_PRO_DOMAIN), 5))
				),
				'custom' => array( 'custom' => __('Custom sizes', SAM_PRO_DOMAIN) )
			);
			$sections = array(
				'horizontal' => __('Horizontal', SAM_PRO_DOMAIN),
				'vertical' => __('Vertical', SAM_PRO_DOMAIN),
				'square' => __('Square', SAM_PRO_DOMAIN),
				'custom' => __('Custom width and height', SAM_PRO_DOMAIN),
			);

			?>
			<select id="asize" name="asize">
				<?php
				foreach($sizes as $key => $value) {
					?>
					<optgroup label="<?php echo $sections[$key]; ?>">
						<?php
						foreach($value as $skey => $svalue) {
							?>
							<option value="<?php echo $skey; ?>" <?php selected($this->size, $skey); ?> ><?php echo $svalue; ?></option>
						<?php
						}
						?>
					</optgroup>
				<?php
				}
				?>
			</select>
		<?php
		}

		public function getSize() {
			return array(
				'name' => $this->name,
				'width' => $this->width,
				'height' => $this->height
			);
		}

		public function getSizes() {
			return $this->aSizes;
		}
	}
}