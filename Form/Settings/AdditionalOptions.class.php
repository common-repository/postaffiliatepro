<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.9.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_AdditionalOptions extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::GENERAL_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/AdditionalOptions.xtpl';
    }

    protected function initForm() {
    }
}