<?php
class postaffiliatepro_Util_UpdateDB {

    const PAP_PLUGIN_DB_VERSION = 'postaffiliatepro_db_version';

    public function postAffiliateCheckForUpdates () {
        $lastDbVersion = get_option(self::PAP_PLUGIN_DB_VERSION);
        if ($lastDbVersion === false) {
            $lastDbVersion = '1.18.4';
        }
        if (version_compare('1.19.0', $lastDbVersion, '>')) {
            $this->update_1_19_0();
        }
        if (version_compare('1.21.3', $lastDbVersion, '>')) {
            $this->update_1_21_3();
        }
    }

    private function update_1_19_0() {
        if (get_option('memberpress-enable-lifetime') === 'true') {
            update_option(postaffiliatepro_Form_Settings_MemberPress::MEMBERPRESS_DATA1, 'u_ID');
        }
        update_option(self::PAP_PLUGIN_DB_VERSION, '1.19.0');
    }

    private function update_1_21_3() {
        if (get_option(postaffiliatepro_Form_Settings_WooComm::WOOCOMM_ORDERID_SETTING) === false) {
            update_option(postaffiliatepro_Form_Settings_WooComm::WOOCOMM_ORDERID_SETTING, postaffiliatepro_Form_Settings_WooComm::WOOCOMM_ORDER_ID);
        }
        update_option(self::PAP_PLUGIN_DB_VERSION, '1.21.3');
    }
}

$update = new postaffiliatepro_Util_UpdateDB();
add_action('admin_init', array(
        $update,
        'postAffiliateCheckForUpdates'
), 10);