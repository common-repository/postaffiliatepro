<?php
/*
 Plugin Name: Post Affiliate Pro
 Plugin URI: http://www.postaffiliatepro.com/
 Description: Easily integrate your WordPress site with your Post Affiliate Pro
 Author: QualityUnit
 Version: 1.26.11
 Author URI: http://www.qualityunit.com/
 License: GPL2
 */
if (!defined('PAP_PLUGIN_VERSION')) {
	define('PAP_PLUGIN_VERSION', '1.26.11');
}
if (!defined('PAP_PLUGIN_NAME')) {
    define('PAP_PLUGIN_NAME', 'postaffiliatepro');
}
include WP_PLUGIN_DIR . '/' . PAP_PLUGIN_NAME . '/Base.class.php';

if (!class_exists('postaffiliatepro')) {
    class postaffiliatepro extends postaffiliatepro_Base {
        const API_FILE = '/postaffiliatepro/PapApi.class.php';

        //configuration pages and settings
        //general page
        const GENERAL_SETTINGS_PAGE_NAME = 'pap_config_general_page';
        const PAP_URL_SETTING_NAME = 'pap-url';
        const PAP_MERCHANT_NAME_SETTING_NAME = 'pap-merchant-name';
        const PAP_MERCHANT_PASSWORD_SETTING_NAME = 'pap-merchant-password';
        const HASHED_TRACKING_SCRIPT = 'hashed-tracking-script';
        const PAP_VERSION = 'pap-version';
        const ASYNC_ENABLED = 'async-enabled';

        //signup options
        const SIGNUP_SETTINGS_PAGE_NAME = 'pap_config_signup_page';

        const SIGNUP_INTEGRATION_ENABLED_SETTING_NAME = 'pap-sugnup-integration-enabled';
        const SIGNUP_DEFAULT_PARENT_SETTING_NAME = 'pap-sugnup-default-parent';
        const SIGNUP_AFFILIATE_USERNAME = 'pap-sugnup-affiliate-username';
        const SIGNUP_DEFAULT_STATUS_SETTING_NAME = 'pap-sugnup-default-status';
        const SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME = 'pap-sugnup-campaigns-settings';
        const SIGNUP_INTEGRATION_USE_PHOTO = 'pap-sugnup-use-photo';
        const SIGNUP_INTEGRATION_SAVE_LEVEL = 'pap-sugnup-save-level';

        //click tracking integration page
        const CLICK_TRACKING_SETTINGS_PAGE_NAME = 'pap_config_click_tracking_page';

        const CLICK_TRACKING_ENABLED_SETTING_NAME = 'pap-click-tracking-enabled';
        const CLICK_TRACKING_ACCOUNT_SETTING_NAME = 'pap-click-tracking-account';
        const CLICK_TRACKING_CAMPAIGN = 'pap-click-tracking-capaign';

        const DEFAULT_ACCOUNT_NAME = 'default1';

        //specail integrations page
        const INTEGRATIONS_SETTINGS_PAGE_NAME = 'pap-integrations-config-page';

        public function __construct() {
            if (!$this->apiFileExists()) {
                return;
            }
            $this->includePapApiFile();
            $this->initUtils();
            $this->initForms();
            $this->initWidgets();
            $this->initPlugin();
            $this->initShortcodes();
        }

        private function initUtils() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/CampaignHelper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/TopAffiliatesHelper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/ContactForm7Helper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/UpdateDB.class.php';
        }

        private function initForms() {
            $path = WP_PLUGIN_DIR . '/postaffiliatepro/Form/';
            require_once $path.'Base.class.php';
            require_once $path.'Settings/General.class.php';
            require_once $path.'Settings/Signup.class.php';
            require_once $path.'Settings/AdditionalOptions.class.php';
            require_once $path.'Settings/Debugging.class.php';
            require_once $path.'Settings/Campaigns.class.php';
            require_once $path.'Settings/CampaignInfo.class.php';
            require_once $path.'Settings/ClickTracking.class.php';
            require_once $path.'Settings/EDD.class.php';
            require_once $path.'Settings/LifterLMS.class.php';
            require_once $path.'Settings/Integrations.class.php';
            require_once $path.'Settings/ContactForm7.class.php';
            require_once $path.'Settings/Marketpress.class.php';
            require_once $path.'Settings/MemberPress.class.php';
            require_once $path.'Settings/Membership2.class.php';
            require_once $path.'Settings/PMPro.class.php';
            require_once $path.'Settings/PPBuyNowButton.class.php';
            require_once $path.'Settings/RestrictContentPro.class.php';
            require_once $path.'Settings/S2Member.class.php';
            require_once $path.'Settings/SimplePayPro.class.php';
            require_once $path.'Settings/StripePayments.class.php';
            require_once $path.'Settings/WishListMember.class.php';
            require_once $path.'Settings/WooComm.class.php';
            require_once $path.'Settings/WPEasyCart.class.php';
            require_once $path.'Settings/WPPayForms.class.php';
        }

        private function initWidgets() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Widget/TopAffiliates.class.php';
        }

        private function initShortcodes() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Shortcode/Cache.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Shortcode/Affiliate.class.php';
        }

        private function initPlugin() {
            add_action('admin_init', array($this, 'initSettings'));
            add_filter('admin_head', array($this, 'initAdminHeader'), 99);
            add_action('admin_menu', array($this, 'addPrimaryConfigMenu'));
            add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'addSettingsLinkIntoPlugin'));
            add_action('user_register', array($this, 'onNewUserRegistration'), 99);
            add_action('mgm_user_register', array($this, 'onNewUserRegistration'), 99); //fix to work with magic members
            add_action('woocommerce_created_customer', array($this, 'onNewUserRegistrationWoo'), 99, 3); //fix to work with WooCommerce registration
            add_action('register_form', array($this, 'addHiddenFieldToRegistrationForm'));
            add_action('profile_update', array($this, 'onUpdateExistingUser'));
            add_filter('wp_footer', array($this, 'insertIntegrationCodeToFooter'), 99);
            add_action('upgrader_process_complete', array($this, 'afterUpdateMaintenance'), 99, 2);
        }

        private function includePapApiFile() {
            require_once WP_PLUGIN_DIR . self::API_FILE;
        }

        private function apiFileExists() {
            return @file_exists(WP_PLUGIN_DIR . self::API_FILE);
        }

        private function getPapIconURL() {
            return $this->getImgUrl() . 'menu-icon.png';
        }

        public function initSettings() {
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_URL_SETTING_NAME);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_MERCHANT_NAME_SETTING_NAME);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_MERCHANT_PASSWORD_SETTING_NAME);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_ACCOUNT_SETTING_NAME);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::HASHED_TRACKING_SCRIPT);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_VERSION);
            register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::ASYNC_ENABLED);

            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_DEFAULT_PARENT_SETTING_NAME);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_AFFILIATE_USERNAME);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_DEFAULT_STATUS_SETTING_NAME);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_INTEGRATION_USE_PHOTO);
            register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_INTEGRATION_SAVE_LEVEL);

            register_setting(self::CLICK_TRACKING_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_ENABLED_SETTING_NAME);
            register_setting(self::CLICK_TRACKING_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_CAMPAIGN);

            // ask for a review notice
            $this->askForReview();
        }

        public static function getPAPTrackJSDynamicCode() {
            $tracker = 'trackjs.js';
            if (self::isHashed()) {
                $tracker = get_option(self::HASHED_TRACKING_SCRIPT);
            }
            return '<script type="text/javascript" id="pap_x2s6df8d" src="//'.
                    self::parseServerPathForClickTrackingCode().'scripts/'.$tracker.'">
                </script>';
        }
        
        public static function getPAPTrackJSAsyncCode($content) {
            $tracker = 'trackjs.js';
            if (self::isHashed()) {
                $tracker = get_option(self::HASHED_TRACKING_SCRIPT);
            }
        
            $result = '<script type="text/javascript">
            		(function(d,t) {
    				var script = d.createElement(t); script.id= "pap_x2s6df8d"; script.async = true;
    				script.src = "//'.self::parseServerPathForClickTrackingCode().'scripts/'.$tracker.'";
    				script.onload = script.onreadystatechange = function() {
      				var rs = this.readyState; if (rs && (rs != "complete") && (rs != "loaded")) return;';
            $result .= $content . '}
    				var theBody = document.getElementsByTagName("body")[0];
					theBody.insertBefore(script, theBody.childNodes[0]);
					})(document, "script");</script>';
            return $result;
        }

        public static function parseServerPathForClickTrackingCode() {
            $url = str_replace ('https://', '', get_option(self::PAP_URL_SETTING_NAME));
            $url = str_replace ('http://', '', $url);
            if (substr($url,-1) != '/') {
                $url .= '/';
            }
            return $url;
        }

        public static function parseSaleScriptPath() {
            $tracker = 'sale.php';

            $url = str_replace('https://', 'http://', get_option(self::PAP_URL_SETTING_NAME));
            if (substr($url,-1) != '/') {
                $url .= '/';
            }
            if (self::isHashed()) {
                $tracker = str_replace('j', 's', get_option(self::HASHED_TRACKING_SCRIPT));
            }
            return $url.'scripts/'.$tracker;
        }

        public static function isHashed() {
            $hash = get_option(self::HASHED_TRACKING_SCRIPT);
            if ($hash != '' && $hash != '0') {
                return true;
            }
            return false;
        }

        public function insertIntegrationCodeToFooter($content) {
            // exit if feed
            if (is_feed()) {
                return false;
            }

            if (get_option(self::CLICK_TRACKING_ENABLED_SETTING_NAME) != 'true') {
                return $content;
            }

            $contentToAsync = 'PostAffTracker.setAccountId(\''.self::getAccountName().'\');';
        
            $campaigId = get_option(self::CLICK_TRACKING_CAMPAIGN);
            if (!empty($campaigId)) {
            	$contentToAsync .= "window.CampaignID = '$campaigId';\n";
            }
            
            $contentToAsync .= 'try {PostAffTracker.track();} catch (err) { }';
            
            if (get_option(self::ASYNC_ENABLED) != 'true') {
	            $result = self::getPAPTrackJSDynamicCode()."<script type='text/javascript'>\n";
	            $result .= $contentToAsync . "</script>\n";
            } else {
            	$result = self::getPAPTrackJSAsyncCode($contentToAsync);
            }

	        echo $result.$content;
        }

        public function afterUpdateMaintenance($upgrader_object, $options) {
            if (isset($options['translations']) && isset($options['translations']['version'])) {
                $this->_log('After "' . $options['type'] . '" update to version ' . $options['translations']['version']);
            }

            $this->getPapVersion();
        }

        private function resolveParentAffiliateFromCookie(Pap_Api_Session $session, Pap_Api_Affiliate $affiliate) {
            if (!empty($_REQUEST['pap_parent'])) {
                $affiliate->setParentUserId($_REQUEST['pap_parent']);
                $this->_log(__('Parent affiliate resolved from cookies: '.$_REQUEST['pap_parent']));
                return true;
            }

            $clickTracker = new Pap_Api_ClickTracker($session);
            try {
                $clickTracker->track();
            } catch (Exception $e) {
                $this->_log(__('Error running track:' . $e->getMessage()));
            }
            if ($clickTracker->getAffiliate() != null) {
                $affiliate->setParentUserId($clickTracker->getAffiliate()->getValue('userid'));
            } else {
                $this->_log(__('Parent affiliate not found from cookie'));
            }
        }

        private function resolveFirstAndLastName(WP_User $user, Pap_Api_Affiliate $affiliate) {
            $first_name = $user->first_name;
            $last_name = $user->last_name;
            if ($first_name == '') {
                foreach ($_POST as $key => $value) {
                    if ((strpos($key, 'first') !== false) && (strpos($key, 'name') !== false)) {
                        $first_name = $value;
                    }
                }
            }
            if ($first_name == '') {
                $first_name = 'empty';
            }
            
            if ($last_name == '') {
                foreach ($_POST as $key => $value) {
                    if ((strpos($key, 'last') !== false) && (strpos($key, 'name') !== false)) {
                        $last_name = $value;
                    }
                }
            }
            if ($last_name == '') {
                $last_name = 'empty';
            }
            
            $affiliate->setFirstname($first_name);
            $affiliate->setLastname($last_name);
        }

        /**
         * @return Pap_Api_Affiliate
         */
        private function initAffiliate(WP_User $user, Pap_Api_Session $session) {
            $affiliate = new Pap_Api_AffiliateSignup($session);
            $affiliate->setUsername($user->user_email);
            if (get_option(self::SIGNUP_AFFILIATE_USERNAME) == 'username') {
                $affiliate->setUsername($user->user_nicename);
            }

            $affiliate->setRefid($user->user_nicename);
            $this->resolveFirstAndLastName($user, $affiliate);
            $affiliate->setNotificationEmail($user->user_email);
            if (get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL) != '' && get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL) > 0) {
                $affiliate->setData(get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL), $user->user_level);
            }
            if (get_option(self::SIGNUP_INTEGRATION_USE_PHOTO) == 'true') {
                $affiliate->setPhoto(site_url('/avatar/user-'.$user->ID.'-96.png'));
                if (is_multisite()) {
                    $affiliate->setPhoto(network_site_url('/avatar/user-'.$user->ID.'-96.png'));
                }
            }
            return $affiliate;
        }

        private function setParentToAffiliate(Pap_Api_Affiliate $affiliate, Pap_Api_Session $session) {
            $parentSignup = get_option(self::SIGNUP_DEFAULT_PARENT_SETTING_NAME);
            if (!empty($parentSignup) && $parentSignup != 'from_cookie') {
                $affiliate->setParentUserId($parentSignup);
            }
            if ($parentSignup == 'from_cookie') {
                $this->resolveParentAffiliateFromCookie($session, $affiliate);
            }
        }

        private function setStatusToAffiliate($affiliateId, Pap_Api_Session $session) {
            $status = get_option(self::SIGNUP_DEFAULT_STATUS_SETTING_NAME);
            if (!empty($status)) {
                $request = new Gpf_Rpc_Request('Pap_Merchants_User_AffiliateForm', 'changeStatus', $session);
                $request->addParam('status', $status);
                $request->addParam('ids', new Gpf_Rpc_Array(array($affiliateId)));
                $request->addParam('dontSendNotification', 'N');
                try {
                    $request->sendNow();
                } catch(Exception $e) {
                    $this->_log('Affiliate status change after signup failed: '.$e->getMessage());
                    return false;
                }

                $response = $request->getStdResponse();

                if ($response->success != 'Y') {
                    $this->_log('Cannot change affiliate status after signup: '.$response->errorMessage);
                }
            }
            return true;
        }

        private function signupIntegrationEnabled() {
            return get_option(self::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME) == 'true';
        }

        public function onNewUserRegistrationWoo($user_id, $new_customer_data, $password_generated) {
            // user info should include first name and last name, we can call the standard function
            $this->onNewUserRegistration($user_id);
        }

        public function onNewUserRegistration($user_id) {
            if (!$this->signupIntegrationEnabled()) {
                $this->_log(__('Signup integration disabled - skipping new affiliate creation'));
                return;
            }
            $session = $this->getApiSession();
            if ($session === null || $session === '0') {
                $this->_log(__('We have no session to PAP installation! Registration of PAP user cancelled.'));
                return;
            }

            $wpuser = new WP_User($user_id);
            $affiliate = $this->initAffiliate($wpuser, $session);

            $this->setParentToAffiliate($affiliate, $session);
            $affiliate->setField('parentUserIdValidate', 'N');

            try {
                if ($affiliate->add()) {
                    $this->_log('Affiliate account for user '.$wpuser->user_email.' created successfully');

                    $this->setStatusToAffiliate($affiliate->getUserid(), $session);
                } else {
                    $this->_log('Cannot save affiliate: '.$affiliate->getMessage());
                    return;
                }
            } catch (Exception $e) {
                $this->_log(__('Error adding affiliate' . $e->getMessage()));
                return;
            }

            $this->processCampaigns($affiliate);
        }

        public static function addHiddenFieldToRegistrationForm() {
            if (get_option(self::PAP_URL_SETTING_NAME) == '') {
                return false;
            }
            echo '<input type="hidden" name="pap_parent" value="" id="pap_xa77cb50a" />';
            
            $contentToAsync = 'PostAffTracker.writeAffiliateToCustomField(\'pap_xa77cb50a\');';
            
            if (get_option(self::ASYNC_ENABLED) != 'true') {
                $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
                $result .= $contentToAsync . "</script>\n";
            } else {
                $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
            }
            
            echo $result;
        }

        public static function addHiddenFieldToPaymentForm($return = false) {
            $result = '<!-- Post Affiliate Pro integration snippet -->';
            $result .= '<input type="hidden" name="pap_IP" value="'.self::getRemoteIp().'" />';
            $result .= '<input type="hidden" name="pap_custom" value="" id="pap_dx8vc2s5" />';
            
            $contentToAsync = 'PostAffTracker.setAccountId(\''.self::getAccountName().'\');
                    PostAffTracker.notifySale();';
            
            if (get_option(self::ASYNC_ENABLED) != 'true') {
                $result .= postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
                $result .= $contentToAsync . "</script>\n";
            } else {
                $result .= postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
            }
            
            $result .= '<!-- /Post Affiliate Pro integration snippet -->';
                    
            if ($return) {
                return $result;
            } else {
                echo $result;
                return;
            }
        }

        public static function getRemoteIp() {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                $ipAddresses = explode(',', $ip);
                foreach ($ipAddresses as $ipAddress) {
                    $ipAddress = trim($ipAddress);
                    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ipAddress;
                    }
                }
            }
            if (isset($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
            }
            return '';
        }

        private function getCampaignOption($campaignId, $name) {
            $value = get_option(self::SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME);
            if (!is_array($value)) {
                return '';
            }
            if (!array_key_exists($name . '-' . $campaignId, $value)) {
                return '';
            }
            return $value[$name . '-' . $campaignId];
        }

        private function assignToCampaign(Pap_Api_Affiliate $affiliate, $campaignId, $sendNotification) {
            try {
                $affiliate->assignToPrivateCampaign($campaignId, ($sendNotification=='true')?true:false);
            } catch (Exception $e) {
                $this->_log('Unable to assign user to private camapign ' . $campaignId . ', problem: ' . $e->getMessage());
            }
        }

        private function processCampaigns(Pap_Api_Affiliate $affiliate) {
            $campaigns = $this->getCampaignHelper()->getCampaignsList();
            if ($campaigns === null) {
                return;
            }
            foreach ($campaigns as $campaign) {
                if ($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_TYPE) != postaffiliatepro_Util_CampaignHelper::CAMPAIGN_TYPE_PUBLIC) {
                    if ($this->getCampaignOption($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID), postaffiliatepro_Form_Settings_CampaignInfo::ADD_TO_CAMPAIGN) == 'true') {
                        $this->assignToCampaign($affiliate, $campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID),
                        $this->getCampaignOption($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID), postaffiliatepro_Form_Settings_CampaignInfo::SEND_NOTIFICATION_EMAIL));
                    }
                }
            }
        }

        public function onUpdateExistingUser($user_id) {
            if (!$this->signupIntegrationEnabled()) {
                $this->_log(__("Signup integration disabled - will not update existing affiliate"));
                return;
            }
            $session = $this->getApiSession();
            if ($session === null || $session === '0') {
                $this->_log(__("We have no session to PAP installation! Updating of PAP user cancelled."));
                return;
            }
            $user = new WP_User($user_id);
            $affiliate = new Pap_Api_Affiliate($session);
            $affiliate->setRefid($user->user_nicename, Pap_Api_Affiliate::OPERATOR_EQUALS);
            try {
                $affiliate->load();
            } catch (Exception $e) {
                // try it with notification email as well
                $this->_log(__('Unable to load affiliate').' '.__('by referral ID'));
                try {
                    $affiliate->setRefid('');
                    $affiliate->setNotificationEmail($user->user_email);
                    $affiliate->load();
                } catch (Exception $e) {
                    // last try - username
                    $this->_log(__('Unable to load affiliate').' '.__('by notification email'));
                    try {
                        $affiliate->setNotificationEmail('');
                        $affiliate->setUsername($user->user_email, Pap_Api_Affiliate::OPERATOR_EQUALS);
                        $affiliate->load();
                    } catch (Exception $e) {
                        $this->_log(__('Unable to load affiliate').' '.__('by username'));
                        $this->_log(__('Update of user %s cancelled', $user->nickname));
                        return;
                    }
                }
            }
            $this->resolveFirstAndLastName($user, $affiliate);
            $affiliate->setNotificationEmail($user->user_email);
            if (get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL) != '' && get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL) > 0) {
                $affiliate->setData(get_option(self::SIGNUP_INTEGRATION_SAVE_LEVEL), $user->user_level);
            }
            $affiliate->save();
        }

        public function initAdminHeader($content) {
            if (!is_feed()) {
                echo $this->getStylesheetHeaderLink('style.css');
            }
            echo $content;
        }

        public function addSettingsLinkIntoPlugin($links) {
            return array_merge($links, array('<a href="'.admin_url('admin.php?page=pap-top-level-options-handle').'">Settings</a>'));
        }

        private function askForReview() {
            $this->handleReviewStatus();
            $activationTime = get_site_option('pap_active_time');
            $reviewDismissed = get_site_option('pap_review_dismiss');

            if ($reviewDismissed == 'yes') return;

            $now = time();
            if (!$activationTime) {
                $activationTime = $now;
                add_site_option('pap_active_time', $now);
            }

            if ($now - $activationTime > 864000) { // 10 days
                add_action('admin_notices', array($this, 'reviewNoticeMessage'));
            }
        }

        private function handleReviewStatus() {
            if (!is_admin() || ! current_user_can('manage_options') || !isset( $_GET['_wpnonce']) ||
                !wp_verify_nonce(sanitize_key(wp_unslash($_GET['_wpnonce'])), 'pap-review-nonce')) {
                return;
            }

            if (isset($_GET['pap_review_later'])) {
                // set time to now
                update_site_option('pap_active_time', time());
            }

            if (isset($_GET['pap_review_dismiss'])) {
                add_site_option('pap_review_dismiss', 'yes');
            }
        }

        public function reviewNoticeMessage() {
            $scheme= (wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY))?'&':'?';
            $url = $_SERVER['REQUEST_URI'] . $scheme;
            $dismissUrl = wp_nonce_url($url . 'pap_review_dismiss=yes', 'pap-review-nonce');
            $laterUrl = wp_nonce_url($url . 'pap_review_later=yes', 'pap-review-nonce' );

            echo '<div class="pap-review-notice-wrapper">
                <div class="pap-review-notice">
                    <div class="pap-review-text">
                        <h2>We would like to know <span class="highlight">your opinion</span></h2>
                        <p>You\'ve been using <span class="highlight">Post Affiliate Pro WordPress Plugin</span> for some time. We hope all is working as supposed and the setup was smooth. Would you mind taking a few minutes to write a review on WordPress.org?<br />Any review works =) Thank you!</p>
                        <div class="reviewButton"><a href="https://wordpress.org/support/plugin/postaffiliatepro/reviews/?filter=5#new-post" target="_blank"><span class="dashicons dashicons-external"></span>Sure! I\'d love to!</a></div>
                        <div class="reviewButton"><a href="' . $dismissUrl .'"><span class="dashicons dashicons-smiley"></span>I\'ve already left a review</a></div>
                        <div class="reviewButton"><a href="' . $laterUrl .'"><span class="dashicons dashicons-calendar-alt"></span>Will rate it later</a></div>
                        <div class="reviewButton"><a href="' . $dismissUrl .'"><span class="dashicons dashicons-dismiss"></span>Hide this forever</a></div>
                        <div class="pap-review-help"><a href="https://www.postaffiliatepro.com/" target="_blank"><span class="dashicons dashicons-external"></span>Help! I have some technical issues.</a></div>
                    </div>
                </div>
            </div>';
        }

        public function addPrimaryConfigMenu() {
            $handle = 'pap-top-level-options-handle';
            add_menu_page(__('Post Affiliate Pro','pap-menu'), __('Post Affiliate Pro','pap-menu'), 'manage_options', $handle, array($this, 'printGeneralConfigPage'), $this->getPapIconURL());
            add_submenu_page($handle, __('General','pap-menu'), __('General','pap-menu'), 'manage_options', $handle, array($this, 'printGeneralConfigPage'));

            if (!$this->isPluginSet()) {
                return;
            }

            add_submenu_page($handle, __('Click tracking','pap-menu'), __('Click tracking','pap-menu'), 'manage_options', 'click-tracking-config-page', array($this, 'printClickTrackingConfigPage'));
            add_submenu_page($handle, __('Signup','pap-menu'), __('Signup options','pap-menu'), 'manage_options', 'signup-config-page', array($this, 'printSignupConfigPage'));
            add_submenu_page($handle, __('Additional options','pap-menu'), __('Additional options','pap-menu'), 'manage_options', 'additional-options-page', array($this, 'printAdditionalOptionsPage'));

            add_menu_page(__('Integrations','pap-integrations'), __('Integrations','pap-integrations'), 'manage_options', 'integrations-config-page-handle', array($this, 'printIntegrationsConfigPage'), $this->getPapIconURL());
            add_submenu_page('integrations-config-page-handle', __('General', 'pap-integrations'), __('General', 'pap-integrations'), 'manage_options', 'integrations-config-page-handle', array($this, 'printIntegrationsConfigPage'));
        }
        public function printGeneralConfigPage() {
            $form = new postaffiliatepro_Form_Settings_General();
            $form->render();
        }
        public function printSignupConfigPage() {
            $form = new postaffiliatepro_Form_Settings_Signup();
            $form->render();
            return;
        }
        public function printClickTrackingConfigPage() {
            $form = new postaffiliatepro_Form_Settings_ClickTracking();
            $form->render();
            return;
        }
        public function printAdditionalOptionsPage() {
            $form = new postaffiliatepro_Form_Settings_AdditionalOptions();
            $form->render();
            return;
        }
        public function printIntegrationsConfigPage() {
            $form = new postaffiliatepro_Form_Settings_Integrations();
            $form->render();
            return;
        }
    }
}

$postaffiliatepro = new postaffiliatepro();
