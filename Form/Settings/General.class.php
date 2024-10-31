<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_General extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::GENERAL_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/GeneralSettings.xtpl';
    }

    protected function initForm() {
        $this->addUrlTextBox(postaffiliatepro::PAP_URL_SETTING_NAME, 50);
        $this->addTextBox(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME, 30);
        $this->addPassword(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME, 30);
        $this->addTextBox(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME, 30, 'default1');
        $this->addCheckbox(postaffiliatepro::ASYNC_ENABLED);
        $this->checkCredentails();
        $this->addSubmit();
    }

    private function checkCredentails() {
        $this->addHtml('login_check_ok', '');
        $this->addHtml('installation_info', '');
        $this->addHtml('error', '');
        $this->addHtml('notice', '');
        $this->addHtml('initial_info', '');

        if (!$this->isPluginSet()) {
            $content = '<div id="generalInfoContent">
            <div class="column" style="padding-right: 1%;border-left: 2px solid #3333ff;">
                <h3>WHAT IS AFFILIATE SOFTWARE?</h3>
                <div>Affiliate software is software that let\'s you track clicks and signups and assign them to the right affiliates
                with the option of reports and payouts. Affiliate software is used to run an in-house affiliate program with the
                advantage of complete customization and ability to control and manage your affiliates, commissions and setups.
                Starting an affiliate program with affiliate software <a href="https://www.postaffiliatepro.com/">Post Affiliate Pro</a> is fairly easy even non-tech professionals.
                </div>
            </div>
            <div class="column" style="padding-right: 1%;border-left: 2px solid #3399ff;">
                <h3>WHY START WITH AFFILIATE MARKETING?</h3>
                <div>Affiliate marketing or partner marketing is one of the most cost effective online marketing channels. It\'s especially
                popular with SMB, SaaS and eCommerce. Unlike PPC, with affiliate marketing, you only pay for real sales in the form of
                affiliate commission. That way, you don\'t have to pay for advertisement which doesn\'t work. Affiliate marketing improves
                your Page Rank and SEO by having affiliates link back to your website.
                </div>
            </div>

            <div class="column" style="border-left: 2px solid #33ccff;">
                <h3>WHY CHOOSE POST AFFILIATE PRO?</h3>
                <div><a href="https://www.postaffiliatepro.com/">Post Affiliate Pro</a> is the #1 ranked and most reviewed affiliate
                software on the market. It is easy to set up an operate and connects with virtually any website or payment gateway.
                Currently, more than 30,000 businesses trust Post Affiliate Pro as their choice for affiliate software. Post Affiliate Pro
                features rock-solid tracking, scalability and endless customization and grows with your needs. All at for an affordable
                monthly fee.
                </div>
            </div></div>';
            $this->addHtml('initial_info', $content);
            return;
        }

        $session = $this->getApiSession(true);
        $notice = false;
        if ($session !== null && $session !== '0') {
            $this->addHtml('login_check_ok', $this->getSectionCode('Successfully connected to your Post Affiliate Pro installation','style="color:#2B7508;"'));
            $this->addHtml('installation_info', $this->getSectionCode('Application version: <span style="font-style:italic;">'.$this->getPapVersion().'</span>'));
            $versionArray = explode('.',$this->getPapVersion());

            if ($versionArray[0] < 4) {
                $notice = true;
            } elseif ($versionArray[0] === 4) {
                if ($versionArray[1] < 5) {
                    $notice = true;
                } elseif ($versionArray[1] === 5) {
                    if ($versionArray[2] < 67) {
                        $notice = true;
                    } elseif ($versionArray[2] === 67) {
                        if ($versionArray[3] < 3) {
                            $notice = true;
                        }
                    }
                }
            }
        } else {
            $this->addHtml('error', $this->getSectionCode($this->getError(), 'style="color:#ff0000"'));
        }

        if ($notice) {
            $this->addHtml('notice', $this->getSectionCode('Your Post Affilate Pro version should be <strong>4.5.67.3 or higher<strong> to enjoy full functionality. Lower versions will not work properly.'));
        }
    }

    private function getSectionCode($content, $style = '') {
        return '<table class="form-table"><tr><td '.$style.'>'.$content."</td></tr></table>\n";
    }
}
