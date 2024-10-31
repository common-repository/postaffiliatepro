<?php
class postaffiliatepro_Form_Settings_Integrations extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/IntegrationsConfig.xtpl';
    }

    protected function initForm() {
        // CF7
        $this->addCheckbox(postaffiliatepro_Form_Settings_ContactForm7::CONTACT7_SIGNUP_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_ContactForm7::CONTACT7_SIGNUP_COMMISSION_ENABLED, 'cf7', 'Contact form 7');
        // EDD
        $this->addCheckbox(postaffiliatepro_Form_Settings_EDD::EDD_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_EDD::EDD_COMMISSION_ENABLED, 'edd', 'Easy Digital Downloads');
        // LifterLMS
        $this->addCheckbox(postaffiliatepro_Form_Settings_LifterLMS::LIFTERLMS_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_LifterLMS::LIFTERLMS_COMMISSION_ENABLED, 'lifterlms', 'LifterLMS');
        // Marketpress
        $this->addCheckbox(postaffiliatepro_Form_Settings_Marketpress::MARKETPRESS_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_Marketpress::MARKETPRESS_COMMISSION_ENABLED, 'marketpress', 'Marketpress');
        // MemberPress
        $this->addCheckbox(postaffiliatepro_Form_Settings_MemberPress::MEMBERPRESS_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_MemberPress::MEMBERPRESS_COMMISSION_ENABLED, 'memberpress', 'MemberPress');
        // Membership 2 Pro
        $this->addCheckbox(postaffiliatepro_Form_Settings_Membership2::MEMBERSHIP2_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_Membership2::MEMBERSHIP2_COMMISSION_ENABLED, 'ms2pro', 'Membership 2 Pro');
        // PayPal Buy Now Button
        $this->addCheckbox(postaffiliatepro_Form_Settings_PPBuyNowButton::WPECPP_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_PPBuyNowButton::WPECPP_COMMISSION_ENABLED, 'paypal', 'PayPal Buy Now Button');
        // PMPro
        $this->addCheckbox(postaffiliatepro_Form_Settings_PMPro::PMPRO_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_PMPro::PMPRO_COMMISSION_ENABLED, 'pmpro', 'Post Membership Pro');
        // Recstrict Content Pro
        $this->addCheckbox(postaffiliatepro_Form_Settings_RestrictContentPro::RESTRICTCP_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_RestrictContentPro::RESTRICTCP_COMMISSION_ENABLED, 'rcppap', 'Restrict Content Pro');
        // s2Member
        $this->addCheckbox(postaffiliatepro_Form_Settings_S2Member::S2MEMBER_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_S2Member::S2MEMBER_COMMISSION_ENABLED, 's2member', 's2Member');
        // Simple Pay Pro
        $this->addCheckbox(postaffiliatepro_Form_Settings_SimplePayPro::SIMPLEPAYPRO_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_SimplePayPro::SIMPLEPAYPRO_COMMISSION_ENABLED, 'spp', 'Simple Pay Pro');
        // Stripe Payments
        $this->addCheckbox(postaffiliatepro_Form_Settings_StripePayments::STRIPEPAYMENTS_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_StripePayments::STRIPEPAYMENTS_COMMISSION_ENABLED, 'stripepay', 'Stripe Payments');
        // WishList Member
        $this->addCheckbox(postaffiliatepro_Form_Settings_WishListMember::WLM_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_WishListMember::WLM_COMMISSION_ENABLED, 'wlm', 'WishList Member');
        // WooComm
        $this->addCheckbox(postaffiliatepro_Form_Settings_WooComm::WOOCOMM_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_WooComm::WOOCOMM_COMMISSION_ENABLED, 'woo', 'WooCommerce');
        // WP EasyCart
        $this->addCheckbox(postaffiliatepro_Form_Settings_WPEasyCart::WPEC_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_WPEasyCart::WPEC_COMMISSION_ENABLED, 'wpec', 'WP EasyCart');
        // WPPayForms
        $this->addCheckbox(postaffiliatepro_Form_Settings_WPPayForms::WPPF_COMMISSION_ENABLED);
        $this->imageTpl(postaffiliatepro_Form_Settings_WPPayForms::WPPF_COMMISSION_ENABLED, 'wppf', 'WPPayForms');

        $this->addSubmit();
    }

    private function imageTpl($optionName, $imgName, $alt) {
        $style = '';
        if ($this->getOption($optionName) !== 'true') {
            $style = 'class="greyedOut" ';
        }
        $img = '<img width="128" height="128" '.$style.'src="../wp-content/plugins/postaffiliatepro/resources/img/'.
            $imgName.'.png" alt="'.$alt.' logo" />';
        $this->addVariable('img-'.$imgName, $img);
        return true;
    }
}