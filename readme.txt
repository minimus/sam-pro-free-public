=== SAM Pro (Free Edition) ===
Contributors: minimus
Donate link: http://uncle-sam.info
Tags: ad, advertising, banner, rotator, simple ads manager
Requires at least: 4.5
Tested up to: 4.8
Stable tag: 2.3.3.87
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ads management system for Wordpress

== Description ==

SAM Pro (Free Edition) is an easy to use plugin that provides the flexible logic of display advertisements. The successor of the [Simple Ads Manager](https://wordpress.org/plugins/simple-ads-manager/) plugin.

= Features =

* Flexible logic of the banners rotation based on the ad weight and the restrictions
* Ad rotation by page refresh
* Scheduling and limitations by impressions/clicks.
* Restrictions by the site page types.
* Restrictions by posts/pages ids, categories, tags, custom taxonomies terms, custom post types.
* The automatic embedding of ads into the content of posts/pages.
* Any type of ads (Image, Flash, JS, PHP, etc.) supported
* Outputting using widgets, shortcodes and functions is available.
* Customizable accuracy of the bots and crawlers detection
* Full statistics of the impressions and clicks
* Safe data migration from the Simple Ads Manager plugin.
* Google DoubleClick for Publishers (DFP) supported (GPT and GAM)
* Any caching plugins supported
* bbPress supported
* Wptouch supported
* Localization ready
* Addons API

The public version of SAM Pro (Free Edition) also available at [GitHub](https://github.com/minimus/sam-pro-free-public). You can fork it and make your own improvements.

= Requirements =

**Server**:

* PHP 5.3.3+
* MySQL 5.1+
* 128 MB memory limit
* 128 MB Wordpress memory limit

**Admin Client Side**:

* At least 2 Mbit data transfer rate
* Any Modern Browser

**Visitor Client Side**:

* No limits

= Available languages =
* English
* Russian

= Available Addons =
* [XAds](http://uncle-sam.info/addons/xads/) - This addon provides extended visualization of ads served by SAM Pro (Lite and Free edition). It adds possibility of displaying ads as popup ads, fly-in ads, background ads and corner ads. All single Ad Objects (Ad, Place, Zone) can be used as content of XAds addon.
* [Ad Slider](http://uncle-sam.info/addons/ad-slider/) - This addon adds to SAM Pro (Free Edition) possibility of rotating ads by the timer.
* [Advertising Request](http://uncle-sam.info/addons/advertising-request/) - This addon adds to Sam Pro plugin possibility of creating pages of requesting of advertising on the site from potential advertisers.
* [Geo Targeting](http://uncle-sam.info/addons/geo-targeting/) - Using this addon you can restrict showing of your ads basing on global location of the visitor.

== Installation ==

**By FTP**

1. Unzip the downloaded `sam-pro-free.zip` file into the suitable folder.
1. Log into your hosting space via an FTP software.
1. Upload the extracted SAM Pro Free plugin folder (sam-pro-free) into `wp-content/plugins` folder on hosting. Correct path after upload will be `wp-content/plugins/sam-pro-free`
1. Activate the installed plugin.

**By WordPress**

1. Navigate to Plugins > Add New
1. Enter “SAM Pro (Free Edition)” at the search field than press Enter
1. Find SAM Pro (Free Edition) in the plugins grid and click “Install Now”
1. Activate the installed plugin

More info about plugin installation on the [project site](http://uncle-sam.info/sam-pro-free/sam-pro-free-docs/installation-2/)...

== Frequently Asked Questions ==

= How to transfer data from the Simple Ads Manager plugin into the SAM Pro plugin? =

If you use the plugin Simple Ads Manager, you can migrate all the data from this plugin into the SAM Pro Free plugin.

The data structure of plugin Simple Ads Manager is different from the data structure of plugin SAM Pro Free, thus you need to use [plugin's tool](http://uncle-sam.info/sam-pro-free/sam-pro-free-docs/tools-2/) for safe migration of data.

== Screenshots ==

1. Settings. Tab "General"
1. Settings. Tab "Embedding"
1. Settings. Tab "Mailer"
1. Settings. Tab "Tools"
1. Ads List
1. Ad Editor
1. Place Editor
1. Zone Editor
1. Block Editor
1. Statistics
1. Tools

== Changelog ==

= 2.3.3.87 =
* Some minor bugs have been resolved
= 2.3.2.85 =
* The bug of the Link Ads action has been resolved
= 2.3.1.84 =
* The bug of using OpenSSL has been resolved
= 2.3.0.83 =
* Deprecated methods of Mcrypt have been replaced with OpenSSL methods (PHP 7.0 warnings)
= 2.2.0.81 =
* Visual representation of ads has been improved
* Some bugs in JS scripts have been resolved
= 2.0.1.76 =
* The bug (unlogged user) has been resolved
= 2.0.0.75 =
* New functions to main plugin object have been added
* Some bugs have been fixed
= 1.9.9.73 =
* The bug has been fixed
= 1.9.9.72 =
* Plugin API has been improved
* Some bugs have been fixed
= 1.9.8.70 =
* Some improvements on admin side
= 1.9.7.69 =
* Some changes for preventing **Local File Inclusion (LFI)** vulnerability have been made
= 1.9.6.67 =
* Bugs of the rules for the tags and the homepage have been fixed
* The bug of the interface on the statistics page in Google Chrome has been fixed
= 1.9.5.65 =
* The problem with the empty dates has been solved
* The minor bug of localization has been solved
* The third-party library has been upgraded
= 1.9.4.62 =
* The Statistics Chart has been improved
* The Settings Page has been improved
* The third-party software has been updated
= 1.9.3.59 =
* Some bugs are fixed
= 1.9.2.57 =
* The bug on advertiser list is fixed
* The bug on the statistics page is fixed
= 1.9.1.55 =
* Possible vulnerability was excluded
* Addons API improved
= 1.8.2.51 =
* Ads request builder is improved
* Checking of the plugin's DB tables is improved
= 1.8.1.49 =
* The bug of transient data with some date formats resolved
* The bug of outputting ads resolved
= 1.8.0.47 =
* Inline ads added
* Custom Taxonomies Terms Restrictions for pages added
* Preventing ad blockers improved
= 1.7.0.44 =
* Added support for Google AdSense page-level ads for mobile devices.
= 1.6.3.43 =
* Addons API improved
* Some bugs fixed
= 1.6.2.41 =
* The bug of changing maintenance date fixed
* Interface improved
= 1.6.1.39 =
* Some interface improvements
* Several minor bugs fixed
= 1.6.0.37 =
* Ready for Wordpress 4.5
= 1.5.6.36 =
* Minor bug resolved
= 1.5.5.35 =
* Data grids (admin side) navigation improved.
= 1.5.4.34 =
* Minor bugs resolved
= 1.5.3.32 =
* The bug of the showing Zone as item of Block is resolved.
* Disabling ad serving on a page added
= 1.5.2.30 =
* Statistics for individual ad is added
* Made some improvements
= 1.5.1.28 =
* Delete confirmation dialog added
= 1.5.0.27 =
* Addons API added
* Some minor bugs are fixed
= 1.4.2.25 =
* Made changes to work correctly under PHP 7
* Added filtering in the data grids
= 1.4.1.23 =
* Initial upload. Version is synchronized with other editions of the plugin.

== Upgrade Notice ==

= 2.3.3.87 =
Some minor bugs have been resolved.
= 2.3.2.85 =
The bug of the Link Ads action has been resolved.
= 2.3.1.84 =
The bug of using OpenSSL has been resolved.
= 2.3.0.83 =
Deprecated methods of Mcrypt have been replaced with OpenSSL methods (PHP 7.0 warnings)
= 2.2.0.81 =
Visual representation of ads has been improved. Some bugs in JS scripts have been resolved.
= 2.0.1.76 =
The bug (unlogged user) has been resolved.
= 2.0.0.75 =
New functions to main plugin object have been added. Some bugs have been fixed.
= 1.9.9.73 =
The bug has been fixed.
= 1.9.9.72 =
Plugin API has been improved. Some bugs have been fixed.
= 1.9.8.70 =
Some improvements on admin side.
= 1.9.7.69 =
Some changes for preventing Local File Inclusion (LFI) vulnerability have been made.
= 1.9.6.67 =
Bugs of the rules for the tags and the homepage have been fixed. The bug of the interface on the statistics page in Google Chrome has been fixed.
= 1.9.5.65 =
The problem with the empty dates has been solved. The minor bug of localization has been solved. The third-party library has been upgraded
= 1.9.4.62 =
* The Statistics Chart has been improved. The Settings Page has been improved. The third-party software has been updated.
= 1.9.3.59 =
Some bugs are fixed.
= 1.9.2.57 =
The bug on advertiser list is fixed. The bug on the statistics page is fixed.
= 1.9.1.55 =
Possible vulnerability was excluded. Addons API improved.
= 1.8.1.49 =
The bug of transient data with some date formats resolved. The bug of outputting ads resolved.
= 1.8.0.47 =
Inline ads added. Custom Taxonomies Terms Restrictions for pages added. Preventing ad blockers improved.
= 1.7.0.44 =
Added support for Google AdSense page-level ads for mobile devices.
= 1.6.3.43 =
Addons API improved. Some bugs fixed.
= 1.6.2.41 =
The bug of changing maintenance date fixed. Interface improved.
= 1.6.1.39 =
Some interface improvements. Several minor bugs fixed.
= 1.6.0.37 =
Ready for Wordpress 4.5.
= 1.5.6.36 =
Minor bug resolved.
= 1.5.5.35 =
Data grids navigation improved.
= 1.5.4.34 =
Minor bugs resolved.
= 1.5.3.32 =
The bug of the showing Zone as item of Block is resolved. Disabling ad serving on a page added.
= 1.5.2.30 =
Statistics for individual ad is added. Made some improvements.
= 1.5.1.28 =
Delete confirmation dialog added
= 1.5.0.27 =
Addons API added. Some minor bugs are fixed.
= 1.4.2.25 =
Made changes to work correctly under PHP 7. Added filtering in the data grids.
= 1.4.1.23 =
Initial upload.
