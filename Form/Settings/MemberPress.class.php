<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_MemberPress extends postaffiliatepro_Form_Base {
    const MEMBERPRESS_COMMISSION_ENABLED = 'memberpress-commission-enabled';
    const MEMBERPRESS_CONFIG_PAGE = 'memberpress-config-page';
    const MEMBERPRESS_TRACK_RECURRING = 'memberpress-track-refurring';
    const MEMBERPRESS_TRACK_RECURRING_TOTAL = 'memberpress-track-recurring-total';
    const MEMBERPRESS_DATA1 = 'memberpress-data1';
    const MEMBERPRESS_DATA2 = 'memberpress-data2';
    const MEMBERPRESS_DATA3 = 'memberpress-data3';
    const MEMBERPRESS_DATA4 = 'memberpress-data4';
    const MEMBERPRESS_DATA5 = 'memberpress-data5';

    public function __construct() {
        parent::__construct(self::MEMBERPRESS_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/MemberPressConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::MEMBERPRESS_TRACK_RECURRING);
        $this->addCheckbox(self::MEMBERPRESS_TRACK_RECURRING_TOTAL);

        $additionalData = array(
                '0' => ' ',
                'u_ID' => 'user ID',
                'u_user_login' => 'user login',
                'u_user_nicename' => 'user nice name',
                'u_display_name' => 'user display name',
                'u_user_email' => 'user email',
                'u_user_url' => 'user URL',
                'u_first_name' => 'user first name',
                'u_last_name' => 'user last name',
                'u_user_ip' => 'user IP address',
                'p_price' => 'membership price',
                'p_period_type' => 'membership period type',
                'p_pricing_title' => 'membership pricing title'
        );
        $this->addSelect(self::MEMBERPRESS_DATA1, $additionalData);
        $this->addSelect(self::MEMBERPRESS_DATA2, $additionalData);
        $this->addSelect(self::MEMBERPRESS_DATA3, $additionalData);
        $this->addSelect(self::MEMBERPRESS_DATA4, $additionalData);
        $this->addSelect(self::MEMBERPRESS_DATA5, $additionalData);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::MEMBERPRESS_COMMISSION_ENABLED);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_TRACK_RECURRING);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_TRACK_RECURRING_TOTAL);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_DATA1);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_DATA2);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_DATA3);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_DATA4);
        register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_DATA5);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::MEMBERPRESS_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('MemberPress','pap-integrations'),
                __('MemberPress','pap-integrations'),
                'manage_options',
                'memberpressintegration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function MemberPressTrackSale($txn) {
        if (get_option(self::MEMBERPRESS_COMMISSION_ENABLED) !== 'true') {
            return false;
        }
        
        // START hacky fix for recurring subscriptions with no free trial comming in as $0 amount and few seconds afterwards triggering MemberPressRecurringSale with correct amount
        $price = $txn->amount;
        if ($price == '0.00' || $price == 0) {
            $product = $txn->product();
            if (isset($product->trial_days) && isset($product->trial_amount)) {
                if ($product->trial_days == '0' || $product->trial_amount != '0') {
                    $this->_log('Subscription product with incorrect amount received, saving cookie to user\'s meta and waiting for recurring notification with actual paid amount.');
                    if (isset($_REQUEST['pap_custom'])) {
                        $wp_user_obj = MeprUtils::get_user_by('id', $txn->user_id);
                        update_user_meta($wp_user_obj->ID, 'pap_custom', $_REQUEST['pap_custom']);
                    }
                    return;
                }
            }
        }
        // END

        $accountID = '';
        $visitorID = '';
        $papCookie = '';
        if (isset($_REQUEST['pap_custom']) && ($_REQUEST['pap_custom'] != '')) {
            $papCookie = $_REQUEST['pap_custom'];
        } else {
            $wp_user_obj = MeprUtils::get_user_by('id', $txn->user_id);
            $papCookie = get_user_meta($wp_user_obj->ID, 'pap_custom', true);
        }
        if($papCookie !== '') {
            $visitorID = substr($papCookie, -32);
            $accountID = substr($papCookie, 0, 8);
        }

        $query = 'AccountId='.$accountID. '&visitorId='.$visitorID.
            '&TotalCost='.$txn->amount.'&ProductID='.$txn->product_id.'&OrderID=';

        if (isset($txn->subscription_id) && $txn->subscription_id != '0' && $txn->subscription_id != 0) {
            $query .= $txn->subscription_id;
        } else {
            $query .= $txn->id;
        }

        $query = $this->addExtraDataToQuery($query, $txn);

        if (isset($_REQUEST['pap_IP'])) {
            $query .= '&ip='.$_REQUEST['pap_IP'];
        }
        
        $this->_log('MemberPress integration sending a tracking request with the following details: ' . $query);
        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
        return $txn;
    }

    public function addHiddenFieldToPaymentForm($productId) {
        postaffiliatepro::addHiddenFieldToPaymentForm();
        return $productId;
    }

    public function MemberPressRecurringSale(MeprEvent $event) {
        $txn = new MeprTransaction($event->evt_id);
        if (get_option(self::MEMBERPRESS_TRACK_RECURRING) !== 'true') {
            $this->_log(__('Recurring commissions are not enabled, ending'));
            return;
        }

        // try to recurr a commission with order ID $txn->subscription_id
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
            return;
        }

        $recurringTotal = false;
        if (get_option(self::MEMBERPRESS_TRACK_RECURRING_TOTAL) === 'true') {
            $recurringTotal = $txn->amount;
        }
        $this->_log('MemberPress integration trying to trigger a recurring commission for subscription ' . $txn->subscription_id . ' with value ' . $recurringTotal);
        if (!$this->fireRecurringCommissions($session, $txn->subscription_id, $recurringTotal)) {
            // creating recurring commissions failed, create a new commission instead
            $this->_log(__('Creating a new commission with order ID %s',$txn->subscription_id));
            $this->MemberPressTrackSale($txn);
        }
    }

    private function addExtraDataToQuery($query, $txn) {
        for ($i = 1; $i <= 5; $i++) {
            if (get_option(constant('self::MEMBERPRESS_DATA'. $i)) === '0') {
                continue;
            }
            if (substr(get_option(constant('self::MEMBERPRESS_DATA'. $i)), 0, 2) === 'u_') {
                $data = $this->getUserDetail($txn->user_id, substr(get_option(constant('self::MEMBERPRESS_DATA'. $i)), 2));
            } else {
                $data = $this->getProductDetail($txn->product_id, substr(get_option(constant('self::MEMBERPRESS_DATA'. $i)), 2));
            }
            $query .= '&data' . $i . '=' . $data;
        }
        return $query;
    }

    private function getUserDetail($id, $detail) {
        $user = new MeprUser($id);
        if (isset($user->$detail)) {
            return urlencode($user->$detail);
        }
        return '';
    }

    private function getProductDetail($id, $detail) {
        $product = new MeprProduct($id);
        if (isset($product->$detail)) {
            return urlencode($product->$detail);
        }
        return '';
    }
}

$submenuPriority = 22;
$integration = new postaffiliatepro_Form_Settings_MemberPress();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_action('mepr-signup', array($integration, 'MemberPressTrackSale'));
add_action('mepr-checkout-before-submit', array($integration, 'addHiddenFieldToPaymentForm'));
add_action('mepr-event-recurring-transaction-completed', array($integration, 'MemberPressRecurringSale'), 99, 1);