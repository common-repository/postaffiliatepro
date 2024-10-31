<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.9.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_Debugging extends postaffiliatepro_Form_Base {
    const DEBUGGING_CONFIG_PAGE = 'debugging-config-page';
    const DEBUGGING_ENABLED = 'debugging-enabled';
    const DEBUG_FILE = '/postaffiliatepro/log.txt';

    public function __construct() {
        parent::__construct(self::DEBUGGING_CONFIG_PAGE, 'options.php');
    }

    public function initSettings() {
        register_setting(self::DEBUGGING_CONFIG_PAGE, self::DEBUGGING_ENABLED);
        register_setting(self::DEBUGGING_CONFIG_PAGE, self::DEBUG_FILE);
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/Debugging.xtpl';
    }

    protected function initForm() {
        $this->addHtml('debug_info', '');
        $debugFile = WP_PLUGIN_DIR . self::DEBUG_FILE;

        if (get_option(self::DEBUGGING_ENABLED) === 'true') {
            if (!file_exists(WP_PLUGIN_DIR . '/postaffiliatepro/.htaccess')) {
                $content = "<Files \"log.txt\">\n";
                $content .= "require all denied\n";
                $content .= "require host localhost\n";
                $content .= "</Files>\n";

                file_put_contents(WP_PLUGIN_DIR .'/postaffiliatepro/.htaccess', $content);
            }
        } else {
            // delete the file
            if (file_exists(WP_PLUGIN_DIR . '/postaffiliatepro/.htaccess')) {
                unlink(WP_PLUGIN_DIR . '/postaffiliatepro/.htaccess');
            }
            if (file_exists($debugFile)) {
                unlink($debugFile);
            }
        }

        // check if log file exists
        if (file_exists($debugFile)) {
            $content = file_get_contents($debugFile);
            $html = '<h3>Debug file</h3><textarea readonly rows="20" style="width: 100%;font-family: monospace;">'.$content.'</textarea>';
            $this->addHtml('debug_info', $html);
        }

        $this->addCheckbox(self::DEBUGGING_ENABLED);

        $this->addSubmit();
    }

    public function addPrimaryConfigMenu() {
        add_submenu_page('pap-top-level-options-handle', __('Debugging','pap-menu'), __('Debugging','pap-menu'), 'manage_options', 'pap-debugging-page', array($this, 'printDebuggingPage'));
    }

    public function printDebuggingPage() {
        $this->render();
    }
}

$submenuPriority = 95;
$integration = new postaffiliatepro_Form_Settings_Debugging();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);