=== Post Affiliate Pro ===
Contributors: jurajsim
Tags: affiliate marketing, pap, post affiliate pro, qualityunit, affiliate
Requires at least: 3.0.0
Tested up to: 6.4
Stable tag: 1.26.11

This plugin integrates Post Affiliate Pro software into any WordPress installation. Post Affiliate Pro is the leading affiliate tracking tool with more than 27,000 active customers worldwide.

== Description ==

This plugin integrates Post Affiliate Pro - [affiliate software](https://www.postaffiliatepro.com/#wordpress "Affiliate software") into any WordPress installation.
Post Affiliate Pro is an award-winning affiliate software with complete set of affiliate marketing features. Sell more with the [best affiliate software](https://www.postaffiliatepro.com/best-affiliate-software/#wordpress "best affiliate software").Post Affiliate Pro is the most Reviewed and #1 Rated affiliate software based on 300+ reviews on 3 independent software marketplaces & review platforms.
You can rely on bullet-proof click/sale tracking technology, which combines multiple tracking methods into one powerful tracking system.

= Promotional video =
[vimeo http://vimeo.com/26098217]
You can find more info about Post Affiliate Pro [here](https://www.postaffiliatepro.com/#wordpress "Affiliate software"). 

Supported features:

*   Integrates WordPress user signup with Post Affiliate Pro signup
*   Integrates Post Affiliate Pro click tracking into WordPress
*   Includes Top affiliates widget with basic affiliate statistics
*   Shortcodes for affiliates
*   Integration with Accept Stripe Payments
*   Integration with Contact Form 7 (http://contactform7.com/) till version 3 (not included)
*   Integration with Easy Digital Downloads
*   Integration with LifterLMS
*   Integration with Marketpress
*   Integration with MemberPress
*   Integration with Membership 2 Pro
*   Integration with Post Membership Pro
*   Integration with Restrict Content Pro
*   Integration with WishList Member
*   Integration with WooCommerce
*   Integration with WooCommerce Autoship
*   Integration with WooCommerce Subscriptions
*   Integration with WooCommerce RevCent
*   Integration with WP EasyCart
*   Integration with WPPayForms
*   Integration with WP Simple Pay Pro
*   Also works with S2Member and Stripe Payments plugin

== Installation ==

1. Navigate to Plugins section in your WordPress admin panel and use the Add New button at top of the page
2. Search for Post Affiliate Pro
3. Install and activate Post Affiliate Pro plugin
4. Navigate to a new Post Affiliate Pro section in your main menu and configure the plugin

Note: If the plugin does not show up, login to you Post Affiliate Pro merchant panel and navigate to main menu Tools> Integration> API Integration
Download the API file from there by clicking the 'Download PAP API' link
Upload PapApi.class.php file to your plugin directory /wp-content/plugins/postaffiliatepro
Refresh your WP admin panel

== Frequently Asked Questions ==

= Q: After update, all menus are gone and plugin is not working at all =
A: In situation like this you should check your PapApi.class.php file in your PostAffiliatePro plugin directory.
In most of the cases, this file was missing after update and that caused plugin malfunction.
Without PapApi.class.php file plugin can not operate correctly and because of that it disables itself to prevent
damaging the main pages with error or warning messages etc.

= What is Post Affiliate Pro? =

Post Affiliate Pro is an award-winning affiliate tracking software designed to empower or establish in-house affiliate program.
For more info check out [this page](href='https://www.postaffiliatepro.com/#wordpress "Affiliate software")

= Can Post Affiliate Pro user use same password as in WordPress? =

No. This is not possible at the moment. Passwords will be always different.

= How can I use affiliate shortcode? =

Here are few examples of usage:

[affiliate item="name"/] - prints name of currently loaded affiliate.

[affiliate item="loginurl"/] - prints a button "Affiliate panel" that affiliate can use to log into their panel
 
[affiliate item="loginurl" caption="Log me in!" class="some-special-class"/] - this way you can set a caption and a CSS class for the login button

[affiliate item="loginurl_raw"/] - prints an HTML link, you can also define caption and class the same way you can do it with 'loginurl'

[affiliate item="OTHER_ATTRIBUTES"/] - prints other affiliate attributes. OTHER_ATTRIBUTES can be one of these items:

* userid - ID of user
* refid - user referral ID
* rstatus - user status
* minimumpayout - amount of the minimum payout for user
* payoutoptionid - ID of the payout option used by user
* note - user note
* photo - URL of user image
* username - username
* rpassword - user passwrod
* firstname - user first name
* lastname - user last name
* parentuserid - ID od parent user
* ip - user signup IP address
* notificationemail - user notification email
* data1 to data25 - user data fields

example of getting user notification email:

[affiliate item="notificationemail"]

It is possible to use a shortcode for affiliate commission balance. The syntax is:

[affiliate item="unpaidCommissions" timeframe="week"/] - prints last week's commission balance for the currently logged affiliate

You can get the same values of affiliate parent, instead of 'affiliate' shortcode call 'parent' and then the needed item. E.g. for parent name call this:
[parent item="name"/]

= Is it possible to integrate this plugin with s2Member? =
Yes it is. But keep in mind you should not use any mandatory fields in Post Affiliate Pro signup.
You have to use optional fields only.

= Is it possible to integrate this plugin with MagicMembers? =
Yes it is. But this feature is just experimental at this time.

= How to use the TopAffiliates widget? =
If you want to publicly display affiliate statistics in your WordPress, simply navigate to Appearance> Widgets section and add the Top Affiliates widget. When added, you can configure how many affiliates should be included in the result, set which value to use for ordering and you can also define the template of the result.
You can use these variables:
{$firstname}
{$lastname}
{$userid}
{$parentuserid}
{$clicksAll}
{$salesCount}
{$commissions}

== Screenshots ==

1. Plugin adds an extra menu to your WP installation
2. General options screen
3. Signup options screen
4. Click tracking options screen
5. Top affiliates widget config
6. You can also use shortcodes

== Changelog ==
= 1.26.11 =
* Optimized the process of changing commission status

= 1.26.10 =
* Security against XSS attacks increased

= 1.26.9 =
* Possible XSS attack on plugin config fields fixed
* minor design changes

= 1.26.8 =
* Affiliate info added to WooCommerce order metadata

= 1.26.7 =
* Request review notice added

= 1.26.6 =
* Problem with Account ID value saving fixed

= 1.26.5 =
* Contact Form 7 menu item fixed

= 1.26.4 =
* Zero order tracking enabled for Post Membership Pro plugin

= 1.26.3 =
* Error with WooCommerce sending 0 as variation ID fixed

= 1.26.2 =
* Error in Accept Stripe Payments integration fixed

= 1.26.1 =
* Integration with Accept Stripe Payments improved

= 1.26.0 =
* Post Membership Pro integration

= 1.25.1 =
* Fixed hiding of config of Simple Pay Pro when recurring tracking is enabled

= 1.25.0 =
* Plugin security level increased

= 1.24.9 =
* Minor code fixes

= 1.24.8 =
* Affiliate login shortcode reworked

= 1.24.7 =
* YITH Custom Thank You Page tracking fixed

= 1.24.6 =
* Asynchronous tracking with campaign ID fixed

= 1.24.5 =
* Affiliate commission shortcode added

= 1.24.4 =
* Forcing campaign for asynchronous click tracking truly fixed

= 1.24.3 =
* Plugin can cause fatal errors if specific query string parameters are in the URL address and WooCommerce plugin isn't installed

= 1.24.2 =
* WooCommerce offline per product tracking fixed

= 1.24.1 =
* Restrict Content Pro action tracking fixed

= 1.24.0 =
* Integration with Restrict Content Pro added
* Forcing campaign for tracking fixed

= 1.23.9 =
* WooCommerce integration - allow passing order note to extra data fields

= 1.23.8 =
* WooCommerce integration - allow passing customer's phone to extra data fields

= 1.23.6 =
* s2member integration improved
* Account Id added to requests where it was missing from

= 1.23.5 =
* improved work with IDs in WooCommerce integration

= 1.23.4 =
* Added support for Deposits & Partial Payments for WooCommerce
* Improved processing of partial refunds in WooCommerce

= 1.23.3 =
* Update for RevCent integration to prevent duplicate commissions

= 1.23.2 =
* Added support for the Custom Thank You Page For WooCommerce plugin

= 1.23.1 =
* Make sure WooCommerce RevCent is setting metadata as integer

= 1.23.0 =
* WooCommerce - added integration with RevCent

= 1.22.2 =
* WooCommerce - fix the option to deduct order fees, it should deduct also tax and shipping

= 1.22.1 =
* Easy Digital Downloads - added support for tracking of signup fees and recurring payments

= 1.22.0 =
* Added integration with LifterLMS

= 1.21.7 =
* WooCommerce integration supports Thanks Redirect 3.0

= 1.21.6 =
* WooCommerce integration now supports tags as product IDs
* an option added to deduct order fees in WooCommerce integration

= 1.21.5 =
* Contact form 7 option to choose product ID

= 1.21.4 =
* API connection improved

= 1.21.3 =
* added database update for WooCommerce order number setting

= 1.21.2 =
* WooCommerce order number can now be used as commission's order ID

= 1.21.1 =
* customer first and last name resolving improved for affiliate signup

= 1.21.0 =
* old API session replaced with the new version
* support for loginKeys added
* deprecated use of authToken removed
* minor display fixes

= 1.20.1 =
* asynchronous tracking error fixed

= 1.20.0 =
* added option for asynchronous tracking

= 1.19.17 =
* track WooCommerce orders placed through CartFlows

= 1.19.16 =
* code style

= 1.19.15 =
* removed default status in WooCommerce integration

= 1.19.14 =
* transaction loading API improvement

= 1.19.13 =
* added integration with WPPayForms

= 1.19.12 =
* finalize MemberPress fixes

= 1.19.11 =
* prevent javascript execution through Top Affiliates widget
* improve invalid login counter to try again after 15 minutes from the first invalid login to ensure that the plugin works after hosted Post Affiliate Pro account gets unsuspended 
* minor issues with MemberPress integration
* some typos fixed

= 1.19.10 =
* Coupons in data fields for WooCommerce integration

= 1.19.9 =
* Contact Form 7 minor fixes
* Top Affiliates widget can now display affiliate profile data fields

= 1.19.8 =
* log file is not accessible from web now
* log file is automatically deleted after debugging is disabled
* Contact Form 7 new options added

= 1.19.7 =
* fixed bug where plugin would say it is connected even with wrong credentials
* fixed session loop on Signup options screen
* fixed PHP warning when there was no session
* improvement to not try to connect to Post Affiliate Pro if the connection failed 5 times already. Setting correct credentials will reset the counter. 
* added prevention for XSS attacks

= 1.19.6 =
* session error fix

= 1.19.5 =
* fixed behavior when set credentials are wrong
* top affiliates widget improvement

= 1.19.4 =
* added 'recompute commission' button for WooCommerce integration (to support partial refunds)

= 1.19.3 =
* plugin's css won't affect other Wordpress content

= 1.19.2 =
* fixed problems with saving of plugin configuration on some Wordpress installations

= 1.19.1 =
* process all coupons instead of one
* added internal info about Post Affiliate Pro version
* order number added as an option for WooCommerce sale extra data

= 1.19.0 =
* extra data options in Memberpress integration

= 1.18.4 =
* minor fix to getting version of Post Affiliate Pro
* updated PapApi.class.php file

= 1.18.3 =
* improved integration with WP Simple Pay Pro
* added YITH Custom Thank You page support for WooCommerce integration

= 1.18.2 =
* replacement of deprecated function in WooCommerce integration
* sale tracking improved in WooCommerce integration

= 1.18.1 =
* WooCommerce PayPal PDT support

= 1.18.0 =
* Integration with WP EasyCart added

= 1.17.0 =
* Integration with Stripe Payments plugin added

= 1.16.6 =
* option to define what should be saved into Data fields in Contact Form 7 integration

= 1.16.5 =
* minor fix

= 1.16.4 =
* problem with WooCommerce PayPal orders fixed

= 1.16.3 =
* affiliate signup status fixed

= 1.16.2 =
* fixed bugs and replaced deprecated functions in WooCommerce integration

= 1.16.1 =
* per variation tracking option added to WooCommerce integration

= 1.16.0 =
* itegration with WooCommerce Autoship added
* shortcode fixes
* ContactForm7 form name fix

= 1.15.5 =
* improved the way how first and last name is recognized for new affiliates

= 1.15.4 =
* design change of Integrations section
* minor code fixes and improvements

= 1.15.3 =
* approval of commissions is faster now
* minor fixes
* affiliate creation improved

= 1.15.2 =
* JotForm integration removed

= 1.15.1 =
* Integration with PayPal Buy Now Button added

= 1.14.2 =
* Fixed bug in adding affiliate to campaign
* Integrations screen not loading fixed

= 1.14.0 =
* Integration with Membership 2 Pro added
* document.write function replaced

= 1.13.6 =
* Added support for recurring total cost (MemberPress, WooCommerce)

= 1.13.5 =
* ContactForm7 integration updated

= 1.13.4 =
* per product tracking improved for option delete cookie after sale

= 1.13.3 =
* coupons with PayPal fixed

= 1.13.2 =
* IP handling improved

= 1.13.1 =
* work with coupons in subscriptions fixed

= 1.13.0 =
* Now you can choose affiliate username for signup (email or wp username)
* commission status change logic improved

= 1.12.5 =
* minor code changes

= 1.12.4 =
* Affiliate signup logic changed to support merchant notifications
* TopAffiliates widget updated to fit API changes
* minor code changes

= 1.12.3 =
* hash script fixed
* minor fixes

= 1.12.2 =
* Debugging section fixes

= 1.12.1 =
* Debugging section added
* tracking hashed script enabled
* WooCommerce product name added as an option for products and extra data
* improved work with cookies for per product tracking when delete cookie after sale is used
* s2memebr recurring tracking fixed

= 1.11.2 =
* MemberPress hidden form field fix

= 1.11.1 =
* Affiliate approval on product ID for WooCommerce
* WooCommerce refund fix for subscriptions
* Minor bug fixes

= 1.10.4 =
* WooCommerce integration has 5 extra data fields now
* WooCommerce refund fix for subscriptions
* Improved affiliate signup

= 1.10.3 =
* WooCommerce category option for extra data fixed

= 1.10.2 =
* Support for currency added to Easy Digital Downloads integration
* WooCommerce coupons improvement (removal of deprecated functions)
* Compatibility test with WP 4.8

= 1.10.1 =
* Integration with Easy Digital Downloads added

= 1.9.4 =
* WooCommerce Subscriptions refund fix

= 1.9.3 =
* WooCommerce recurrent orders refund fix
* affiliate signup level option fix
* create an initial commission from recurring WooCommerce order fix

= 1.9.2 =
* WooCommerce recurrence fix

= 1.9.1 =
* affiliate update fix
* PHP error fixes

= 1.9.0 =
* s2member integration added
* a help section for shortcodes added
* added an option to save user level to a custom field
* improved affiliate details loading
* minor code changes

= 1.8.4 =
* update of affiliate details fix

= 1.8.3 =
* order status change of unprocessed transactions

= 1.8.2 =
* WishList Member tracking issue fixed

= 1.8.1 =
* function declaration warning fixed

= 1.8.0 =
* integration with Simple Pay Pro added
* WooCommerce product ID bug fixed
* refunding fixed
* external form library removed
* code revision to support PHP7
* minor bugfixes

= 1.7.0 =
* integration with MemberPress added
* integration with WishList Member added
* Options for product ID tracking in WooCommerce integration added

= 1.6.2 =
* added option to create an affiliate with a photo
* optimised WooCommerce refunds
* slightly redesigned

= 1.6.1 =
* IP address tracking feature added for WooCommerce PayPal orders

= 1.6.0 =
* integration with Marketpress added
* added a link to plugin to general setting
* menu changes
* wording corrections

= 1.5.7 =
* minor changes of code and wording

= 1.5.6 =
* WooCommerce with PayPal tracking fix

= 1.5.5 =
* WooCommerce sale tracking fix

= 1.5.4 =
* Campaign ID option added for WooCommerce click tracking
* Campaign ID option added for WooCommerce sale tracking
* WooCommerce Subscriptions recurrence tracking fix
* Missing account ID added to tracking codes
* Account ID setting moved to from click tracking section to general

= 1.5.3 =
* WooCommerce Subscriptions support added

= 1.5.2 =
* WooCommerce automatic protocol recognition added

= 1.5.1 =
* WooCommerce product tracking improved

= 1.5.0 =
* WooCommerce automatic sale tracking integration
* automatic parent affiliate recognition for signup tuned up

= 1.4.1 =
* set username of newly signed up WP user as Referral ID for his new affiliate account, unsupported characters are replaced with underscore

= 1.4.0 =
* Contact7 integration updated to the latest version
* JotForm configuration moved to a sub-page
* shortcodes for parent affiliates added

= 1.3.3 =
* a JotForm fields total cost bugfix

= 1.3.2 =
* TotalCost field added for JotForm
* a bugfix for dynamic JotForm fields

= 1.3.1 =
* TopAffiliates widget bug with hosted accounts fixed
* minor changes

= 1.3.0 =
* JotForm support with custom fields
* minor changes

= 1.2.33 =
* a bug fix for cases when buffering on server is disabled
* a plugin icon has been added
* tested WP compatibility up to 4.4.1

= 1.2.32 =
* add load of parentusreid in Top Affiliates, at least 5.3.28 version of Pap is needed

= 1.2.31 =
* fixed some PHP notifications

= 1.2.27 =
* fixed bug with duplicate mail if a setting is turn on in PAP and also in WP plugin
* fixed bug with Contact Form 7 when custom db prefix is used

= 1.2.26 =
* added item 'loginurl_raw' to affiliate shortcode for displaying url link

= 1.2.25 =
* fixed affiliate loading problem

= 1.2.24 =
* fixed some bugs dururing affiliate signup

= 1.2.22 =
* minor fixes and code refactoring

= 1.2.21 =
* fixed invisible shortcodes when affiliates do not use email names in PAP

= 1.2.20 =
* fixed bug with contact form 7: Call to a member function get_results() on a non-object in /home/bandi/public_html/sikeresemenyek.hu/wp-content/plugins/postaffiliatepro/Util/ContactForm7Helper.class.php on line 50

= 1.2.19 =
* fixed bug with shortcodes disappearing.

= 1.2.18 =
* tested compatibility with WP 3.5.1

= 1.2.17 =
* minor fixes

= 1.2.16 =
* fixed Contact form 7 form count handling 

= 1.2.15 =
* shortcodes descriptions fixing

= 1.2.14 =
* descriptions fixing

= 1.2.13 =
* shortcodes problmes fix

= 1.2.12 =
* just fixes typos in some texts
* minor code changes 

= 1.2.11 =
* change some texts

= 1.2.10 =
* just typos in some texts

= 1.2.9 =
* just typos in some texts

= 1.2.8 =
* bugfixes

= 1.2.7 =
* experimental support for Magic members Wordpress plugin

= 1.2.6 =
* readme.txt changed - small changes

= 1.2.5 =
* tested on WP 3.2.1

= 1.2.4 =
* screenshots update

= 1.2.3 =
* fixed some minor bugs
* just got report, that plugin works well with S2 member WordPress plugin

= 1.2.2 =
* add support for Contact form 7 integration

= 1.2.1 =
* small bugfixes 
* added chache for affialite login links urls

= 1.2.0 =
* add "affiliate" shortcode

= 1.1.5 =
* fixed critical error with broken shortcodes
* wp_content hook is not used anymore, plugin use wp_head instead

= 1.1.4 =
* fixed critical error with disappearing content

= 1.1.3 =
* fixed crash on plugin load: Warning: SimpleXMLElement::__construct() [simplexmlelement.--construct]: Entity: line 39: parser error : Opening and ending tag mismatch: ...

= 1.1.2 =
* minor bugfixes

= 1.1.1 =
* added possibility to insert newly created affiliate to private campaigns
* added support for click tracking integration
* added Top affiliates widget where you can see your top affiliates names, commissions, total costs etc. 
* signup and/or click tracking can now be enabled/disabled
* many internal chnages, code completly rewritten
* some minor bugs fixed

= 1.0.8 =
* corrected some spelling
* fixed non-functional signup dialog
* add option to send emails from pap when new affiliate signs-up

= 1.0.7 =
* bigfixes

= 1.0.6 =
* chnage menu possition from top to bottom

= 1.0.5 =
* added some more accurate descriptions to signup options form

= 1.0.4 =
* minor bugfixes

= 1.0.3 =
* Added suuport for default status for signing affiliates

= 1.0.2 =
* Fixed bug on signup option page when API file was not on place or out of date

= 1.0.1 =
* Add support to attach some concrete affiliate as parent for every new signed up user from wordpress.

== Upgrade Notice ==

* from 1.0.X to 1.1.X - you need to change path to your Post Afiliate Pro in general settings from http://www.yoursite.com/affiliate/scripts to http://www.yoursite.com/affiliate/ (remove directory 'script' at the end of url)
* other than that, there are no special requirements, just overwrite plugin files. All should work.

== Arbitrary section ==

If you have any thoughts how to make this plugin better, do not hasitate to leave your ideas in plugin forum, or write an email to support@postaffiliatepro.com.
