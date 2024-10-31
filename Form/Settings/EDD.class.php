<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_EDD extends postaffiliatepro_Form_Base {
    const EDD_COMMISSION_ENABLED = 'edd-commission-enabled';
    const EDD_CONFIG_PAGE = 'edd-config-page';
    const EDD_PERPRODUCT = 'edd-per-product';
    const EDD_PRODUCT_ID = 'edd-product-id';
    const EDD_TRACK_FEE = 'edd-track-fee';
    const EDD_DATA1 = 'edd-data1';
    const EDD_CAMPAIGN = 'edd-campaign';
    const EDD_STATUS_UPDATE = 'edd-status-update';

    public function __construct() {
        parent::__construct(self::EDD_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/EDD.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::EDD_PERPRODUCT);
        $this->addCheckbox(self::EDD_TRACK_FEE);
        $this->addSelect(self::EDD_PRODUCT_ID, array(
                '0' => ' ',
                'id' => 'product ID',
                'name' => 'product name'
        ));
        $this->addSelect(self::EDD_DATA1, array(
                '0' => ' ',
                'id' => 'customer ID',
                'email' => 'customer email'
        ));

        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(self::EDD_CAMPAIGN, $campaigns);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::EDD_COMMISSION_ENABLED);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_PERPRODUCT);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_TRACK_FEE);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_PRODUCT_ID);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_DATA1);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_STATUS_UPDATE);
        register_setting(self::EDD_CONFIG_PAGE, self::EDD_CAMPAIGN);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::EDD_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('Easy Digital Downloads', 'pap-integrations'), __('Easy Digital Downloads', 'pap-integrations'), 'manage_options', 'eddintegration-settings-page', array(
                    $this,
                    'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function eddAddThankYouPageTrackSale($payment, $edd_receipt_args) {
        if (get_option(self::EDD_COMMISSION_ENABLED) !== 'true') {
            echo "<!-- Post Affiliate Pro sale tracking error - tracking not enabled -->\n";
            return;
        }

        $payment = new EDD_Payment($payment->ID);
        $cart_details = $payment->cart_details;

        if (is_array($cart_details) && get_option(self::EDD_PERPRODUCT) === 'true') {
            $count = count($cart_details);
            $i = 1;
            $deleteCookiesAfterFee = false;
            foreach ($cart_details as $n => $download) {
                $deleteCookies = false;
                $orderId = ($this->eddIsRecurringProduct($download)) ? $payment->ID . '-' . $download['id'] : $payment->ID.'('.($i).')';
                $order['id'] = $orderId;
                $order['total'] = $download['subtotal'];
                $order['product'] = $this->getProductID($download);
                $order['data1'] = $this->getTrackingData1($payment);
                $order['currency'] = $payment->currency;
                if ($count == $i) {
                    $deleteCookies = true;
                    if (get_option(self::EDD_TRACK_FEE) === 'true') {
                        $deleteCookies = false;
                        $deleteCookiesAfterFee = true;
                    }
                }
                $this->trackOrder($order, $deleteCookies);
                $i++;
                if (get_option(self::EDD_TRACK_FEE) === 'true') {
                    $this->trackSignupFee($payment, $download, $i, $deleteCookiesAfterFee);
                    $i++;
                }
            }
        } else {
            // per order
            $order['id'] = $payment->ID;
            $totalCost = (get_option(self::EDD_TRACK_FEE) === 'true') ? $payment->subtotal + $this->getSignupFee($payment) : $payment->subtotal;
            $order['total'] = $totalCost;
            $order['product'] = '';
            $order['data1'] = $this->getTrackingData1($payment);
            $order['currency'] = $payment->currency;
            $this->trackOrder($order);
        }
    }

    public function eddTrackRecurring($payment, $extraDetails) {
        if (get_option(self::EDD_COMMISSION_ENABLED) !== 'true') {
            return;
        }
        self::_log('EDD Tracking of recurring order: ' . $payment->parent_payment);

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            self::_log(__('We have no session to PAP installation! Recurring commission failed. Trying to create a regular commission.'));
            $this->eddTrackRecurringAsRegularSale($payment, $extraDetails);
            return;
        }

        if (!$this->fireRecurringCommissions($session, $payment->parent_payment . '-' . $extraDetails->product_id, $payment->total)) {
            self::_log(__('Recurring commission for order ID ') . $payment->parent_payment . ' failed, trying to create a regular commission.');
            $this->eddTrackRecurringAsRegularSale($payment, $extraDetails);
        }
    }

    private function trackOrder($order, $deleteCookie = false) {
        $result = "<!-- Post Affiliate Pro sale tracking -->\n";
        
        $contentToAsync = 'PostAffTracker.setAccountId(\'' . postaffiliatepro::getAccountName() . '\');';
        $contentToAsync .= "var sale = PostAffTracker.createSale();\n";
        $contentToAsync .= "sale.setTotalCost('" . $order['total'] . "');\n";
        $contentToAsync .= "sale.setOrderID('" . $order['id'] . "');\n";
        $contentToAsync .= "sale.setProductID('" . $order['product'] . "');\n";
        $contentToAsync .= "sale.setData1('" . $order['data1'] . "');\n";
        $contentToAsync .= "sale.setCurrency('" . $order['currency'] . "');\n";

        if (!$deleteCookie) {
            $contentToAsync .= "if (typeof sale.doNotDeleteCookies === 'function') {sale.doNotDeleteCookies();}\n";
        }
        if (get_option(self::EDD_CAMPAIGN) !== '' && get_option(self::EDD_CAMPAIGN) !== null && get_option(self::EDD_CAMPAIGN) !== 0 && get_option(self::EDD_CAMPAIGN) !== '0') {
            $contentToAsync .= "sale.setCampaignID('" . get_option(self::EDD_CAMPAIGN) . "');\n";
        }
        $contentToAsync .= "PostAffTracker.register();\n";
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) !== 'true') {
            $result .= postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result .= postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }
        
        echo $result;
        return true;
    }

    private function getProductID($order) {
        switch (get_option(self::EDD_PRODUCT_ID)) {
            case 'id':
                return $order['id'];
            case 'name':
                return $order['name'];
            default: return '';
        }
    }

    private function getTrackingData1($payment) {
        switch (get_option(self::EDD_DATA1)) {
            case 'id': return $payment->user_id; break;
            case 'email': return $payment->email; break;
            default: return '';
        }
    }

    private function getSignupFee($payment) {
        if (isset($payment->fees)) {
            foreach ($payment->fees as $fee) {
                if ($fee['id'] === 'signup_fee') {
                    return $fee['amount'];
                }
            }
        }
        return 0;
    }

    private function getSignupFeeFromDownload($download) {
        if (isset($download['options']['recurring']['signup_fee'])) {
            return $download['options']['recurring']['signup_fee'];
        }
        if (isset($download['item_number']['options']['recurring']['signup_fee'])) {
            return $download['item_number']['options']['recurring']['signup_fee'];
        }
        return 0;
    }

    private function trackSignupFee($payment, $download, $i, $deleteCookies) {
        $order['id'] = $payment->ID.'('.($i).')';
        $order['total'] = $this->getSignupFeeFromDownload($download);
        $order['product'] = $this->getProductID($download) . ' Signup Fee';
        $order['data1'] = $this->getTrackingData1($payment);
        $order['currency'] = $payment->currency;
        $this->trackOrder($order, $deleteCookies);
    }

    private function eddTrackRecurringAsRegularSale($payment, $extraDetails) {
        $query = 'AccountId=' . postaffiliatepro::getAccountName();
        $campaignId = get_option(self::EDD_CAMPAIGN);
        if ($campaignId != '' && $campaignId !== '0') {
            $query .= '&CampaignID=' . $campaignId;
        }
        $productId = $extraDetails->product_id;
        if (get_option(self::EDD_PRODUCT_ID) === 'name' && function_exists('edd_get_payment')) {
            $parentPayment = edd_get_payment($payment->parent_payment);
            if (isset($parentPayment->cart_details)) {
                foreach ( $parentPayment->cart_details as $cart_item ) {
                    if ( (int) $extraDetails->product_id === (int) $cart_item['id'] ) {
                        $productId = $cart_item['name'];
                    }
                }
            }
        }
        $orderId = $payment->ID . '-' . $extraDetails->product_id;
        $query .= "&TotalCost=$payment->total&OrderID=$orderId&ProductID=$productId";
        $query .= "&Currency=$payment->currency";
        $query .= "&Data1=" . urlencode($this->getTrackingData1($payment));
        self::_log('EDD Sending a tracking request with these details: ' . print_r($query, true));
        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }

    private function eddIsRecurringProduct($download) {
        if(isset($download['options']['recurring'])) {
            return true;
        }
        if(isset($download['item_number']['options']['recurring'])) {
            return true;
        }
        return false;
    }
}

$submenuPriority = 12;
$integration = new postaffiliatepro_Form_Settings_EDD();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);
add_action('edd_payment_receipt_after_table', array(
        $integration,
        'eddAddThankYouPageTrackSale'
), 99, 2);
add_action('edd_recurring_add_subscription_payment', array(
    $integration,
    'eddTrackRecurring'
), 99, 2);