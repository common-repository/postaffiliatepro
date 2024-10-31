<?php
/**
 *   @copyright Copyright (c) 2019 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_WPEasyCart extends postaffiliatepro_Form_Base {
    const WPEC_COMMISSION_ENABLED = 'wpec-commission-enabled';
    const WPEC_CONFIG_PAGE = 'wpec-config-page';
    const WPEC_DATA1 = 'wpec-data1';

    public function __construct() {
        parent::__construct(self::WPEC_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WPEasyCartConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::WPEC_DATA1);
        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WPEC_COMMISSION_ENABLED);
        register_setting(self::WPEC_CONFIG_PAGE, self::WPEC_DATA1);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::WPEC_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('WP EasyCart', 'pap-integrations'), __('WP EasyCart', 'pap-integrations'), 'manage_options', 'wpecintegration-settings-page', array(
                    $this,
                    'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function wpecAddThankYouPageTrackSale($orderId, $order) {
        if (get_option(self::WPEC_COMMISSION_ENABLED) !== 'true') {
            echo "<!-- Post Affiliate Pro sale tracking error - tracking not enabled -->\n";
            return $orderId;
        }

        echo "<!-- Post Affiliate Pro sale tracking -->\n";
        $contentToAsync = 'PostAffTracker.setAccountId(\'' . postaffiliatepro::getAccountName() . '\');';
        $contentToAsync .= "var sale = PostAffTracker.createSale();\n";
        $contentToAsync .= "sale.setTotalCost('" . $order->grand_total . "');\n";
        $contentToAsync .= "sale.setOrderID('$orderId(1)');\n";

        if (get_option(self::WPEC_DATA1) == 'true') {
            $contentToAsync .= "sale.setData1('" . $order->user_email . "');\n";
        }
        $contentToAsync .= 'PostAffTracker.register();';
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) != 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }
        
        echo $result;
    }

    public function wpecOrderStatusChanged($orderId) {
        // order paid...
        $this->_log('Transaction ' . $orderId . ' paid, approving commission.');
        return $this->changeOrderStatus($orderId, 'A');
    }

    private function refundTransaction($orderId) {
        $limit = 100;
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
            return;
        }
        $ids = $this->getTransactionIDsByOrderID($orderId, $session, 'A,P', $limit);
        if (empty($ids)) {
            $this->_log(__('Nothing to change, the commission does not exist in PAP'));
            return true;
        }

        $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'makeRefundChargeback', $session);
        $request->addParam('ids', new Gpf_Rpc_Array($ids));
        $request->addParam('status', 'R');
        $request->addParam('merchant_note', 'Refunded automatically from WP EasyCart');
        try {
            $request->sendNow();
        } catch (Exception $e) {
            $this->_log(__('A problem occurred while transaction status change with API: ') . $e->getMessage());
            return false;
        }

        return true;
    }
}

$submenuPriority = 55;
$integration = new postaffiliatepro_Form_Settings_WPEasyCart();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);

add_action('wpeasycart_success_page_content_top', array(
        $integration,
        'wpecAddThankYouPageTrackSale'
), 99, 2);


add_action('wpeasycart_order_paid', array(
        $integration,
        'wpecOrderStatusChanged'
));

add_action('wpeasycart_full_order_refund', array(
        $integration,
        'refundTransaction'
));
