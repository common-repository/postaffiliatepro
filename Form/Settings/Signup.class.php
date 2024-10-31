<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_Signup extends postaffiliatepro_Form_Base {
    private $campaignHelper = null;
    private $isValidSession = true;

    public function __construct() {
        parent::__construct(postaffiliatepro::SIGNUP_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/SignupSettings.xtpl';
    }

    private function getAffiliatesSelectData() {
        $data = array('0' => ' ', 'from_cookie' => 'resolve automatically');

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('Unable to connect to your Post Affiliate Pro installation'));
            return $data;
        }
        $request = new Pap_Api_AffiliatesGrid($session);
        $request->addParam('columns', new Gpf_Rpc_Array(array(array('id'), array('username'), array('firstname'), array('lastname'))));
        $request->setLimit(0, 5000);
        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->_log(__('API call error: ') . $e->getMessage());
            return $data;
        }
        $grid = $request->getGrid();
        $recordset = $grid->getRecordset();
        foreach($recordset as $rec) {
            $data[$rec->get('id')] = $rec->get('username') . ' (' . htmlspecialchars($rec->get('firstname')).' '.htmlspecialchars($rec->get('lastname')).')';
        }
        return $data;
    }

    protected function initForm() {
        if (!$this->isValidSession) {
            return;
        }
        $this->addSelect(postaffiliatepro::SIGNUP_DEFAULT_STATUS_SETTING_NAME, array('' => 'default', 'A' => 'Approved', 'P' => 'Pending', 'D'=>'Declined'));
        $this->addSelect(postaffiliatepro::SIGNUP_AFFILIATE_USERNAME, array('email' => 'User email', 'username' => 'Username'));
        $this->addSelect(postaffiliatepro::SIGNUP_DEFAULT_PARENT_SETTING_NAME, $this->getAffiliatesSelectData());
        $this->addCheckbox(postaffiliatepro::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME);
        $this->addCheckbox(postaffiliatepro::SIGNUP_INTEGRATION_USE_PHOTO);
        $this->addSelect(postaffiliatepro::SIGNUP_INTEGRATION_SAVE_LEVEL, $this->getDataFieldsArray());
        $this->addSubmit();

        $campaignsForm = new postaffiliatepro_Form_Settings_Campaigns($this->getCampaignHelper());
        $this->addHtml('campaigns-content', $campaignsForm->render(true));
    }

    private function getDataFieldsArray() {
        $data = array(0 => '');
        for ($i = 1; $i<=25; $i++) {
            $data[$i] = 'Data '.$i;
        }
        return $data;
    }

    public function render($toVar = false, $template = '') {
        $session = $this->getApiSession();
        if ($session !== null && $session !== '0') {
            parent::render();
            return;
        }
        $this->isValidSession = false;
        parent::render(false, WP_PLUGIN_DIR . '/postaffiliatepro/Template/SignupSettingsNoSession.xtpl');
    }
}