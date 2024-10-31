<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_ContactForm7 extends postaffiliatepro_Form_Base {
    const CONTACT7_SIGNUP_COMMISSION_ENABLED = 'contact7-signup-commission-enabled';
    const CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE = 'contact7-signup-commission-config-page';
    const CONTACT7_CONTACT_COMMISSION_AMOUNT = 'contact7-contact-commission-amount';
    const CONTACT7_CONTACT_COMMISSION_CAMPAIGN = 'contact7-contact-commission-campaign';
    const CONTACT7_CONTACT_COMMISSION_TYPE = 'contact7-contact-commission-type';
    const CONTACT7_CONTACT_COMMISSION_FORM = 'contact7-contact-commission-form';
    const CONTACT7_CONTACT_EMAIL_AS_ORDERID = 'contact7-contact-email-as-orderid';
    const CONTACT7_CONTACT_COMMISSION_STORE_FORM = 'contact7-contact-commission-store-form';
    const CONTACT7_CONTACT_SEPARATE_ACTIONS = 'contact7-contact-separate-actions';
    const CONTACT7_DATA2 = 'contact7-data2';
    const CONTACT7_DATA3 = 'contact7-data3';
    const CONTACT7_DATA4 = 'contact7-data4';
    const CONTACT7_DATA5 = 'contact7-data5';
    const CONTACT7_PRODUCTID = 'contact7-prodid';

    private $Contact7PostedData = array();

    public function __construct() {
        parent::__construct(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, 'options.php');
    }

    private function getFormData() {
        if (count($this->Contact7PostedData) == 0) {
            return '';
        }
        $output = '';
        foreach ($this->Contact7PostedData as $key => $value) {
            $output .= $key . ': ' . $value . ', ';
        }
        return substr($output,0,-2);
    }

    private function getEmail() {
        if (count($this->Contact7PostedData) == 0) {
            return '';
        }
        foreach ($this->Contact7PostedData as $key => $value) {
            if (strpos(strtolower($key), 'email') !== false) {
                return $value;
            }
        }
        return '';
    }

    private function commissionEnabledForForm($form) {
        if (get_option(self::CONTACT7_CONTACT_COMMISSION_FORM) == '0') {
            return true;
        }
        return get_option(self::CONTACT7_CONTACT_COMMISSION_FORM) == $form->id;
    }

    private function contactForm7ContactCommissionEnabled() {
        return postaffiliatepro_Util_ContactForm7Helper::formsExists() && get_option(self::CONTACT7_SIGNUP_COMMISSION_ENABLED) === 'true';
    }

    private function contactForm7ContactCommissionStoreForm() {
        return get_option(self::CONTACT7_CONTACT_COMMISSION_STORE_FORM) === 'true';
    }

    public function addContactForm7ContactCommission($form) {
        if (!$this->contactForm7ContactCommissionEnabled()) {
            postaffiliatepro_Base::_log(__('Contact form 7 contact commission disabled. Skipping action.'));
            return $form;
        }
        if (!$this->commissionEnabledForForm($form)) {
            postaffiliatepro_Base::_log(__('Contact form 7 contact commission not enabled for form ' . $form->unit_tag . '. Skipping action.'));
            return $form;
        }
        $saleTracker = new Pap_Api_SaleTracker($this->getApiSessionUrl());
        $saleTracker->setAccountId(postaffiliatepro::getAccountName());
        if (get_option(self::CONTACT7_CONTACT_SEPARATE_ACTIONS) != '') {
            $sale = $saleTracker->createAction($form->id());
        } elseif (get_option(self::CONTACT7_CONTACT_COMMISSION_TYPE) != '') {
            $sale = $saleTracker->createAction(get_option(self::CONTACT7_CONTACT_COMMISSION_TYPE));
        } else {
            $sale = $saleTracker->createSale();
        }
        $sale->setTotalCost(get_option(self::CONTACT7_CONTACT_COMMISSION_AMOUNT));
        
        $prodId = get_option(self::CONTACT7_PRODUCTID);
        if ($prodId === '' || $prodId === ' ') {
            $sale->setProductID($form->title());
        } else {
            if (isset($this->Contact7PostedData[$prodId])) {
                $product = $this->Contact7PostedData[$prodId];
                if (is_array($product)) {
                    $sale->setProductID($product[0]);
                } else {
                    $sale->setProductID($product);
                }
                
            }
        }

        if ($this->contactForm7ContactCommissionStoreForm()) {
            $sale->setData1($this->getFormData());
        }
        for ($i = 2; $i <= 5; $i++) {
            if (($key = get_option(constant('self::CONTACT7_DATA' . $i))) != '' && isset($this->Contact7PostedData[$key])) {
                $sale->setData($i, $this->Contact7PostedData[$key]);
            }
        }

        if (get_option(self::CONTACT7_CONTACT_EMAIL_AS_ORDERID) === 'true') {
            $email = $this->getEmail();
            if ($email != '') {
                $sale->setOrderID($email);
            }
        }

        if (get_option(self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN) != '') {
            $sale->setCampaignId(get_option(self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN));
        }
        try {
            $saleTracker->register();
        } catch (Exception $e) {
            postaffiliatepro_Base::_log(__('Error during registering contact commission: ' . $e->getMessage()));
        }
    }

    public function saveContactForm7FormData($posted_data) {
        $this->Contact7PostedData = $posted_data;
        return $posted_data;
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/ContactForm7Config.xtpl';
    }

    private function getCampaignSelectData() {
        $campaigns = $this->getCampaignHelper()->getCampaignsList();
        $data = array();
        foreach($campaigns as $rec) {
            $data[$rec->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID)] = htmlspecialchars($rec->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_NAME));
        }
        return $data;
    }

    private function getFormsSelectData() {
        $forms = postaffiliatepro_Util_ContactForm7Helper::getFormList();
        $data[0] = 'All';
        foreach($forms as $form) {
            $data[$form->cf7_unit_id] = $form->title;
        }
        return $data;
    }

    protected function getOption($name, $type = 'none') {
        if ($name === self::CONTACT7_CONTACT_COMMISSION_AMOUNT && get_option($name) == '') {
            return 0;
        }
        if ($name === self::CONTACT7_CONTACT_COMMISSION_FORM && get_option($name) == '') {
            return 0;
        }
        return parent::getOption($name);
    }

    protected function initForm() {
        if (!postaffiliatepro_Util_ContactForm7Helper::formsExists()) {
            $this->addHtml('contact7-signup-note', '<tr><td colspan="2" style="padding-top:0px;padding-bottom:15px;color:#750808;">No forms exist!</td></tr>');
        } else {
            $this->addHtml('contact7-signup-note', '');
        }

        $this->addTextBox(self::CONTACT7_CONTACT_COMMISSION_AMOUNT, 10);
        $this->addSelect(self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN, $this->getCampaignSelectData());
        $this->addTextBox(self::CONTACT7_CONTACT_COMMISSION_TYPE, 10);
        $this->addCheckbox(self::CONTACT7_CONTACT_COMMISSION_STORE_FORM);
        $this->addCheckbox(self::CONTACT7_CONTACT_EMAIL_AS_ORDERID);
        $this->addCheckbox(self::CONTACT7_CONTACT_SEPARATE_ACTIONS);
        $this->addSelect(self::CONTACT7_CONTACT_COMMISSION_FORM, $this->getFormsSelectData());
        $this->addTextBox(self::CONTACT7_DATA2);
        $this->addTextBox(self::CONTACT7_DATA3);
        $this->addTextBox(self::CONTACT7_DATA4);
        $this->addTextBox(self::CONTACT7_DATA5);
        $this->addTextBox(self::CONTACT7_PRODUCTID);
        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::CONTACT7_SIGNUP_COMMISSION_ENABLED);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_AMOUNT);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_TYPE);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_FORM);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_EMAIL_AS_ORDERID);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_STORE_FORM);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_SEPARATE_ACTIONS);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_DATA2);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_DATA3);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_DATA4);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_DATA5);
        register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_PRODUCTID);
    }

    public function addPrimaryConfigMenu() {
        if (postaffiliatepro_Util_ContactForm7Helper::formsExists() && get_option(self::CONTACT7_SIGNUP_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('Contact form 7','pap-integrations'),
                __('Contact form 7','pap-integrations'),
                'manage_options',
                'contact-form-7-settings-page',
                array($this, 'printConfigPage')
            );
        }
    }

    public function printConfigPage() {
        $this->render();
    }
}

$submenuPriority = 11;
$integration = new postaffiliatepro_Form_Settings_ContactForm7();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_action('wpcf7_mail_sent', array($integration, 'addContactForm7ContactCommission'));
add_filter('wpcf7_posted_data', array($integration, 'saveContactForm7FormData'));