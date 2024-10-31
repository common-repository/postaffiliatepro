<?php
/**
 *   @copyright Copyright (c) 2020 Quality Unit s.r.o.
 *   @author Martin Svitek
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_WPPayForms extends postaffiliatepro_Form_Base {
    const WPPF_COMMISSION_ENABLED = 'wppf-commission-enabled';
    const WPPF_CONFIG_PAGE = 'wppf-config-page';
    
    public function __construct() {
        parent::__construct(self::WPPF_CONFIG_PAGE, 'options.php');
    }
    
    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WPPayFormsConfig.xtpl';
    }
    
    protected function initForm() {
        '';
    }
    
    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WPPF_COMMISSION_ENABLED);
    }
    
    public function addPrimaryConfigMenu() {
        if (get_option(self::WPPF_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('WPPayForms', 'pap-integrations'), __('WPPayForms', 'pap-integrations'), 'manage_options', 'wppfintegration-settings-page', array(
                $this,
                'printConfigPage'
            ));
        }
    }
    
    public function printConfigPage() {
        $this->render();
        return;
    }
    
    public function addHiddenCodeToForm($form) {
        if (get_option(self::WPPF_COMMISSION_ENABLED) === 'true') {
            postaffiliatepro::addHiddenFieldToPaymentForm();
        }
    }
    
    public function wppfTrackSale($submission, $transaction, $form_id, $invoice) {
        if (get_option(self::WPPF_COMMISSION_ENABLED) !== 'true') {
            return;
        }
        if (!$transaction) {
            $this->_log('No transaction received, stopping. Most probably this is a subscription payment and tracking of subscription payments is not implemented. Contact support if you need this tracked.');
            return;
        } else {
            $this->_log('Tracking of transaction: ' . print_r($transaction, true));
        }
        if(is_array($transaction) && isset($transaction[0])) {
            $transaction = $transaction[0];
        }
        if($transaction->status !== 'paid') {
            $this->_log('Transaction was not paid yet, stopping.');
            return;
        }
        
        $cookie = '';
        if(isset($submission->form_data_raw) && isset($submission->form_data_raw['pap_custom'])) {
            $cookie = $submission->form_data_raw['pap_custom'];
        } else {
            $this->_log('Cookie not set, submission contained the following: ' . print_r($submission, true));
        }
        $totalCost = $transaction->payment_total / 100;
        $orderId = $transaction->submission_id;
        if(isset($transaction->subscription_id) && $transaction->subscription_id != '') {
            $orderId = $transaction->subscription_id;
        }
        $query = 'AccountId=' . substr($cookie, 0, 8) . '&visitorId=' . substr($cookie, -32);
        $query .= "&TotalCost={$totalCost}&OrderID={$orderId}&ProductID={$transaction->form_id}&Currency={$transaction->currency}&data1={$transaction->user_id}";
        $this->_log('Sending a tracking request with these details: ' . print_r($query, true));
        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }
}

$submenuPriority = 57;
$integration = new postaffiliatepro_Form_Settings_WPPayForms();
add_action('admin_init', array(
    $integration,
    'initSettings'
), 99);
add_action('admin_menu', array(
    $integration,
    'addPrimaryConfigMenu'
), $submenuPriority);

add_action('wppayform/form_render_before_submit_button', array(
    $integration,
    'addHiddenCodeToForm'
));
add_action('wppayform/form_payment_success', array(
    $integration,
    'wppfTrackSale'
), 99, 4);
