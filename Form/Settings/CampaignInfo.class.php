<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_CampaignInfo extends postaffiliatepro_Form_Base {

    const ADD_TO_CAMPAIGN = 'add-to-campaign';
    const SEND_NOTIFICATION_EMAIL = 'send-notification-mail';

    /**
     * @var postaffiliatepro_Util_CampaignHelper
     */
    private $campaignHelper;
    private $campaignId;
    private $optionsPrefix;

    public function __construct($campaignId,postaffiliatepro_Util_CampaignHelper $campaignHelper) {
        $this->optionsPrefix = postaffiliatepro::SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME;
        $this->campaignId = $campaignId;
        $this->campaignHelper = $campaignHelper;
        parent::__construct();
    }

    private function isPublic() {
        return $this->campaignHelper->getCampaignType($this->campaignId) == postaffiliatepro_Util_CampaignHelper::CAMPAIGN_TYPE_PUBLIC;
    }

    protected function getTemplateFile() {
        if ($this->isPublic()) {
            return WP_PLUGIN_DIR . '/postaffiliatepro/Template/CampaignInfo-Public.xtpl';
        }
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/CampaignInfo-Private.xtpl';
    }

    protected function getOption($name, $type = 'none') {
        $value = get_option($this->optionsPrefix);
        if (!is_array($value)) {
            return '';
        }
        $name = str_replace(array('[',']', $this->optionsPrefix), '', $name);
        if (!array_key_exists($name, $value)) {
            return '';
        }
        return $value[$name];
    }

    protected function initForm() {
        $this->addHtml('campaign-name', $this->campaignHelper->getCampaignName($this->campaignId));
        $this->addHtml('campaign-type', $this->campaignHelper->getTypeAsText($this->campaignHelper->getCampaignType($this->campaignId)));
        $this->addHtml('campaign-id', $this->campaignId);

        if (!$this->isPublic()) {
            $this->addCheckbox($this->optionsPrefix.'['.self::ADD_TO_CAMPAIGN.'-'.$this->campaignId.']', self::ADD_TO_CAMPAIGN);
            $this->addCheckbox($this->optionsPrefix.'['.self::SEND_NOTIFICATION_EMAIL.'-'.$this->campaignId.']', self::SEND_NOTIFICATION_EMAIL);
        }
    }

    public function render($toVar = false, $template = '') {
        return parent::render(true);
    }
}