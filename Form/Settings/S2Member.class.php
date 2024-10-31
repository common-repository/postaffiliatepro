<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_S2Member extends postaffiliatepro_Form_Base {
    const S2MEMBER_COMMISSION_ENABLED = 's2member-commission-enabled';
    const S2MEMBER_CONFIG_PAGE = 's2member-config-page';
    const S2MEMBER_CAMPAIGN = 's2member-campaign';
    const S2MEMBER_TRACK_RECURRING = 's2member-track-recurring';

    public function __construct() {
        parent::__construct(self::S2MEMBER_CONFIG_PAGE, 'options.php');
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::S2MEMBER_COMMISSION_ENABLED);
        register_setting(self::S2MEMBER_CONFIG_PAGE, self::S2MEMBER_CAMPAIGN);
        register_setting(self::S2MEMBER_CONFIG_PAGE, self::S2MEMBER_TRACK_RECURRING);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(postaffiliatepro_Form_Settings_S2Member::S2MEMBER_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('s2Member','pap-integrations'),
                __('s2Member','pap-integrations'),
                'manage_options',
                's2memberintegration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/S2MemberConfig.xtpl';
    }

    protected function initForm() {
        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(self::S2MEMBER_CAMPAIGN, $campaigns);
        $this->addCheckbox(self::S2MEMBER_TRACK_RECURRING);

        $this->addSubmit();
    }

    public function s2MemberPayPalButton($buttoncode, $params) {
        $buttoncode = str_replace('name="notify_url"', 'name="notify_url" id="pap_ab78y5t4a"', $buttoncode);
        
        $contentToAsync = 'PostAffTracker.setAccountId(\''.postaffiliatepro_Base::getAccountName().'\');
                 PostAffTracker.writeCookieToCustomField(\'pap_ab78y5t4a\', \'\', \'pap_custom\');';
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) != 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }
        
        return $buttoncode.$result;
    }

    public function s2MemberRecurringTracking($params) {
        // payment notification handling...
        $paypal = $_REQUEST;
		$this->_log('Received notification from Paypal: '.print_r($paypal, true));
		
		if ($paypal['txn_type'] != 'subscr_payment') {
			$this->_log('Paypal transaction type: '.$paypal['txn_type']);
			return;
		}

        $query = 'AccountId='.substr($_GET['pap_custom'],0,8). '&visitorId='.substr($_GET['pap_custom'],-32);
        $query .= '&TotalCost='.$paypal['mc_gross'].'&OrderID='.$paypal['txn_id'].'(1)';
        $query .= '&ProductID='.urlencode($paypal['item_name']);
        $query .= '&Currency='.$paypal['mc_currency'];
        $query .= '&Data1='.urlencode($paypal['payer_email']);

        if (get_option(self::S2MEMBER_CAMPAIGN) !== '' &&
                get_option(self::S2MEMBER_CAMPAIGN) !== null &&
                get_option(self::S2MEMBER_CAMPAIGN) !== 0 &&
                get_option(self::S2MEMBER_CAMPAIGN) !== '0') {
            $query .= '&CampaignID='.get_option(self::S2MEMBER_CAMPAIGN);
        }

        postaffiliatepro::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }
}

$submenuPriority = 40;
$integration = new postaffiliatepro_Form_Settings_S2Member();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_filter('ws_plugin__s2member_sc_paypal_button', array($integration, 's2MemberPayPalButton'), 99, 2);
add_action('ws_plugin__s2member_before_paypal_notify', array($integration, 's2MemberRecurringTracking'));