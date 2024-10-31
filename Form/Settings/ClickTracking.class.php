<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_ClickTracking extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::CLICK_TRACKING_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/ClickTrackingSettings.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(postaffiliatepro::CLICK_TRACKING_ENABLED_SETTING_NAME);

        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(postaffiliatepro::CLICK_TRACKING_CAMPAIGN, $campaigns);

        $this->addSubmit();
    }

    protected function getOption($name, $type = 'none') {
        if ($name === postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME) {
            return self::getAccountName();
        }
        return parent::getOption($name);
    }
}