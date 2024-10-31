<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class Shortcode_Affiliate extends postaffiliatepro_Base {
    const SHORTCODES_SETTINGS_PAGE_NAME = 'shortcodes-settings-page';
    const AFFILAITE_SHORTCODE_CACHE = 'affiliate-shortcode_cache';
    /**
     *
     * @var Shortcode_Cache
     */
    private static $cache = null;

    public function __construct() {
        if (self::$cache === null) {
            self::$cache = new Shortcode_Cache();
        }
    }

    public function getAffiliateShortCode($attr, $content = null) {
        return $this->getCode($attr, $content);
    }

    public function getParentAffiliateShortCode($attr, $content = null) {
        return $this->getCode($attr, $content, true);
    }

    /**
     * @return Pap_Api_Affiliate
     */
    private function loadAffiliate(Pap_Api_Session $session, $parent = false) {
        global $current_user;
        $affiliate = new Pap_Api_Affiliate($session);
        $affiliate->setRefid($current_user->user_nicename, Pap_Api_Affiliate::OPERATOR_EQUALS);
        try {
            $affiliate->load();
        } catch (Exception $e) {
            // try it with notification email as well
            $this->_log(__('Unable to load affiliate').' '.__('by referral ID'));
            try {
                $affiliate->setRefid('');
                $affiliate->setNotificationEmail($current_user->user_email);
                $affiliate->load();
            } catch (Exception $e) {
                // last try - username
                $this->_log(__('Unable to load affiliate').' '.__('by notification email'));
                try {
                    $affiliate->setNotificationEmail('');
                    $affiliate->setUsername($current_user->user_email, Pap_Api_Affiliate::OPERATOR_EQUALS);
                    $affiliate->load();
                } catch (Exception $e) {
                    $this->_log(__('Unable to load affiliate').' '.__('by username'));
                    $this->_log(__('Loading user %s failed', $current_user->nickname));
                    return null;
                }
            }
        }

        if ($parent) {
            $parentId = $affiliate->getParentUserId();
            if ($parentId) {
                $parentAffiliate = new Pap_Api_Affiliate($session);
                $parentAffiliate->setUserid($parentId);
                $parentAffiliate->load();
                return $parentAffiliate;
            }
            return null;
        }
        return $affiliate;
    }

    public function getCode($atts, $content = null, $parent = false) {
        global $current_user;
        if ($current_user->ID == 0) {
            return;
        }
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log('Error getting session for login to PAP. Check WP logs for details.');
            return;
        }
        $affiliate = $this->loadAffiliate($session, $parent);
        if ($affiliate == null) {
            $this->_log('Error getting affiliate');
            return;
        }
        if (array_key_exists('item', $atts)) {
            switch ($atts['item']) {
                case 'name':
                    return $affiliate->getFirstname() . ' ' . $affiliate->getLastname();
                case 'loginurl':
                case 'loginurl_raw':
                    if ($parent) return '';
                    $caption = 'Affiliate panel';
                    $class = '';
                    if (array_key_exists('caption', $atts)) {
                        $caption = $atts['caption'];
                    }
                    if (array_key_exists('class', $atts)) {
                        $class = $atts['class'];
                    }

                    wc_enqueue_js("$('#pap-login-url').on('click', function() {
                        $('#loader').show();
                        papGetLoginUrl();
                	});

                    function papGetLoginUrl() {
                        var d = {
                        	action: 'pap_login_redirect_ajax'
                        };

                        $.post('".admin_url('admin-ajax.php')."', d, function(response) {
                        	if (response.success === true) {
                                window.open(response.data.newUrl, '_parent');
                        	} else {
                                console.log(response.data.error);
                        	}
                        });
                    }"
                    );

                    if ($atts['item'] == 'loginurl') { // return button
                        $loader = '<style>#loader {
                          display: inline-block;
                          width: 1em;
                          height: 1em;
                          border: 3px solid rgba(255,255,255,.3);
                          border-radius: 50%;
                          border-top-color: #000;
                          padding-left: 1em;
                          animation: spin 1s ease infinite;
                          -webkit-animation: spin 1s ease infinite;
                        }</style>';
                        return $loader.'<input id="pap-login-url" type="submit" value="'.$caption.'" class="'.$class.'"><span id="loader" style="display: none"></span>';
                    } else { // return <a>
                        return '<a href="'.$this->getLoginUrl($affiliate, $session).'" target="_parent" class="'.$class.'">'.$caption.'</a>';
                    }
                case 'unpaidCommissions':
                    $timeframe = '';
                    if (array_key_exists('timeframe', $atts)) {
                        $timeframe = $atts['timeframe'];
                    }
                    return $this->getAffiliateCommissionsValue($affiliate->getUserid(), $timeframe, $session);
            }
            return $affiliate->getField($atts['item']);
        }
    }

    public function loginRedirectAjaxCallback() {
        // verify logged user again, as this is an Ajax call
        global $current_user;
        if ($current_user->ID == 0) {
            wp_send_json_error(array('error' => 'No authenticated user recognized'));
            wp_die();
            return;
        }
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log('Error getting session for login to PAP. Check WP logs for details.');
            wp_send_json_error(array('error' => 'Error getting session for login to PAP.'));
            wp_die();
            return;
        }
        $affiliate = $this->loadAffiliate($session);
        if ($affiliate == null) {
            $this->_log('Error getting affiliate');
            wp_send_json_error(array('error' => 'Affiliate account not found in PAP.'));
            wp_die();
            return;
        }
        $newUrl = $this->getLoginUrl($affiliate, $session);
        wp_send_json_success(array('newUrl' => $newUrl));

    }

    private function getAffiliateCommissionsValue($affiliateId, $timeframe, $session) {
        $request = new Gpf_Rpc_GridRequest('Pap_Merchants_Payout_PayAffiliatesGrid', 'getRows', $session);
        $request->setLimit(0, 1);
        $request->addParam('columns', new Gpf_Rpc_Array(array(array('userid'), array('amounttopay'))));
        $request->addFilter('rstatus', '=', 'A');
        $request->addFilter('payoutstatus', '=', 'U');
        $request->addFilter('userid', '=', $affiliateId);

        $dateRangeValue = $this->getDateRangeValue($timeframe);
        if ($dateRangeValue != '') {
            $request->addFilter('dateinserted', Gpf_Data_Filter::DATERANGE_IS, $dateRangeValue);
        }
        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->_log("API call error: ".$e->getMessage());
            return "0";
        }

        $grid = $request->getGrid();
        $recordset = $grid->getRecordset();
        if ($recordset->getSize() == 0) {
            return "0";
        }

        foreach($recordset as $rec) {
            return $rec->get('amounttopay');
        }
    }

    private function getDateRangeValue($timeframe) {
        switch (strtolower($timeframe)) {
            case 'yesterday':
                $dateRangeValue = Gpf_Data_Filter::RANGE_YESTERDAY;
                break;
            case 'week':
            case 'lastweek':
            case 'last week':
            case 'w':
                $dateRangeValue = Gpf_Data_Filter::RANGE_LAST_WEEK;
                break;
            case 'month':
            case 'lastmonth':
            case 'last month':
            case 'm':
                $dateRangeValue = Gpf_Data_Filter::RANGE_LAST_MONTH;
                break;
            case 'year':
            case 'thisyear':
            case 'this year':
            case 'y':
                $dateRangeValue = Gpf_Data_Filter::RANGE_THIS_YEAR;
                break;
            case 'lastyear':
            case 'last year':
                $dateRangeValue = Gpf_Data_Filter::RANGE_LAST_YEAR;
                break;
            case '30':
                $dateRangeValue = Gpf_Data_Filter::RANGE_LAST_30_DAYS;
                break;
            default:
                $dateRangeValue = '';
                break;
        }
        return $dateRangeValue;
    }
    private function getLoginUrl($affiliate, $session) {
        $request = new Gpf_Rpc_FormRequest('Pap_Auth_LoginKeyService', 'getLoginKey', $session);
        $request->addParam('userId', $affiliate->getUserid());
        
        $loginKey = '';
        try {
            $request->sendNow();
            $response = $request->getStdResponse();
            
            if ($response->success == 'Y') {
                $loginKey = '?LoginKey='.$request->getForm()->getFieldValue('LoginKey');
            } else {
                $this->_log('Error loading login key: '.$response->message);
            }
        } catch(Exception $e) {
            $this->_log('API loading login key failed: '.$e->getMessage());
        }

        if ($loginKey != '') {
            return rtrim(get_option(postaffiliatepro::PAP_URL_SETTING_NAME), '/') . '/affiliates/login.php' . $loginKey;
        }
        
        // a backup method:
        $email = $affiliate->getUsername();
        if (strpos($email, '@') === 0) {
            $email = $affiliate->getNotificationEmail();
        }
        return rtrim(get_option(postaffiliatepro::PAP_URL_SETTING_NAME), '/') . '/affiliates/login.php?username=' . urlencode($email);
    }

    public function initSettings() {
        register_setting(self::SHORTCODES_SETTINGS_PAGE_NAME, self::AFFILAITE_SHORTCODE_CACHE);
    }
}

$shortcodeAffiliate = new Shortcode_Affiliate();
add_action('admin_init', array($shortcodeAffiliate, 'initSettings'), 99);
add_shortcode('affiliate', array($shortcodeAffiliate, 'getAffiliateShortCode'));
add_shortcode('parent', array($shortcodeAffiliate, 'getParentAffiliateShortCode'));
// AJAX
add_action('wp_ajax_pap_login_redirect_ajax', array($shortcodeAffiliate, 'loginRedirectAjaxCallback'), 99);