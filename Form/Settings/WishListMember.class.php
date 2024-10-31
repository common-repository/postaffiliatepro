<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_WishListMember extends postaffiliatepro_Form_Base {
    const WLM_COMMISSION_ENABLED = 'wlm-commission-enabled';
    const WLM_CONFIG_PAGE = 'wlm-config-page';
    const WLM_TRACK_RECURRING = 'wlm-track-recurring';
    const WLM_TRACK_REGISTRATION = 'wlm-track-registration';

    public function __construct() {
        parent::__construct(self::WLM_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WLMConfig.xtpl';
    }

    protected function initForm() {
        $this->addTextBox(self::WLM_TRACK_REGISTRATION, 30);
        $this->addCheckbox(self::WLM_TRACK_RECURRING);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WLM_COMMISSION_ENABLED);
        register_setting(self::WLM_CONFIG_PAGE, self::WLM_TRACK_REGISTRATION);
        register_setting(self::WLM_CONFIG_PAGE, self::WLM_TRACK_RECURRING);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::WLM_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('WishList Member','pap-integrations'),
                __('WishList Member','pap-integrations'),
                'manage_options',
                'wlm-settings-page',
                array($this, 'printConfigPage')
            );
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    public function WLMnewUserRegistration($message, WishListMemberPluginMethods $functions) {
        if (get_option(self::WLM_COMMISSION_ENABLED) !== 'true') {
            return $message;
        }
        $levels = new WLMAPIMethods();
        $levels = $levels->get_level($_POST['wpm_id']);

        $members = wlmapi_get_level_member_data($_POST['wpm_id'],$_POST['mergewith']);

        $message = str_replace('(wlmredirect,3000)', '(wlmredirect,5000)', $message);
        $message = str_replace('<meta http-equiv="refresh" content="3', '<meta http-equiv="refresh" content="5', $message);
        $message .= "<!-- Post Affiliate Pro sale tracking -->\n";
        $contentToAsync = "PostAffTracker.setAccountId('".self::getAccountName()."');\n
            var sale = PostAffTracker.createSale();\n
            sale.setProductID('".$levels['level']['name']."');\n
            sale.setOrderID('".$members['member']['level']->TxnID."');\n
            sale.setData1('".$_POST['email']."');\n";

        if (get_option(self::WLM_TRACK_REGISTRATION) != '') { // action code is set
            $contentToAsync .= "var action = PostAffTracker.createAction('".get_option(self::WLM_TRACK_REGISTRATION)."');\n
            action.setOrderID('".$_POST['firstname'].' '.$_POST['lastname']."');\n
            action.setData1('".$_POST['email']."');\n";
        }
        $contentToAsync .= "PostAffTracker.register();\n";
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) != 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }

        return $message.$result."<!-- /Post Affiliate Pro sale tracking -->\n";
    }

    public function WLMRecurringCommission() {
        if (get_option(self::WLM_COMMISSION_ENABLED) !== 'true') {
            return;
        }
        $orderId = $_POST['sctxnid'];

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
            return;
        }

        if ($orderId == '' || $orderId == null) {
            $this->_log(__('No order ID found! Recurring commission failed.'));
            return;
        }
        $this->fireRecurringCommissions($session, $orderId);
    }
}

$submenuPriority = 50;
$integration = new postaffiliatepro_Form_Settings_WishListMember();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_filter('wishlistmember_after_registration_page', array($integration, 'WLMnewUserRegistration'), 99, 2);
add_action('wlm_shoppingcart_rebill', array($integration, 'WLMRecurringCommission'));