<?php
/**
 *   @copyright Copyright (c) 2021 Quality Unit s.r.o.
 *   @author Martin Svitek
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.22.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_LifterLMS extends postaffiliatepro_Form_Base {
    const LIFTERLMS_COMMISSION_ENABLED = 'lifterlms-commission-enabled';
    const LIFTERLMS_CONFIG_PAGE = 'lifterlms-config-page';
    const LIFTERLMS_PRODUCT_ID = 'lifterlms-product-id';
    const LIFTERLMS_DATA1 = 'lifterlms-data1';
    const LIFTERLMS_DATA2 = 'lifterlms-data2';
    const LIFTERLMS_DATA3 = 'lifterlms-data3';
    const LIFTERLMS_DATA4 = 'lifterlms-data4';
    const LIFTERLMS_DATA5 = 'lifterlms-data5';
    const LIFTERLMS_CAMPAIGN = 'lifterlms-campaign';

    public function __construct() {
        parent::__construct(self::LIFTERLMS_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/LifterLMSConfig.xtpl';
    }

    protected function initForm() {
        $this->addSelect(self::LIFTERLMS_PRODUCT_ID, array(
            '0' => ' ',
            'product_id' => 'product ID',
            'product_title' => 'product title',
            'product_sku' => 'product SKU',
            'product_type' => 'product type',
            'plan_id' => 'plan ID',
            'plan_title' => 'plan title',
            'plan_sku' => 'plan SKU'
        ));

        $dataOptions = array(
            '0' => ' ',
            'user_id' => 'customer ID',
            'billing_email' => 'customer billing email',
            'billing_phone' => 'customer billing phone',
            'name' => 'customer billing name', // billing_first_name billing_last_name
            'address' => 'customer billing address', // billing_address_1 billing_address_2 billing_city billing_zip billing_state billing_country
            'coupon_code' => 'coupon code',
            'product_id' => 'product ID',
            'product_title' => 'product title',
            'product_sku' => 'product SKU',
            'product_type' => 'product type',
            'plan_id' => 'plan ID',
            'plan_title' => 'plan title',
            'plan_sku' => 'plan SKU'
        );
        $this->addSelect(self::LIFTERLMS_DATA1, $dataOptions);
        $this->addSelect(self::LIFTERLMS_DATA2, $dataOptions);
        $this->addSelect(self::LIFTERLMS_DATA3, $dataOptions);
        $this->addSelect(self::LIFTERLMS_DATA4, $dataOptions);
        $this->addSelect(self::LIFTERLMS_DATA5, $dataOptions);

        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array(
            '0' => ' '
        );
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(self::LIFTERLMS_CAMPAIGN, $campaigns);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::LIFTERLMS_COMMISSION_ENABLED);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_PRODUCT_ID);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_DATA1);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_DATA2);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_DATA3);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_DATA4);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_DATA5);
        register_setting(self::LIFTERLMS_CONFIG_PAGE, self::LIFTERLMS_CAMPAIGN);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::LIFTERLMS_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('LifterLMS', 'pap-integrations'), __('LifterLMS', 'pap-integrations'), 'manage_options', 'lifterlmsintegration-settings-page', array(
                $this,
                'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    public function addHiddenCodeToForm() {
        if (get_option(self::LIFTERLMS_COMMISSION_ENABLED) === 'true') {
            postaffiliatepro::addHiddenFieldToPaymentForm();
        }
    }

    public function papLifterTrackSale($order) {
        if (get_option(self::LIFTERLMS_COMMISSION_ENABLED) !== 'true') {
            return;
        }
        if (!is_object($order)) {
            $order = new LLMS_Order($order);
        }

        $cookie = $_POST['pap_custom'] ?? '';
        $query = 'AccountId=' . postaffiliatepro::getAccountName() . '&visitorId=' . substr($cookie, -32);
        $campaignId = get_option(self::LIFTERLMS_CAMPAIGN);
        if ($campaignId != '' && $campaignId !== '0') {
            $query .= '&CampaignID=' . $campaignId;
        }
        $totalCost = ($order->get('trial_offer') === 'yes') ? $order->get('trial_total') : $order->get('total');
        $query .= "&TotalCost=$totalCost&OrderID={$order->get('id')}&ProductID={$order->get('product_id')}";
        $query .= "&Currency={$order->get('currency')}&ip={$order->get('user_ip_address')}&Coupon={$order->get('coupon_code')}";
        for ($d = 1; $d <=5; $d++) {
            $query .= "&Data$d=" . urlencode($this->getTrackingData($order, $d));
        }
        self::_log('Sending a tracking request with these details: ' . print_r($query, true));
        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }

    public function papLifterTrackRecurring($order) {
        if (get_option(self::LIFTERLMS_COMMISSION_ENABLED) !== 'true') {
            return;
        }
        if (!is_object($order)) {
            $order = new LLMS_Order($order);
        }
        self::_log('Tracking of recurring order: ' . $order->get('id'));

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            self::_log(__('We have no session to PAP installation! Recurring commission failed. Try to create a regular commission.'));
            $this->papLifterTrackSale($order);
            return;
        }

        if (!$this->fireRecurringCommissions($session, $order->get('id'), $order->get('total'))) {
            self::_log(__('Recurring commission for order ID ') . $order->get('id') . ' failed, trying to create a regular commission.');
            $this->papLifterTrackSale($order);
        }
    }

    private function getTrackingData($order, $n) {
        $data = get_option(constant('self::LIFTERLMS_DATA'.$n));
        switch ($data) {
            case '0':
                return '';
            case 'name':
                return $order->get('billing_first_name') . ' ' . $order->get('billing_last_name');
            case 'address':
                return $order->get('billing_address_1') . ' ' . $order->get('billing_address_2') . ' ' . $order->get('billing_city') . ' ' . $order->get('billing_zip') . ' ' . $order->get('billing_state') . ' ' . $order->get('billing_country');
            default: return $order->get($data);
        }
    }
}

$submenuPriority = 20;
$integration = new postaffiliatepro_Form_Settings_LifterLMS();
add_action('admin_init', array(
    $integration,
    'initSettings'
), 99);
add_action('admin_menu', array(
    $integration,
    'addPrimaryConfigMenu'
), $submenuPriority);

add_action('lifterlms_after_checkout_form', array(
    $integration,
    'addHiddenCodeToForm'
));
add_action('lifterlms_order_status_active', array(
    $integration,
    'papLifterTrackSale'
));
add_action('llms_charge_recurring_payment', array(
    $integration,
    'papLifterTrackRecurring'
));
