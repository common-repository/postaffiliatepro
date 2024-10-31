<?php
/**
 *   @copyright Copyright (c) 2018 Quality Unit s.r.o.
 *   @author Martin Svitek
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.15.1
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_PPBuyNowButton extends postaffiliatepro_Form_Base {
    const WPECPP_COMMISSION_ENABLED = 'wpecpp-commission-enabled';
    const WPECPP_CONFIG_PAGE = 'wpecpp-config-page';

    public function __construct() {
        parent::__construct(self::WPECPP_CONFIG_PAGE, 'options.php');
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WPECPP_COMMISSION_ENABLED);
    }

    protected function initForm() {
        '';
    }
    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/PPBuyNowButtonConfig.xtpl';
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::WPECPP_COMMISSION_ENABLED) === 'true') {
            add_submenu_page('integrations-config-page-handle', __('PayPal Buy Now Button', 'pap-integrations'), __('PayPal Buy Now Button', 'pap-integrations'), 'manage_options', 'wpecppintegration-settings-page', array(
                    $this,
                    'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function integratePayPalButton($output, $shortcode, $attr){
        if('wpecpp' !== $shortcode){
            return $output;
        }
        if (get_option(self::WPECPP_COMMISSION_ENABLED) !== 'true') {
            return $output;
        }
        $url = postaffiliatepro::parseServerPathForClickTrackingCode();
        $integration = "<input id='pap_dx8vc2s5' type='hidden' name='custom' value=''>";
        $integration .= "<input type='hidden' name='notify_url' value='http://{$url}plugins/PayPal/paypal.php'>";

        $async = '';
        if (get_option(postaffiliatepro::ASYNC_ENABLED) == 'true') {
            $async = ' async';
        }

        $integration .= "<script$async id='pap_x2s6df8d' src='//{$url}scripts/notifysale.php' type='text/javascript'></script>";
        $output = substr_replace($output, $integration, strpos($output, '</form>'), 0);
        return $output;
    }
}
$submenuPriority = 30;
$integration = new postaffiliatepro_Form_Settings_PPBuyNowButton();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);
add_filter( 'do_shortcode_tag', array(
        $integration,
        'integratePayPalButton'
),10,3);