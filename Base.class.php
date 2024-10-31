<?php
if (!class_exists('postaffiliatepro_Base')) {
    class postaffiliatepro_Base {
        const IMG_PATH = 'resources/img/';
        const URL_SEPARATOR = '/';
        const CSS_PATH = 'resources/css/';
        const UNSUCCESSFUL_LOGIN_COUNT = 'pap-unsuccessful-login-counter';
        const FIRST_UNSUCCESSFUL_LOGIN = 'pap-first-unsuccessful-login';
        const UNSUCCESSFUL_LOGIN_TIME_LIMIT = 900;
        private static $session = null;
        private static $campaignHelper = null;
        private $error = '';

        public static function _log($message) {
            if (get_option(postaffiliatepro_Form_Settings_Debugging::DEBUGGING_ENABLED) === 'true') {
                if (is_array($message) || is_object($message)) {
                    $message = print_r($message, true);
                }
                try {
                    $f = fopen(WP_PLUGIN_DIR . postaffiliatepro_Form_Settings_Debugging::DEBUG_FILE, 'a');
                    fwrite($f, date('Y.m.d H:i:s', time()).':'.$message."\n");
                    fclose($f);
                } catch (Exception $e) {
                    // something went wrong
                    return false;
                }
            }
            return true;
        }

        /**
         * @return postaffiliatepro_Util_CampaignHelper
         */
        protected function getCampaignHelper() {
            if (self::$campaignHelper) {
                return self::$campaignHelper;
            }
            self::$campaignHelper = new postaffiliatepro_Util_CampaignHelper();
            return self::$campaignHelper;
        }

        public static function getAccountName() {
            if (get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME) == '') {
                return postaffiliatepro::DEFAULT_ACCOUNT_NAME;
            }
            return get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME);
        }

        public function getError() {
            return $this->error;
        }

        protected function getPapVersion() {
            $url = get_option(postaffiliatepro::PAP_URL_SETTING_NAME);
            if ($url == '') {
                return false;
            }

            if (substr($url, -1) != '/') {
                $url .= '/';
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . 'api/version.php');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $result = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error != '') {
                self::_log('Unable to parse application version number, curl error: ' . $error);
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($result);
            if (!$xml) {
                $msg = '';
                foreach (libxml_get_errors() as $error) {
                    $msg .= $error->message;
                }
                self::_log('Unable to parse application version number: ' . $msg);
                return _('unknown (possible less than 4.5.48.1)') . $msg;
            }

            update_option(postaffiliatepro::PAP_VERSION, (string) $xml->applications->pap->versionNumber);
            return (string) $xml->applications->pap->versionNumber;
        }

        protected function getApiSessionUrl($tryDifferentProtocol = false) {
            $url = get_option(postaffiliatepro::PAP_URL_SETTING_NAME);
            if (substr($url, -1) != '/') {
                $url .= '/';
            }
            
            if ($tryDifferentProtocol) {
                if (strpos($url, 'http://') === 0) {
                    $url = str_replace('http://', 'https://', $url);
                } else {
                    if (strpos($url, 'https://') === 0) {
                        $url = str_replace('https://', 'http://', $url);
                    }
                }
                if (strpos($url, 'http') === false) {
                    $url = 'https://'.$url;
                }
                update_option(postaffiliatepro::PAP_URL_SETTING_NAME, $url);
            }
            return $url . 'scripts/server.php';
        }

        /**
         * @return Pap_Api_Session
         */
        protected function getApiSession($isFromConfigurationScreen = false) {
            if (self::$session !== null && !$isFromConfigurationScreen) {
                return self::$session;
            }
            $unsuccessfulLogins = get_option(self::UNSUCCESSFUL_LOGIN_COUNT);
            if (!$isFromConfigurationScreen && $this->isLoginLimitExceeded($unsuccessfulLogins)) {
                self::_log(__('Invalid session counter exceeded. Fix credentials in plugin configuration.'));
                return null;
            }
            $session = new Pap_Api_Session($this->getApiSessionUrl());
            try {
                $login = $session->login(get_option(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME), get_option(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME));
            } catch(Exception $e) {
                self::_log('API call error: '.$e->getMessage());
                self::_log('Trying different protocol now');
                $session = new Pap_Api_Session($this->getApiSessionUrl(true));
                try {
                    $login = $session->login(get_option(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME), get_option(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME));
                } catch(Exception $e) {
                    self::_log('API call error: '.$e->getMessage());
                    return null;
                }
            }

            try {
                if ($login == false) {
                    if (!$isFromConfigurationScreen) {
                        $firstUnsuccessfulLoginTimestamp = get_option(self::FIRST_UNSUCCESSFUL_LOGIN);
                        if ($unsuccessfulLogins == 0 || $firstUnsuccessfulLoginTimestamp == 0 || (time() - $firstUnsuccessfulLoginTimestamp) > self::UNSUCCESSFUL_LOGIN_TIME_LIMIT) {
                            update_option(self::FIRST_UNSUCCESSFUL_LOGIN, time());
                        }
                        update_option(self::UNSUCCESSFUL_LOGIN_COUNT, $unsuccessfulLogins + 1);
                    }
                    $this->error = $session->getMessage();
                    self::_log(__('Unable to login into PAP installation with given credentails: ') . $session->getMessage());
                    self::$session = '0';
                    return null;
                }
                self::_log('Login with API was successful');
                // enable hashing if available
                if (get_option(postaffiliatepro::HASHED_TRACKING_SCRIPT) == '') {
                    $request = new Gpf_Rpc_DataRequest('Pap_Merchants_Tools_IntegrationMethods', 'getHashScriptNameParams', $session);

                    try {
                        $request->sendNow();
                        $data = $request->getData();
                        update_option(postaffiliatepro::HASHED_TRACKING_SCRIPT, $data->getValue('hashTrackingScriptsValue'));
                        self::_log('Hashing will be used, hashed script is '.$data->getValue('hashTrackingScriptsValue'));
                    }
                    catch(Exception $e) {
                        self::_log("API call error for 'getHashScriptNameParams': ".$e->getMessage());
                        update_option(postaffiliatepro::HASHED_TRACKING_SCRIPT, '0');
                    }
                }
            } catch (Gpf_Api_IncompatibleVersionException $e) {
                $this->error = 'Unable to login into PAP installation because of icompatible versions (probably your API file here in WP installation is older than your PAP installation)';
                self::_log(__($this->error));
                return null;
            }
            update_option(self::UNSUCCESSFUL_LOGIN_COUNT, 0);
            update_option(self::FIRST_UNSUCCESSFUL_LOGIN, 0);
            self::$session = $session;
            return $session;
        }
        
        private function isLoginLimitExceeded($unsuccessfulLogins) {
            $firstUnsuccessfulLoginTimestamp = get_option(self::FIRST_UNSUCCESSFUL_LOGIN);
            if ($firstUnsuccessfulLoginTimestamp == 0) {
                return false;
            }
            if ($unsuccessfulLogins > 4 && (time() - $firstUnsuccessfulLoginTimestamp) < self::UNSUCCESSFUL_LOGIN_TIME_LIMIT) {
                return true;
            }
            return false;
        }

        public function changeAffiliateStatus($email, $status) {
            $session = $this->getApiSession();
            if ($session === null || $session === '0') {
                self::_log(__('We have no session to PAP installation! Affiliate status change failed.'));
                return;
            }
            $affiliate = new Pap_Api_Affiliate($session);
            $affiliate->setUsername($email);
            try {
                $affiliate->load();
            } catch (Exception $e) {
                // try notification email as well
                $affiliate->setUsername('');
                $affiliate->setNotificationEmail($email);
                try {
                    $affiliate->load();
                } catch (Exception $e) {
                    self::_log(__('Affiliate not found by email ').$email);
                    return false;
                }
            }
            $affiliate->setStatus($status);
            try {
                $affiliate->save();
                self::_log(__('Affiliate status changed'));
                return true;
            } catch (Exception $e) {
                self::_log(__('Error changing affiliate status: ').$e->getMessage());
                return false;
            }
        }

        public function changeOrderStatus($orderId, $status) {
            self::_log('Changing commission status for order ' . $orderId . ' to ' . $status);
            $session = $this->getApiSession();
            if ($session === null || $session === '0') {
                self::_log(__('We have no session to PAP installation! Transaction status change failed.'));
                return false;
            }
            if (method_exists('Pap_Api_Transaction', 'approveByOrderId')) {
                $transaction = new Pap_Api_Transaction($session);
                $transaction->setOrderId($orderId);
                if ($status === 'A') { // for approval
                    $transaction->approveByOrderId();
                    return true;
                }
                if ($status === 'D') { // for declining
                    $transaction->declineByOrderId();
                    return true;
                }
                self::_log('Status change failed, status ' . $status . ' is not supported.');
                return false;
            }
            $ids = $this->getTransactionIDsByOrderID($orderId, $session, $status);
            if (empty($ids)) {
                self::_log('No existing transactions found for order ID ' .$orderId . '. Since your API version is old (you can update it), we cannot change status of unprocessed transactions with it. Ending.');
                return false;
            }
            $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'changeStatus', $session);
            $request->addParam('ids', new Gpf_Rpc_Array($ids));
            $request->addParam('status', $status);
            try {
                $request->sendNow();
                self::_log('Status of transactions with IDs: "' . implode(', ', $ids) . '" have been changed to ' . $status);
            } catch (Exception $e) {
                self::_log('Status change failed with error: ' . $e->getMessage());
                return false;
            }
            return true;
        }

    public function getTransactionIDsByOrderID($orderId, $session, $status, $limit = 100) {
            $ids = array();
            if (($orderId == '') || $orderId == null) {
                return $ids;
            }
            $request = new Pap_Api_TransactionsGrid($session);
            $request->addFilter('orderid', Gpf_Data_Filter::LIKE, $orderId . '(%');
            $request->setLimit(0, $limit);
            if ($status === 'A') {
                $request->addFilter('rstatus', '=', 'P'); // load only pending for approval
            }
            if ($status === 'P') {
                $request->addFilter('rstatus', '=', 'A'); // load only approved to change to pending (this should not ever happen)
            }
            if ($limit === 1) {
                $request->addParam('sort_col', 'dateinserted');
                $request->addParam('sort_asc', 'false');
                $request->addFilter('rstatus', 'IN', $status);
            }

            try {
                $request->sendNow();
                $grid = $request->getGrid();
                $recordset = $grid->getRecordset();

                if ($recordset->getSize() == 0) {
                    return $ids;
                }

                if ($limit == 1) {
                    // load the transaction and use it's 'dateinserted' to filter all commissions of that
                    // day for the orderID - this way we will load also tier commission of subscription
                    foreach ($recordset as $rec) {
                        $dateinserted = $rec->get('dateinserted');
                    }
                    $request2 = new Pap_Api_TransactionsGrid($session);
                    $request2->addFilter('orderid', Gpf_Data_Filter::LIKE, $orderId . '(%');
                    $request2->addFilter('dateinserted', Gpf_Data_Filter::EQUALS, $dateinserted);
                    $request2->sendNow();
                    $grid = $request2->getGrid();
                    $recordset = $grid->getRecordset();
                }

                foreach ($recordset as $rec) {
                    $ids[] = $rec->get('id');
                }
            } catch (Exception $e) {
                self::_log(__('A problem occurred while loading transactions with API: ') . $e->getMessage());
            }
            return $ids;
        }

        public function loadTransactionsByOrderID($orderId, $session, $status = 'A') {
            if (($orderId == '') || $orderId == null) {
                return null;
            }
            $request = new Pap_Api_TransactionsGrid($session);
            $request->addFilter('orderid', Gpf_Data_Filter::LIKE, $orderId . '(%');
            $request->addParam('columns', new Gpf_Rpc_Array(array(array('id'), array('transid'), array('orderid'), array('commission'),  array('userid'))));
            $request->sendNow();
            $grid = $request->getGrid();
            return $grid->getRecordset();
        }

        public function fireRecurringCommissions($session, $orderId, $total = false) {
            $recurringCommission = new Pap_Api_RecurringCommission($session);
            $recurringCommission->setOrderId($orderId);
            if ($total !== false) {
                $recurringCommission->setTotalCost($total);
            }
            try {
                $recurringCommission->createCommissions();
            } catch (Exception $e) {
                self::_log(__('Can not process recurring commission: ') . $e->getMessage());
                return false;
            }
            return true;
        }

        public function isPluginSet() {
            return (get_option(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME) != '' && get_option(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME) != '');
        }

        protected function getImgUrl() {
            return plugins_url() . self::URL_SEPARATOR . PAP_PLUGIN_NAME . self::URL_SEPARATOR . self::IMG_PATH;
        }

        protected function getCssUrl() {
            return plugins_url() . self::URL_SEPARATOR . PAP_PLUGIN_NAME . self::URL_SEPARATOR . self::CSS_PATH;
        }

        protected function getStylesheetHeaderLink($filename) {
            return '<link type="text/css" rel="stylesheet" href="' . $this->getCssUrl() . $filename . '?ver=' . PAP_PLUGIN_VERSION . '" />' . "\n";
        }

        public static function sendRequest($url, $query = null, $method = 'GET') {
            self::_log("Calling URL $url");
            $curl = curl_init();
            if ($method === 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                curl_setopt($curl, CURLOPT_URL, str_replace('https://', 'http://', $url));
            } else {
                if (is_array($query)) {
                    $query = http_build_query($query);
                }
                curl_setopt($curl, CURLOPT_URL, $url . ((strpos($url, '?') === false) ? '?' : '&') . $query);
            }

            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // usually http -> https
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if (!$response) {
                self::_log($error);
                return false;
            }

            return true;
        }
    }
}