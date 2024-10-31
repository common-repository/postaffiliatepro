<?php
/**
 *   @copyright Copyright (c) 2023 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.26.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_PMPro extends postaffiliatepro_Form_Base {
    const PMPRO_COMMISSION_ENABLED = 'pmpro-commission-enabled';
    const PMPRO_CONFIG_PAGE = 'pmpro-config-page';
    const PMPRO_PRODUCT_ID = 'pmpro-prodid';

    public $visitorId = '';

    public function __construct() {
        parent::__construct(self::PMPRO_CONFIG_PAGE, 'options.php');
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::PMPRO_COMMISSION_ENABLED);
        register_setting(self::PMPRO_CONFIG_PAGE, self::PMPRO_PRODUCT_ID);
    }

    protected function initForm() {
        $this->addSelect(self::PMPRO_PRODUCT_ID, array(
            'id' => 'membership ID',
            'name' => 'membership name'
        ));
        $this->addSubmit();
    }
    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/PMProConfig.xtpl';
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::PMPRO_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('Paid Membership Pro', 'pap-integrations'), __('Paid Membership Pro', 'pap-integrations'), 'manage_options', 'pmprointegration-settings-page', array(
                    $this,
                    'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    public function pmproSaveAffIdToOrder($order) {
        if ($this->visitorId != '') {
            $order->affiliate_id = $this->visitorId;
            $order->saveOrder();
        }
    }

    public function pmproTrackOrder($userId, $order) {
        if (get_option(self::PMPRO_COMMISSION_ENABLED) !== 'true') {
            return;
        }

        $visitorId = $_COOKIE['PAPVisitorId'];

        if (isset($order->total)) {
            $query = 'AccountID='.postaffiliatepro::getAccountName().'&TotalCost=' . $order->total . '&OrderID=' . $order->code;
            $productId = $order->membership_id;
            if (get_option(self::PMPRO_PRODUCT_ID) == 'name') {
                $level = pmpro_getLevel($order->membership_id);
                $productId = sanitize_title($level->name, 'pmpro-level-' . $order->membership_id);
            }
            $query .= '&ProductID='.$productId.'&visitorId='.$visitorId.'&data1='.$userId;
            self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);

            // save affiliate ID into order
            $order->affiliate_id = $visitorId;
            $order->saveOrder();
        }
    }

    public function pmproTrackAddedOrder($order) {
        if (get_option(self::PMPRO_COMMISSION_ENABLED) !== 'true') {
            return;
        }

        $order->getLastMemberOrder($order->user_id);
        if (!empty($order->affiliate_id)) {
            $visitorId = $order->affiliate_id;

            $query = 'AccountID='.postaffiliatepro::getAccountName().'&TotalCost=' . $order->subtotal . '&OrderID=' . $order->code;
            $productId = $order->membership_id;
            if (get_option(self::PMPRO_PRODUCT_ID) == 'name') {
                $level = pmpro_getLevel($order->membership_id);
                $productId = sanitize_title($level->name, 'pmpro-level-' . $order->membership_id);
            }
            $query .= '&ProductID='.$productId.'&visitorId='.$visitorId.'&data1='.$order->user_id;
            self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);

            // save affiliate ID into order
            $order->affiliate_id = $visitorId;
            $order->saveOrder();
        }
    }
}

$submenuPriority = 32;
$integration = new postaffiliatepro_Form_Settings_PMPro();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);
add_action('pmpro_added_order', array(
    $integration,
    'pmproSaveAffIdToOrder'
), 99);
add_action('pmpro_after_checkout', array(
    $integration,
    'pmproTrackOrder'
), 99, 2);
add_action('pmpro_add_order', array(
    $integration,
    'pmproTrackAddedOrder'
), 99);
