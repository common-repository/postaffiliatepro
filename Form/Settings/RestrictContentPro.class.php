<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_RestrictContentPro extends postaffiliatepro_Form_Base {
    const RESTRICTCP_COMMISSION_ENABLED = 'rcppap-commission-enabled';
    const RESTRICTCP_CONFIG_PAGE = 'rcppap-config-page';
    const RESTRICTCP_REGISTRATION = 'rcppap-track-registration';
    const RESTRICTCP_REGISTRATION_ACTION_NAME = 'rcppap-registration-action-name';
    const RESTRICTCP_PAYMENTS = 'rcppap-track-payments';
    const RESTRICTCP_UPDATE_COMM_STATUS = 'rcppap-update-comm-status';

    public function __construct() {
        parent::__construct(self::RESTRICTCP_CONFIG_PAGE, 'options.php');
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::RESTRICTCP_COMMISSION_ENABLED);
        register_setting(self::RESTRICTCP_CONFIG_PAGE, self::RESTRICTCP_REGISTRATION);
        register_setting(self::RESTRICTCP_CONFIG_PAGE, self::RESTRICTCP_REGISTRATION_ACTION_NAME);
        register_setting(self::RESTRICTCP_CONFIG_PAGE, self::RESTRICTCP_PAYMENTS);
        register_setting(self::RESTRICTCP_CONFIG_PAGE, self::RESTRICTCP_UPDATE_COMM_STATUS);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(postaffiliatepro_Form_Settings_RestrictContentPro::RESTRICTCP_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('Restrict Content Pro','pap-integrations'),
                __('Restrict Content Pro','pap-integrations'),
                'manage_options',
                'rcppapintegration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/RestrictContentProConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::RESTRICTCP_REGISTRATION);
        $this->addTextBox(self::RESTRICTCP_REGISTRATION_ACTION_NAME);
        $this->addCheckbox(self::RESTRICTCP_PAYMENTS);
        $this->addCheckbox(self::RESTRICTCP_UPDATE_COMM_STATUS);

        $this->addSubmit();
    }

    public function customFieldsToRestrictSignupForm() {
        postaffiliatepro::addHiddenFieldToPaymentForm();
    }
    
    public function updateStatus($newStatus, $paymentId) {
        if (get_option(self::RESTRICTCP_UPDATE_COMM_STATUS) !== 'true') {
            return false;
        }
        
        $this->_log('Received status: ' . $newStatus);
        
        switch ($newStatus) {
            case 'complete':
                $status = 'A';
                break;
            case 'pending':
                $status = 'P';
                break;
            case 'failed':
                $status = 'D';
                break;
            case 'refunded':
                return $this->refundTransaction($orderId);
            default:
                $status = '';
        }
        
        if ($status == '') {
            $this->_log('Unsupported status ' . $newStatus);
            return false;
        }
        
        return $this->changeOrderStatus($paymentId, $status);
    }
    
    public function trackRcpRegistration($POST, $userId, $price, $paymentId, $customer, $membershipId, $previousMembership, $registrationType) {
        if (get_option(self::RESTRICTCP_REGISTRATION) !== 'true') {
            return false;
        }
        
        $action = '';
        if (get_option(self::RESTRICTCP_REGISTRATION_ACTION_NAME) != '') {
            $action = get_option(self::RESTRICTCP_REGISTRATION_ACTION_NAME);
        }      
        
        $wpuser = new WP_User($userId);

        $query = 'TotalCost='.$price.'&OrderID='.$membershipId;
        $query .= '&Data1='.$wpuser->user_email;

        try {
            $reg = rcp_get_registration();
            $lvlId = $reg->get_membership_level_id();
            $membershipLevel = rcp_get_membership_level($lvlId);

            $query .= '&ProductID='.urlencode($membershipLevel->get_name());
        } catch (Exception $e) {
            $this->_log('Error loading membership level: ' . $e->getMessage());
        }

        if ($action != '') {
            $query .= '&ActionCode='.$action;
        }

        if (isset($_REQUEST['pap_custom']) && $_REQUEST['pap_custom'] != '') {
            $query .= '&AccountId='.substr($_REQUEST['pap_custom'],0,8).
            $query .= '&visitorId='.substr($_REQUEST['pap_custom'],-32);
        }

        postaffiliatepro::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }

    public function trackRcpPayment($paymentId, $params) {
        if (get_option(self::RESTRICTCP_UPDATE_COMM_STATUS) !== 'true') {
            return false;
        }
		$this->_log('RCP payment details received: '.print_r($params, true));
		
		$level = rcp_get_membership_level($params['object_id']);
		$wpuser = new WP_User($params['user_id']);

        $query = 'TotalCost='.$params['amount'].'&OrderID='.$params['membership_id'];
        $query .= '&ProductID='.urlencode($level->get_name());
        $query .= '&Data1='.$wpuser->user_email;

        if (isset($_REQUEST['pap_custom']) && $_REQUEST['pap_custom'] != '') {
            $query .= '&AccountId='.substr($_REQUEST['pap_custom'],0,8).
            $query .= '&visitorId='.substr($_REQUEST['pap_custom'],-32);
        }

        postaffiliatepro::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
    }
}

$submenuPriority = 35;
$integration = new postaffiliatepro_Form_Settings_RestrictContentPro();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_action('rcp_create_payment', array($integration, 'trackRcpPayment'), 99, 2);
add_action('rcp_form_processing', array($integration, 'trackRcpRegistration'), 99, 8);
add_action('rcp_update_payment_status', array($integration, 'updateStatus'), 99, 2);
add_action('rcp_before_register_form_fields', array($integration, 'customFieldsToRestrictSignupForm'));