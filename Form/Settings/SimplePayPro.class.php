<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_SimplePayPro extends postaffiliatepro_Form_Base {
    const SIMPLEPAYPRO_COMMISSION_ENABLED = 'simplepaypro-commission-enabled';
    const SIMPLEPAYPRO_CONFIG_PAGE = 'simplepaypro-config-page';
    const SIMPLEPAYPRO_CAMPAIGN = 'simplepaypro-campaign';
    const SIMPLEPAYPRO_TRACK_RECURRING = 'simplepaypro-track-recurring';
    const SIMPLEPAYPRO_DATA1 = 'simplepaypro-data1';
    const SIMPLEPAYPRO_DATA2 = 'simplepaypro-data2';
    const SIMPLEPAYPRO_DATA3 = 'simplepaypro-data3';
    const SIMPLEPAYPRO_DATA4 = 'simplepaypro-data4';
    const SIMPLEPAYPRO_DATA5 = 'simplepaypro-data5';

    public function __construct() {
        parent::__construct(self::SIMPLEPAYPRO_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/SimplePayProConfig.xtpl';
    }

    protected function initForm() {
        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(self::SIMPLEPAYPRO_CAMPAIGN, $campaigns);

        $this->addCheckbox(self::SIMPLEPAYPRO_TRACK_RECURRING, null, 'onchange="hideConfigTable2()"');
        $productOptions = array(
                '0' => ' ',
                'id' => 'customer ID',
                'email' => 'customer email',
                'name' => 'customer name'

        );
        $this->addSelect(self::SIMPLEPAYPRO_DATA1, $productOptions);
        $this->addSelect(self::SIMPLEPAYPRO_DATA2, $productOptions);
        $this->addSelect(self::SIMPLEPAYPRO_DATA3, $productOptions);
        $this->addSelect(self::SIMPLEPAYPRO_DATA4, $productOptions);
        $this->addSelect(self::SIMPLEPAYPRO_DATA5, $productOptions);

        $this->addSubmit();

        $code = '<script type="text/javascript">
                const isRecurringEnabled = "' . get_option(self::SIMPLEPAYPRO_TRACK_RECURRING) . '";
                if (isRecurringEnabled === "true") {
                    document.getElementById(\'form-table2\').style.display = "none";
                }
                
                function hideConfigTable2() {
                    if (document.getElementById(\''.self::SIMPLEPAYPRO_TRACK_RECURRING.'_\').checked == false) {
                        document.getElementById(\'form-table2\').style.display = "";
                    } else {
                        document.getElementById(\'form-table2\').style.display = "none";
                    }
                }
                </script>';
        $this->addHtml('hider', $code);
        $this->addHtml('papURL', get_option(postaffiliatepro::PAP_URL_SETTING_NAME).'plugins/Stripe/stripe.php'.((get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME) != '')?'?AccountId='.get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME):''));
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::SIMPLEPAYPRO_COMMISSION_ENABLED);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_CAMPAIGN);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_DATA1);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_DATA2);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_DATA3);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_DATA4);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_DATA5);
           register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_TRACK_RECURRING);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::SIMPLEPAYPRO_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('Simple Pay Pro','pap-integrations'),
                __('Simple Pay Pro','pap-integrations'),
                'manage_options',
                'simplepayprointegration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    public function addHiddenCodeToForm($formId, $form) {
        // Adding hidden code to form so we could work with cookie in Stripe Customer object later
        if (get_option(self::SIMPLEPAYPRO_TRACK_RECURRING) === 'true') {
            postaffiliatepro::addHiddenFieldToPaymentForm();
        }
    }

    public function modifyCustomerInfo($customer_args) {
        if (get_option(self::SIMPLEPAYPRO_TRACK_RECURRING) == 'true') {
            if (isset($customer_args['description'])) {
                $customer_args['description'] = $customer_args['description'] . '||' . $_POST['form_values']['pap_custom'];
            } else {
                $customer_args['description'] = $_POST['form_values']['pap_custom'];
            }
        }
        return $customer_args;
    }

    public function confirmationMessage($HTMLcontent, $paymentData) {
        if (get_option(self::SIMPLEPAYPRO_TRACK_RECURRING) !== 'true') {
            $HTMLcontent .= "<!-- Post Affiliate Pro sale tracking -->\n";
            $contentToAsync = 'PostAffTracker.setAccountId(\'' . postaffiliatepro::getAccountName() . '\');';
            $contentToAsync .= "var sale = PostAffTracker.createSale();\n";
            $contentToAsync .= "sale.setTotalCost('" . ($paymentData['paymentintents'][0]->amount_received / 100) . "');\n";
            $contentToAsync .= "sale.setOrderID('". $paymentData['paymentintents'][0]->charges->data[0]->id ."');\n";
            $contentToAsync .= "sale.setProductID('". $paymentData['form']->id ."');\n";

            if (get_option(self::SIMPLEPAYPRO_CAMPAIGN) != '') {
                $contentToAsync .= "sale.setCampaignID('".get_option(self::SIMPLEPAYPRO_CAMPAIGN)."');\n";
            }

            $contentToAsync .= "sale.Data1('". str_replace("'","\'",$this->getTrackingData($paymentData['customer'], 1))."');\n";
            $contentToAsync .= "sale.Data2('". str_replace("'","\'",$this->getTrackingData($paymentData['customer'], 2))."');\n";
            $contentToAsync .= "sale.Data3('". str_replace("'","\'",$this->getTrackingData($paymentData['customer'], 3))."');\n";
            $contentToAsync .= "sale.Data4('". str_replace("'","\'",$this->getTrackingData($paymentData['customer'], 4))."');\n";
            $contentToAsync .= "sale.Data5('". str_replace("'","\'",$this->getTrackingData($paymentData['customer'], 5))."');\n";
            $contentToAsync .= "sale.setCurrency('".$paymentData['form']->currency."');\n";
            $contentToAsync .= 'PostAffTracker.register();';
            
            if (get_option(postaffiliatepro::ASYNC_ENABLED) !== 'true') {
                $HTMLcontent .= postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
                $HTMLcontent .= $contentToAsync . "</script>\n";
            } else {
                $HTMLcontent .= postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
            }
        }

        return $HTMLcontent;
    }

    private function getTrackingData($customer, $n) {
        $product = null;
        $data = get_option(constant('self::SIMPLEPAYPRO_DATA'.$n));
        switch ($data) {
            case 'id':
            case 'email':
            case 'name':
                return $customer->{$data};
                break;
            default: return '';
        }
    }
}

$submenuPriority = 43;
$integration = new postaffiliatepro_Form_Settings_SimplePayPro();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);
add_filter('simpay_payment_confirmation_content', array(
        $integration,
        'confirmationMessage'
), 99, 2);
add_action('simpay_form_before_form_bottom', array(
        $integration,
        'addHiddenCodeToForm'
), 99, 2);
add_filter('simpay_create_customer_args', array(
        $integration,
        'modifyCustomerInfo'
), 99);