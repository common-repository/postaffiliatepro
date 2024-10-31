<?php
/**
 *   @copyright Copyright (c) 2018 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_Membership2 extends postaffiliatepro_Form_Base {
    const MEMBERSHIP2_COMMISSION_ENABLED = 'membership2-commission-enabled';
    const MEMBERSHIP2_CONFIG_PAGE = 'membership2-config-page';
    const MEMBERSHIP2_TRACK_REFUNDS = 'membership2-track-refunds';

    public function __construct() {
        parent::__construct(self::MEMBERSHIP2_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/Membership2Config.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::MEMBERSHIP2_TRACK_REFUNDS);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::MEMBERSHIP2_COMMISSION_ENABLED);
        register_setting(self::MEMBERSHIP2_CONFIG_PAGE, self::MEMBERSHIP2_TRACK_REFUNDS);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('Membership 2 Pro','pap-integrations'),
                __('Membership 2 Pro','pap-integrations'),
                'manage_options',
                'membership2integration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function Membership2TrackSale($invoice, $subscription) {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        $accountID = '';
        $visitorID = '';
        if (isset($_REQUEST['pap_custom']) && ($_REQUEST['pap_custom'] != '')) {
            $visitorID = substr($_REQUEST['pap_custom'],-32);
        }
        if (isset($_REQUEST['pap_custom']) && ($_REQUEST['pap_custom'] != '')) {
            $accountID = substr($_REQUEST['pap_custom'],0,8);
        }

        if ($subscription->is_trial_eligible()) {
            $total = $invoice->trial_price;
        } else {
            $total = $invoice->total;
        }

        $query = 'AccountId='.$accountID. '&visitorId='.$visitorID.
            '&TotalCost='.$total.'&ProductID='.$subscription->get_membership()->id.'&OrderID='.$subscription->id;

        $user = get_user_by('email', $_POST['payer_email']);
        $query .= '&Data1='.$user->ID;

        if (isset($_REQUEST['pap_IP'])) {
            $query .= '&ip='.$_REQUEST['pap_IP'];
        }

        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
        return true;
    }

    public function Membership2StripeTransaction($invoice, $gateway) {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        $subscription = $invoice->get_subscription();
        $member = $subscription->get_member();
        $customer = $gateway->_api->find_customer($member);

        $accountID = '';
        $visitorID = '';
        $pap_custom = '';
        if (!empty($customer)) {
            $pap_custom = $customer->description;
        }

        if (isset($pap_custom) && ($pap_custom != '')) {
            $visitorID = substr($pap_custom,-32);
            $accountID = substr($pap_custom,0,8);
        }

        if ($subscription->is_trial_eligible()) {
            $total = $invoice->trial_price;
        } else {
            $total = $invoice->total;
        }

        $query = 'AccountId='.$accountID. '&visitorId='.$visitorID.
        '&TotalCost='.$total.'&ProductID='.$subscription->get_membership()->id.'&OrderID='.$subscription->id;

        $user = get_user_by('email', $member->email);
        $query .= '&Data1='.$user->ID;

        self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
        return true;
    }

    public function Membership2TrackRefund($invoice, $subscription) {
        if (get_option(self::MEMBERSHIP2_TRACK_REFUNDS) !== 'true') {
            return false;
        }

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
            return false;
        }
        $ids = $this->getTransactionIDsByOrderID($subscription->id, $session, 'A,P', 1);
        if (empty($ids)) {
            $this->_log(__('Nothing to change, the commission does not exist in PAP'));
            return true;
        }

        $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'makeRefundChargeback', $session);
        $request->addParam('ids', new Gpf_Rpc_Array($ids));
        $request->addParam('status', 'R');
        $request->addParam('merchant_note', 'Refunded automatically from WooCommerce');
        try {
            $request->sendNow();
        } catch (Exception $e) {
            $this->_log(__('A problem occurred while transaction status change with API: ') . $e->getMessage());
            return false;
        }

        return true;
    }

    public function Membership2PayPalButton($html, $subscription, $gateway) {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        $ip = 'pap_IP='.postaffiliatepro::getRemoteIp().'&';
        $snippet = '<!-- Post Affiliate Pro integration snippet -->';
        
        $contentToAsync = 'PostAffTracker.setAccountId(\''.postaffiliatepro_Base::getAccountName().'\');
              PostAffTracker.writeCookieToCustomField(\'notify_url\', \'\', \''.$ip.'pap_custom\');';
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) != 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }
        
        $snippet .= $result.'<!-- /Post Affiliate Pro integration snippet -->';

        $html = str_replace('</form>', '</form>'.$snippet, $html);
        return $html;
    }

    public function Membership2StripeButton($html, $subscription, $gateway) {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        $snippet = postaffiliatepro::addHiddenFieldToPaymentForm(true);
        $html = str_replace('</form>', $snippet.'</form>', $html);
        return $html;
    }

    public function Membership2StripeCustomer($customer, $member, $gateway) {
        if (get_option(self::MEMBERSHIP2_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        if (empty($customer)) {
            $customer = Stripe_Customer::create(
                array(
                    'card' => $token,
                    'email' => $member->email,
                    'description' => $_REQUEST['pap_custom']
                )
            );
            $member->set_gateway_profile($gateway::ID, 'customer_id', $customer->id);
            $member->save();
        }
        return $customer;
    }
}

$submenuPriority = 23;
$integration = new postaffiliatepro_Form_Settings_Membership2();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_action('ms_gateway_paypalstandard_payment_processed_', array($integration, 'Membership2TrackSale'), 99, 2);
add_action('ms_gateway_paypalstandard_payment_processed_paid', array($integration, 'Membership2TrackSale'), 99, 2);
add_action('ms_gateway_paypalstandard_payment_processed_denied', array($integration, 'Membership2TrackRefund'), 99, 2);
add_filter('ms_controller_gateway_purchase_button_paypalstandard', array($integration, 'Membership2PayPalButton'), 99, 3);
add_filter('ms_controller_gateway_purchase_button_stripeplan', array($integration, 'Membership2StripeButton'), 99, 3);
add_filter('ms_gateway_stripe_find_customer', array($integration, 'Membership2StripeCustomer'), 1, 3);
add_filter('ms_gateway_stripe_process_purchase', array($integration, 'Membership2StripeTransaction'), 1, 2);
