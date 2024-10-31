<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */
class postaffiliatepro_Form_Settings_WooComm extends postaffiliatepro_Form_Base {
    const WOOCOMM_COMMISSION_ENABLED = 'woocomm-commission-enabled';
    const WOOCOMM_CONFIG_PAGE = 'woocomm-config-page';
    const WOOCOMM_ORDERID_SETTING = 'woocomm-orderid';
    const WOOCOMM_PERPRODUCT = 'woocomm-per-product';
    const WOOCOMM_PRODUCT_ID = 'woocomm-product-id';
    const WOOCOMM_DATA1 = 'woocomm-data1';
    const WOOCOMM_DATA2 = 'woocomm-data2';
    const WOOCOMM_DATA3 = 'woocomm-data3';
    const WOOCOMM_DATA4 = 'woocomm-data4';
    const WOOCOMM_DATA5 = 'woocomm-data5';
    const WOOCOMM_CAMPAIGN = 'woocomm-campaign';
    const WOOCOMM_STATUS_UPDATE = 'woocomm-status-update';
    const WOOCOMM_AFFILIATE_APPROVAL = 'woocomm-affiliate-approval';
    const WOOCOMM_TRACK_RECURRING_TOTAL = 'woocomm-track-recurring-total';
    const WOOCOMM_DEDUCT_FEES = 'woocomm-deduct-fees';
    
    const WOOCOMM_ORDER_ID = 'Order ID';
    const WOOCOMM_ORDER_NUMBER = 'Order number';

    public function __construct() {
        parent::__construct(self::WOOCOMM_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WooCommConfig.xtpl';
    }

    protected function initForm() {
        $this->addSelect(self::WOOCOMM_ORDERID_SETTING, array(
                self::WOOCOMM_ORDER_ID => self::WOOCOMM_ORDER_ID,
                self::WOOCOMM_ORDER_NUMBER => self::WOOCOMM_ORDER_NUMBER
        ));
        $this->addCheckbox(self::WOOCOMM_PERPRODUCT);
        $this->addCheckbox(self::WOOCOMM_TRACK_RECURRING_TOTAL);
        $this->addCheckbox(self::WOOCOMM_DEDUCT_FEES);
        $this->addSelect(self::WOOCOMM_PRODUCT_ID, array(
                '0' => ' ',
                'id' => 'product ID',
                'title' => 'product name',
                'var' => 'variation ID',
                'sku' => 'SKU',
                'categ' => 'product category',
                'tag' => 'product tag',
                'role' => 'user role'
        ));
        $this->addCheckbox(self::WOOCOMM_STATUS_UPDATE);

        $productOptions = array(
                '0' => ' ',
                'id' => 'customer ID',
                'email' => 'customer email',
                'name' => 'customer name', // $billing_first_name $billing_last_name
		        'phone' => 'customer phone',
                'pmethod' => 'payment method', // $payment_method_title
                'discount' => 'cart discount', // $cart_discount
                'coupon' => 'coupon code',
                'title' => 'product name',
                'order' => 'order name',
                'order_note' => 'order note'

        );
        $this->addSelect(self::WOOCOMM_DATA1, $productOptions);
        $this->addSelect(self::WOOCOMM_DATA2, $productOptions);
        $this->addSelect(self::WOOCOMM_DATA3, $productOptions);
        $this->addSelect(self::WOOCOMM_DATA4, $productOptions);
        $this->addSelect(self::WOOCOMM_DATA5, $productOptions);
        $this->addTextBox(self::WOOCOMM_AFFILIATE_APPROVAL);

        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array(
                '0' => ' '
        );
        foreach ($campaignList as $row) {
            $campaigns[$row->get('campaignid')] = htmlspecialchars($row->get('name'));
        }
        $this->addSelect(self::WOOCOMM_CAMPAIGN, $campaigns);

        $this->addSubmit();

        $this->addHtml('currentOrderIdSetting', get_option(self::WOOCOMM_ORDERID_SETTING));
        $this->addHtml('currentStatusUpdateSetting', get_option(self::WOOCOMM_STATUS_UPDATE));
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WOOCOMM_COMMISSION_ENABLED);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_ORDERID_SETTING);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_PERPRODUCT);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_PRODUCT_ID);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_STATUS_UPDATE);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA1);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA2);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA3);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA4);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA5);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_CAMPAIGN);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_AFFILIATE_APPROVAL);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_TRACK_RECURRING_TOTAL);
        register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DEDUCT_FEES);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::WOOCOMM_COMMISSION_ENABLED) == 'true') {
            add_submenu_page('integrations-config-page-handle', __('WooCommerce', 'pap-integrations'), __('WooCommerce', 'pap-integrations'), 'manage_options', 'woocommintegration-settings-page', array(
                    $this,
                    'printConfigPage'
            ));
        }
    }

    public function printConfigPage() {
        $this->render();
    }

    public function wooHandleCustomThankYouPages($content) {
        if (!function_exists('wc_get_order')) {
            return $content;
        }
        if (isset($_GET['ctpw'], $_GET['key'], $_GET['order']) && $_GET['ctpw'] !== '' && $_GET['key'] !== '' && $_GET['order'] !== '') {
            $this->wooAddThankYouPageTrackSale($_GET['order']);
            return $content;
        }
        if (isset($_GET['ctpw'], $_GET['key'], $_GET['order-received']) && $_GET['ctpw'] !== '' && $_GET['key'] !== '' && $_GET['order-received'] !== '') {
            $this->wooAddThankYouPageTrackSale($_GET['order-received']);
            return $content;
        }
        if (isset($_GET['order']) && $_GET['order'] !== '') { // Thanks Redirect 3.0
            $this->wooAddThankYouPageTrackSale($_GET['order']);
            return $content;
        }
        if (isset($_GET['wcf-order'], $_GET['wcf-key'], $_GET['wcf-sk']) && $_GET['wcf-order'] !== '' && $_GET['wcf-key'] !== '' && $_GET['wcf-sk'] !== '') {
            $this->wooAddThankYouPageTrackSale($_GET['wcf-order']);
            return $content;
        }
        if (isset($_GET['ctp_order_id'], $_GET['ctp_order_key']) && $_GET['ctp_order_id'] !== '' && $_GET['ctp_order_key'] !== '') {
            $this->wooAddThankYouPageTrackSale($_GET['ctp_order_id']);
            return $content;
        }
        return $content;
    }

    public function wooAddThankYouPageTrackSale($order_id) {
        if (get_option(self::WOOCOMM_COMMISSION_ENABLED) !== 'true') {
            echo "<!-- Post Affiliate Pro sale tracking error - tracking not enabled -->\n";
            return $order_id;
        }
        $order = wc_get_order($order_id);
        if (empty($order)) {
            echo '<!-- Post Affiliate Pro sale tracking error - no order loaded for order ID ' . $order_id . " -->\n";
            return $order_id;
        }
        if (isset($_GET['customGateway'])) {
            if (empty($_REQUEST['cm']) || empty($_REQUEST['tx']) || empty($_REQUEST['st'])) {
                // SKIP THANK YOU PAGE, SALE WILL BE TRACKED FROM PAYPAL IPN
                echo "<!-- Post Affiliate Pro sale tracking - no sale tracker needed -->\n";
                $this->_log('Thank you page sale tracking is skipped (customGateway).');
                return $order_id;
            } else {
                // THIS IS A PDT RESPONSE, NO IPN WILL BE USED/VALIDATED/TRIGGERED, CONTINUE TO THANK YOU PAGE
            }
        }
        // RevCent integration
        $paymentMethod = $order->get_payment_method();
        if ($paymentMethod == 'revcent_payments' || strpos($paymentMethod, 'revcent') !== false) {
            // SKIP THANK YOU PAGE, SALE WILL BE TRACKED FROM REVCENT WEBHOOK
            echo "<!-- Post Affiliate Pro sale tracking - no sale tracker needed -->\n";
            $this->_log('Thank you page sale tracking is skipped (RevCent).');
            return $order_id;
        }
        $this->trackWooOrder($order);

        return $order_id;
    }

    private function trackWooOrder($order) {
        $orderId = $this->getOrderId($order);

        echo "<!-- Post Affiliate Pro sale tracking -->\n";
        $contentToAsync = 'PostAffTracker.setAccountId(\'' . postaffiliatepro::getAccountName() . '\');';

        $status = '';
        if (get_option(self::WOOCOMM_STATUS_UPDATE) === 'true') {
        	$orderStatus = $order->get_status();
        	switch ($orderStatus) {
        		case 'completed': $status = 'A'; break;
        		case 'cancelled': 
        		case 'refunded':
        		case 'failed': $status = 'D'; break;
        		default: $status = '';
        	}
        }
        if ($status === 'D') {
        	$this->_log('The order was not successful, no commissions will be created.');
        	return false;
        }

        try {
            if (method_exists($order, 'get_coupon_codes')) { // for newer versions
                $coupons = $order->get_coupon_codes();
            } else {
                $coupons = $order->get_used_coupons();
            }
            $couponCode = implode(',', $coupons);
        } catch (Exception $e) {
            //
        }

        if (get_option(self::WOOCOMM_PERPRODUCT) === 'true') {
            $parentOrderId = $order->get_parent_id();
            if (get_class($order) == 'AWCDP_Order' && !empty($parentOrderId)) {
                $order = wc_get_order($order->get_parent_id());
                $orderId = $this->getOrderId($order);
                // as the order uses the deposit plugin, the original order is definitely not paid so we will set pending status here
                $status = 'P';
            }
            
            $i = 1;
            $count = count($order->get_items());
            foreach ($order->get_items() as $item) {
                $itemprice = $item['line_total'];

                $contentToAsync .= "var sale$i = PostAffTracker.createSale();\n";
                $contentToAsync .= "sale$i.setTotalCost('" . $itemprice . "');\n";
                $contentToAsync .= "sale$i.setOrderID('$orderId($i)');\n";
                $contentToAsync .= "sale$i.setProductID('" . str_replace("'","\'",$this->getTrackingProductID($order, $item)) . "');\n";
                $contentToAsync .= "sale$i.setCurrency('" . $order->get_currency() . "');\n";
                $contentToAsync .= "sale$i.setCoupon('" . $couponCode . "');\n";

                for ($d = 1; $d <=5; $d++) {
                    $contentToAsync .= "sale$i.setData$d('" . str_replace("'","\'",$this->getTrackingData($order, $d, $item, $couponCode)) . "');\n";
                }
                if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                    $contentToAsync .= "sale$i.setCampaignID('" . get_option(self::WOOCOMM_CAMPAIGN) . "');\n";
                }
                if ($status != '') {
                    $contentToAsync .= "sale$i.setStatus('" . $status . "');\n";
                }

                if ($i != $count) { // delete cookie after sale fix
                    $contentToAsync .= "if (typeof sale$i.doNotDeleteCookies=== 'function') {sale$i.doNotDeleteCookies();}
                    PostAffTracker.register();";
                } else {
                    $contentToAsync .= "if (typeof PostAffTracker.registerOnAllFinished === 'function') {
                            PostAffTracker.registerOnAllFinished();
                        } else {
                            PostAffTracker.register();
                        }";
                }
                $i++;
            }
        } else {
            $contentToAsync .= "var sale = PostAffTracker.createSale();\n";
            $contentToAsync .= "sale.setTotalCost('" . $this->getSubtotal($order) . "');\n";
            $contentToAsync .= "sale.setOrderID('$orderId(1)');\n";
            $contentToAsync .= "sale.setCurrency('" . $order->get_currency() . "');\n";
            $contentToAsync .= "sale.setProductID('" . str_replace("'","\'",$this->getTrackingProductIDsLine($order)) . "');\n";
            for ($d = 1; $d <=5; $d++) {
                $contentToAsync .= "sale.setData$d('" . str_replace("'","\'",$this->getTrackingData($order, $d, null, $couponCode)) . "');\n";
            }
            $contentToAsync .= "sale.setCoupon('" . $couponCode . "');\n";

            if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                $contentToAsync .= "sale.setCampaignID('" . get_option(self::WOOCOMM_CAMPAIGN) . "');\n";
            }
            if ($status != '') {
                $contentToAsync .= "sale.setStatus('" . $status . "');\n";
            }

            $contentToAsync .= 'PostAffTracker.register();';
        }
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) != 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode().'<script type="text/javascript">';
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }
        
        echo $result;

        // affiliate approval?
        if (get_option(self::WOOCOMM_AFFILIATE_APPROVAL) != '') {
            $approvalProducts = explode(';', get_option(self::WOOCOMM_AFFILIATE_APPROVAL));
            $orderedProducts = explode(', ', $this->getTrackingProductIDsLine($order));
            foreach ($orderedProducts as $item) {
                if (in_array($item, $approvalProducts)) {
                    // approve the customer/affiliate
                    $this->changeAffiliateStatus($order->get_billing_email(), 'A');
                    break;
                }
            }
        }

        return true;
    }

    private function trackWooOrderRemote($order, $affiliateId = null, $orderRefunds = null) {
        $orderId = $this->getOrderId($order, false);

        if (get_option(self::WOOCOMM_PERPRODUCT) === 'true') {
            $i = 1;
            $count = count($order->get_items());
            
            $status = '';
            if (get_option(self::WOOCOMM_STATUS_UPDATE) === 'true') {
            	$orderStatus = $order->get_status();
            	switch ($orderStatus) {
            		case 'completed': $status = 'A'; break;
            		case 'cancelled':
            		case 'refunded':
            		case 'failed': $status = 'D'; break;
            		default: $status = '';
            	}
            }
	        if ($status === 'D') {
	        	$this->_log('The order was not successful, no commissions will be created.');
	        	return false;
	        }
	        
	        $parentOrderId = $order->get_parent_id();
	        if (get_class($order) == 'AWCDP_Order' && !empty($parentOrderId)) {
	            $order = wc_get_order($order->get_parent_id());
	            $orderId = $this->getOrderId($order);
	        }

            foreach ($order->get_items() as $item) {
                if (!empty($orderRefunds)) {
                    if (in_array($item['product_id'], $orderRefunds)) {
                        // this item was refunded, ignore it
                        continue;
                    }
                }
                $itemprice = $item['line_total'];
                $couponCode = '';

                try { //if coupon has been used, set the last one in the setCoupon() parameter
                    if (method_exists($order, 'get_coupon_codes')) { // for newer versions
                        $coupons = $order->get_coupon_codes();
                    } else {
                        $coupons = $order->get_used_coupons();
                    }

                    $couponCode = implode(',', $coupons);
                } catch (Exception $e) {
                    //echo "<!--Error: ".$e->getMessage()."-->";
                }

                $query = 'AccountID='.postaffiliatepro::getAccountName()."&TotalCost=$itemprice&OrderID=" . $orderId . "($i)";
                $query .= '&ProductID=' . urlencode($this->getTrackingProductID($order, $item));
                $query .= '&Currency=' . $order->get_currency() . "&Coupon=$couponCode";

                for ($d = 1; $d <=5; $d++) {
                    $query .= "&Data$d=" . urlencode($this->getTrackingData($order, $d, $item, $couponCode));
                }

                if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                    $query .= '&CampaignID=' . get_option(self::WOOCOMM_CAMPAIGN);
                }

                if ($affiliateId != null) {
                    $query .= "&AffiliateID=$affiliateId";
                }

                if ($i != $count) { // delete cookie after sale fix
                    $query .= '&DoNotDeleteCookies=Y';
                }
                self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
                $i++;
            }
        } else {
            if (method_exists($order, 'get_coupon_codes')) { // for newer versions
                $coupons = $order->get_coupon_codes();
            } else {
                $coupons = $order->get_used_coupons();
            }
            $couponCode = implode(',', $coupons);
            $query = 'AccountID='.postaffiliatepro::getAccountName().'&TotalCost=' . $this->getSubtotal($order, $orderRefunds) . '&OrderID=' . $orderId . '(1)';
            $query .= '&ProductID=' . urlencode($this->getTrackingProductIDsLine($order));
            $query .= '&Currency=' . $order->get_currency() . '&Coupon=' . $couponCode;

            for ($d = 1; $d <=5; $d++) {
                $query .= "&Data$d=" . urlencode($this->getTrackingData($order, $d, null, $couponCode));
            }

            if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                $query .= '&CampaignID=' . get_option(self::WOOCOMM_CAMPAIGN);
            }

            if ($affiliateId != null) {
                $query .= "&AffiliateID=$affiliateId";
            }

            self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
        }
    }

    private function getSubtotal($order, $orderRefunds = 0) {
        $subtotal = $order->get_total() - $order->get_total_tax() - $order->get_shipping_total();
        
        if (!empty($orderRefunds) && !is_array($orderRefunds)) {
            $subtotal -= $orderRefunds;
        }
        
        if (get_option(self::WOOCOMM_DEDUCT_FEES) === 'true') {
            foreach ($order->get_fees() as $fee) {
                $subtotal -= $fee->get_total();
            }
        }
        return $subtotal;
    }
    
    private function getTrackingProductID($order, $item) {
        $product = $item->get_product();
        try {
            $productId = $item->get_product_id();
        } catch (Exception $e) {
            $productId = $product->get_id();
        }

        switch (get_option(self::WOOCOMM_PRODUCT_ID)) {
            case 'id':
                return $productId;
            case 'sku':
                if (!empty($product->get_sku())) {
                    return $product->get_sku();
                } else {
                    return $productId;
                }
            case 'var':
                $variationId = $productId;
                try {
                    $variationId = $item->get_variation_id();
                } catch (Exception $e) {
                    if ($product->is_type('variation')) {
                        $variationId = $product->get_variation_id();
                    }
                }
                return ($variationId === 0) ? $productId : $variationId;
            case 'categ':
                $categories = explode(',', wc_get_product_category_list($productId,','));
                return strip_tags($categories[0]);
            case 'tag':
                $tags = explode(',', wc_get_product_tag_list($productId,','));
                return strip_tags($tags[0]);
            case 'role':
                try {
                    $user = new WP_User($order->get_user_id());
                    if (isset($user->roles[0])) {
                        return $user->roles[0];
                    } else {
                        break;
                    }
                } catch (Exception $e) {
                    break;
                }
            case 'title':
                return get_the_title($productId);
        }
        return '';
    }

    private function getTrackingProductIDsLine($order) {
        $productSelection = get_option(self::WOOCOMM_PRODUCT_ID);
        if (empty($productSelection)) {
            return '';
        }

        $line = '';
        foreach ($order->get_items() as $item) {
            $line .= $this->getTrackingProductID($order, $item) . ', ';
        }
        if (!empty($line)) {
            $line = substr($line, 0, -2);
        }
        return $line;
    }

    private function getTrackingData($order, $n, $item = null, $coupon = '') {
        $product = null;
        if ($item != null) {
            $product = $item->get_product();
        }
        $data = get_option(constant('self::WOOCOMM_DATA'.$n));
        switch ($data) {
            case 'id':
                return $order->get_user_id();
            case 'email':
                return $order->get_billing_email();
            case 'name':
                return $order->get_billing_first_name().' '.$order->get_billing_last_name();
            case 'phone':
                return $order->get_billing_phone();
            case 'pmethod':
                return $order->get_payment_method_title();
            case 'discount':
                return $order->get_total_discount();
            case 'coupon':
                return $coupon;
            case 'title':
                if ($product != null) {
                    return get_the_title($product->get_id());
                } else {
                    return '';
                }
            case 'order':
                return '#'.$order->get_order_number();
            case 'order_note':
                return $this->getOrderNote($order->get_id());
            default: return '';
        }
    }

    private function getOrderNote($orderId) {
        $post = get_post($orderId);
        if (!$post) {
            return '';
        }
        return substr($post->post_excerpt, 0, 255);
    }

    public function wooOrderStatusChanged($orderId, $old_status, $new_status) {
        if (get_option(self::WOOCOMM_STATUS_UPDATE) !== 'true') {
            return false;
        }

        $this->_log('Received status: ' . $new_status);

        switch ($new_status) {
            case 'completed':
                $status = 'A';
                break;
            case 'processing':
            case 'on-hold':
                $status = 'P';
                break;
            case 'cancelled':
            case 'failed':
                $status = 'D';
                break;
            case 'refunded':
                return $this->refundTransaction($orderId);
            default:
                $status = '';
        }

        if ($status == '') {
            $this->_log('Unsupported status ' . $new_status);
            return false;
        }

        $orderId = $this->getOrderId(wc_get_order($orderId));

        return $this->changeOrderStatus($orderId, $status);
    }

    private function refundTransaction($orderId) {
        $limit = 100;
        $isSubscription = false;
        if (function_exists('wcs_get_subscriptions_for_order')) { // we will have to refund one of the recurring commissions
            $subscriptions = wcs_get_subscriptions_for_order($orderId);
            if (!empty($subscriptions)) {
                foreach ($subscriptions as $key => $value) { // take the first and leave
                    $orderId = $key;
                    $limit = 1;
                    $isSubscription = true;
                    break;
                }
            }
        }
        if (!$isSubscription && get_option(self::WOOCOMM_ORDERID_SETTING) === self::WOOCOMM_ORDER_NUMBER) {
            $order = wc_get_order($orderId);
            $orderId = $order->get_order_number();
        }

        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
            return false;
        }
        $ids = $this->getTransactionIDsByOrderID($orderId, $session, 'A,P', $limit);
        if (empty($ids)) {
            $this->_log(__('Nothing to change, the commission does not exist in PAP'));
            return true;
        }

        $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'makeRefundChargeback', $session);
        $request->addParam('ids', new Gpf_Rpc_Array($ids));
        $request->addParam('status', 'R');
        $request->addParam('merchant_note', 'Refunded automatically from WooCommerce');
        $request->addParam('refund_multitier', 'Y');
        try {
            $request->sendNow();
        } catch (Exception $e) {
            $this->_log(__('A problem occurred while transaction status change with API: ') . $e->getMessage());
            return false;
        }

        return true;
    }

    public function wooSubscriptionStatusChanged($orderId, $old_status, $new_status) {
        if ($new_status !== 'cancelled') {
            return false;
        }
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
            return;
        }
        // load recurring order ID
        $request = new Gpf_Rpc_GridRequest('Pap_Features_RecurringCommissions_RecurringCommissionsGrid', 'getRows', $session);
        $request->addFilter('orderid', 'L', $orderId . '(%');
        $recurringIds = array();
        try {
            $request->sendNow();
            $grid = $request->getGrid();
            $recordset = $grid->getRecordset();
            foreach ($recordset as $rec) {
                $recurringIds[] = $rec->get('orderid');
            }
        } catch (Exception $e) {
            $this->_log(__('A problem occurred while loading recurring commissions: ') . $e->getMessage());
            return false;
        }

        if (empty($recurringIds)) {
            $this->_log(__('Nothing to change, the commission does not exist in PAP'));
            return false;
        }

        $request = new Gpf_Rpc_FormRequest('Pap_Features_RecurringCommissions_RecurringCommissionsForm', 'changeStatus', $session);
        $request->addParam('ids', new Gpf_Rpc_Array($recurringIds));
        $request->addParam('status', 'D');
        try {
            $request->sendNow();
        } catch (Exception $e) {
            $this->_log(__('A problem occurred while transaction status change with API: ') . $e->getMessage());
            return false;
        }

        return true;
    }

    public function wooRecurringCommission($renewal_order, $subscription) {
        if (!is_object($subscription)) {
            $subscription = wcs_get_subscription($subscription);
        }

        if (!is_object($renewal_order)) {
            $renewal_order = wc_get_order($renewal_order);
        }

        // try to recurr a commission with order ID $subscription->id
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            $this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
            return $renewal_order;
        }

        $recurringSubtotal = false;
        if (get_option(self::WOOCOMM_TRACK_RECURRING_TOTAL) === 'true') {
            $recurringSubtotal = $renewal_order->get_total() - $renewal_order->get_total_tax() - $renewal_order->get_shipping_total();
        }
        if (!$this->fireRecurringCommissions($session, $subscription->id . '(1)', $recurringSubtotal)) {
            // creating recurring commissions failed, create a new commission instead
            $this->_log(__('Creating new commissions with order ID ') . $renewal_order->id . '(1)');
            $this->trackWooOrderRemote($renewal_order);
        }

        return $renewal_order;
    }

    public function wooAutoshipCommission($order_id, $schedule_id) {
        // can work only with lifetime relations, no way to use recurring commissions
        $renewal_order = wc_get_order($order_id);
        $this->_log(__('Creating new commissions with order ID ') . $renewal_order->id . '(1)');
        $this->trackWooOrderRemote($renewal_order);
        return $renewal_order;
    }

    public function wooModifyPaypalArgs($array) {
        if (strpos($array['notify_url'], '?')) {
            $array['notify_url'] .= '&';
        } else {
            $array['notify_url'] .= '?';
        }
        $array['notify_url'] .= 'pap_custom=' . $_REQUEST['pap_custom'];
        if (isset($_REQUEST['pap_IP'])) {
            $array['notify_url'] .= '&pap_IP=' . $_REQUEST['pap_IP'];
        }
        if (strpos($array['return'], '?')) {
            $array['return'] .= '&';
        } else {
            $array['return'] .= '?';
        }
        $array['return'] .= 'customGateway=paypal';
        return $array;
    }

    public function wooProcessPaypalIPN($post_data) {
        $this->_log('PayPal IPN received: '.print_r($post_data,true));
        $post_data['payment_status'] = strtolower($post_data['payment_status']);
        if (empty($post_data['custom'])) {
            $this->_log('PayPal IPN received but didn\'t find anything in custom, expected WooCommerce order details, stopping.');
            return false;
        }
        if (!$order = $this->get_paypal_order($post_data['custom'])) {
            $this->_log('PayPal IPN received but couldn\'t load the WooCommerce order for the IPN, stopping. Content of custom was '.$post_data['custom']);
            return false;
        }

        if ($post_data['payment_status'] === 'completed') {
            $orderId = $this->getOrderId($order, false);
            if (get_option(self::WOOCOMM_PERPRODUCT) === 'true') {
                $i = 1;
                $count = count($order->get_items());
                foreach ($order->get_items() as $item) {
                    $itemprice = $item['line_total'];
                    $couponCode = '';

                    try { //if coupon has been used, set the last one in the setCoupon() parameter
                        if (method_exists($order, 'get_coupon_codes')) { // for newer versions
                            $coupons = $order->get_coupon_codes();
                        } else {
                            $coupons = $order->get_used_coupons();
                        }
                        $couponCode = $this->resolveCoupons($coupons);
                    } catch (Exception $e) {
                        //echo "<!--Error: ".$e->getMessage()."-->";
                    }

                    $query = 'AccountId=' . substr($_GET['pap_custom'], 0, 8) . '&visitorId=' . substr($_GET['pap_custom'], -32);
                    if (isset($_GET['pap_IP'])) {
                        $query .= '&ip=' . $_GET['pap_IP'];
                    }
                    $query .= "&TotalCost=$itemprice&OrderID=" . $orderId . "($i)";
                    $query .= '&ProductID=' . urlencode($this->getTrackingProductID($order, $item));
                    $query .= '&Currency=' . $order->get_currency() . "&Coupon=$couponCode";

                    for ($d = 1; $d <= 5; $d++) {
                        $query .= "&Data$d=" . urlencode($this->getTrackingData($order, $d, $item, $couponCode));
                    }

                    if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                        $query .= '&CampaignID=' . get_option(self::WOOCOMM_CAMPAIGN);
                    }

                    if ($i != $count) { // delete cookie after sale fix
                        $query .= '&DoNotDeleteCookies=Y';
                    }
                    $this->_log('PayPal sending tracking request to PAP for '.$query);
                    self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
                    $i++;
                }
            } else {
                if (method_exists($order, 'get_coupon_codes')) { // for newer versions
                    $coupons = $order->get_coupon_codes();
                } else {
                    $coupons = $order->get_used_coupons();
                }
                $couponCode = implode(',', $coupons);
                $query = 'AccountId=' . substr($_GET['pap_custom'], 0, 8) . '&visitorId=' . substr($_GET['pap_custom'], -32);
                if (isset($_GET['pap_IP'])) {
                    $query .= '&ip=' . $_GET['pap_IP'];
                }
                $query .= '&TotalCost=' . $this->getSubtotal($order) . '&OrderID=' . $orderId . '(1)';
                $query .= '&ProductID=' . urlencode($this->getTrackingProductIDsLine($order));
                $query .= '&Currency=' . $order->get_currency() . '&Coupon=' . $couponCode;

                for ($d = 1; $d <= 5; $d++) {
                    $query .= "&Data$d=" . urlencode($this->getTrackingData($order, $d, null, $couponCode));
                }

                if (get_option(self::WOOCOMM_CAMPAIGN) !== '' && get_option(self::WOOCOMM_CAMPAIGN) !== null && get_option(self::WOOCOMM_CAMPAIGN) !== 0 && get_option(self::WOOCOMM_CAMPAIGN) !== '0') {
                    $query .= '&CampaignID=' . get_option(self::WOOCOMM_CAMPAIGN);
                }
                $this->_log('PayPal sending tracking request to PAP for '.$query);
                self::sendRequest(postaffiliatepro::parseSaleScriptPath(), $query);
            }

            // affiliate approval?
            if (get_option(self::WOOCOMM_AFFILIATE_APPROVAL) != '') {
                $approvalProducts = explode(';', get_option(self::WOOCOMM_AFFILIATE_APPROVAL));
                $orderedProducts = explode(', ', $this->getTrackingProductIDsLine($order));
                foreach ($orderedProducts as $item) {
                    if (in_array($item, $approvalProducts)) {
                        // approve the customer/affiliate
                        $this->changeAffiliateStatus($order->get_billing_email(), 'A');
                        break;
                    }
                }
            }

            return true;
        }
        return false;
    }

    private function get_paypal_order($raw_custom) {
        if (($custom = json_decode($raw_custom)) && is_object($custom)) {
            $order_id = $custom->order_id;
            $order_key = $custom->order_key;
        } elseif (preg_match('/^a:2:{/', $raw_custom) && !preg_match('/[CO]:\+?[0-9]+:"/', $raw_custom) && ($custom = maybe_unserialize($raw_custom))) {
            $order_id = $custom[0];
            $order_key = $custom[1];
        } else {
            $this->_log('PayPal IPN handling: Order ID and key were not found in "custom".');
            return false;
        }

        if (!$order = wc_get_order($order_id)) {
            // We have an invalid $order_id, probably because invoice_prefix has changed.
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = wc_get_order($order_id);
        }

        if (!$order || $order->get_order_key() !== $order_key) {
            $this->_log('PayPal IPN handling: Order keys do not match.');
            return false;
        }

        return $order;
    }

    private function resolveCoupons(array $coupons) {
        $version = get_option(postaffiliatepro::PAP_VERSION);
        $versionArray = explode('.', $version);
        $minVersion = array(0 => '5', 1 => '9', 2 => '8', 3 => '8');
        $problem = false;

        if ($version != '') {
            if ($versionArray[0] < $minVersion[0]) {
                $problem = true;
            } elseif ($versionArray[0] == $minVersion[0]) {
                if ($versionArray[1] < $minVersion[1]) {
                    $problem = true;
                } elseif ($versionArray[1] == $minVersion[1]) {
                    if ($versionArray[2] < $minVersion[2]) {
                        $problem = true;
                    } elseif ($versionArray[2] == $minVersion[2]) {
                        if ($versionArray[3] < $minVersion[3]) {
                            $problem = true;
                        }
                    }
                }
            }
        } else {
            $problem = true;
        }

        if ($problem) {
            $couponToBeUsed = (count($coupons) > 1 ? count($coupons) - 1 : 0);
            return $coupons[$couponToBeUsed];
        }
        return implode(',', $coupons);
    }

    private function getOrderId($order, $checkSubscription = true) {
        $orderId = $order->get_id();
        if ($checkSubscription && function_exists('wcs_get_subscriptions_for_order')) {
            $subscriptions = wcs_get_subscriptions_for_order($orderId);
            if (!empty($subscriptions)) {
                foreach ($subscriptions as $key => $value) { // take the first and leave
                    return $key;
                }
            }
        }
        if (get_option(self::WOOCOMM_ORDERID_SETTING) === self::WOOCOMM_ORDER_NUMBER) {
            $orderId = $order->get_order_number();
        }
        return $orderId;
    }

    public function addHiddenFieldToPaymentForm($return = false) {
        postaffiliatepro::addHiddenFieldToPaymentForm($return);
        postaffiliatepro::addHiddenFieldToRegistrationForm(); // to support parent affiliate for signup
    }

    public function wooAddRefIdToOrderMetaData($orderId, $postedData, $order) {
        if (isset($_POST['pap_parent']) && ($_POST['pap_parent'] != '')) {
            add_post_meta($orderId, 'Affiliate', $_POST['pap_parent']);
        }
        if (isset($_POST['pap_custom']) && ($_POST['pap_custom'] != '')) {
            add_post_meta($orderId, 'Visitor ID', $_POST['pap_custom']);
        }
    }
    
    public function wooAddMetaToRevCent($saleArray) {
        $_REQUEST['pap_custom'];
        if ($saleArray['type'] == 'sale'
                && $saleArray['method'] == 'create'
                && $_REQUEST['pap_custom'] != 'null'
                && $_REQUEST['pap_custom'] != 'default1null') {
            $visitorObj = new stdClass();
            $visitorObj->name = 'visitorid';
            $visitorObj->value = strval($_REQUEST['pap_custom']);
            array_push($saleArray['metadata'], $visitorObj);
        }
        return $saleArray;
    }

    public function addRecomputionMetaBox($post_type, $post) {
        if ($post_type !== 'shop_order') {
            return;
        }

        $papwc = new postaffiliatepro_Form_Settings_WooComm();
        add_meta_box('wc-pap-recompute-commissions',
            'Post Affiliate Pro',
            array(
                $papwc,
                'papRecomputeCommissions'
            ),
            'shop_order',
            'side',
            'high'
        );

        wc_enqueue_js(
            "$('#pap-recompute').on('click', function() {
                $('#papRecomputionNote.p').hide();
                $('#pap-recompute').val('...working');
                papRecomputeCommissions($('#pap-recompute-orderid').val());
        	});

            function papRecomputeCommissions(orderid) {
                var d = {
                	action: 'pap_recompute_ajax',
                	order_id: orderid
                };

                $.post(ajaxurl, d, function(response) {
                	if (response.success === true) {
                        $('#pap-recompute').css('background-color','#DDFFAA');
                        $('#pap-recompute').css('color','#44AA55');
                        $('#pap-recompute').css('border-color','#AAFF44');
                        $('#pap-recompute').val('Done');
                	} else {
                		$('#pap-recompute').val('Error');
                        $('#papRecomputionNote').html(response.data.error);
                        $('#papRecomputionNote').show();
                	}
                });
            }"
        );
    }

    public function papRecomputeCommissions(WP_Post $post) {
    	echo '
    	    <div id="papRecomputionContent">
    	        <div id="papRecomputionText">
    	           If number of products or their cost or order cost changed, you can recompute commission for the order. Clicking the button will load, decline/refund and re-create the commission.
    	        </div>
    	        <div class="note_content" id="papRecomputionNote">
			    </div>
    	            <input type="hidden" id="pap-recompute-orderid" name="pap-recompute-orderid" value="'.$post->ID.'" />
                    <input id="pap-recompute" type="button" class="button" value="Recompute Commission" />
            </div>';
    }

    public function recomputeCommissionAjaxCallback() {
        try {
            if (!current_user_can('edit_shop_orders')) {
                throw new Exception(__('You do not have enough permissions to manipulate orders!'));
            }

            if (!isset($_POST['order_id']) || ($_POST['order_id'] == '')) {
                throw new Exception(__('Order ID is missing in the request. Something is wrong! Contact the plugin developer.'));
            }
            $orderId = $_POST['order_id'];

            // try to load commission
            $session = $this->getApiSession();
            if ($session === null || $session === '0') {
                throw new Exception(__('We have no session to PAP installation! Recompution is not possible right now.'));
            }

            $order = wc_get_order($orderId);
            $orderIdForLoadingFromPap = $this->getOrderId($order, false);
            $ids = $this->getTransactionIDsByOrderID($orderIdForLoadingFromPap, $session, 'A,P');
            if (empty($ids)) {
                throw new Exception(__('There is no approved or pending commission for this order. Nothing to recompute.'));
            }

            $commissionRecordset = $this->loadTransactionsByOrderID($orderIdForLoadingFromPap, $session, 'A,P');

            $affiliateId = null;
            foreach ($commissionRecordset as $row) {
                $affiliateId = $row->get('userid');
                break;
            }
            if ($affiliateId == null) {
                throw new Exception(__('Could not load affiliate info from commission.'));
            }

            // refund and track again
            if ($this->refundTransaction($orderId)) {
                $orderRefunds = null;
                if (get_option(self::WOOCOMM_PERPRODUCT) !== 'true') {
                    $orderRefunds = $order->get_total_refunded();
                } else { // per product
                    $orderRefunds = array();
                    foreach ($order->get_refunds() as $refund) {
                        // Loop through the order refund line items
                        foreach ($refund->get_items() as $item_id => $item) {
                            $orderRefunds[] = $item_id;
                        }
                    }
                }

                $this->trackWooOrderRemote($order, $affiliateId, $orderRefunds);
            } else {
                throw new Exception('Refunding was not successful!');
            }

            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error(array('error' => $e->getMessage()));
        }
        wp_die();
    }
}

$submenuPriority = 52;
$integration = new postaffiliatepro_Form_Settings_WooComm();
add_action('admin_init', array(
        $integration,
        'initSettings'
), 99);
add_action('admin_menu', array(
        $integration,
        'addPrimaryConfigMenu'
), $submenuPriority);
add_filter('wp_footer', array(
        $integration,
        'wooHandleCustomThankYouPages'
), 99);
add_action('woocommerce_thankyou', array(
        $integration,
        'wooAddThankYouPageTrackSale'
));
add_action('woocommerce_checkout_before_order_review', array(
        $integration,
        'addHiddenFieldToPaymentForm'
));
add_action('woocommerce_order_status_changed', array(
        $integration,
        'wooOrderStatusChanged'
), 99, 3);
add_action('woocommerce_subscription_status_changed', array(
        $integration,
        'wooSubscriptionStatusChanged'
), 99, 3);
add_filter('wcs_renewal_order_created', array(
        $integration,
        'wooRecurringCommission'
), 99, 2);
add_filter('wc_autoship_payment_complete', array(
        $integration,
        'wooAutoshipCommission'
), 99, 2);
add_action('add_meta_boxes', array(
        $integration,
        'addRecomputionMetaBox'
), 99, 2);
add_action('woocommerce_checkout_order_processed', array(
        $integration,
        'wooAddRefIdToOrderMetaData'
), 99, 3);
// WooCommerce PayPal
add_filter('woocommerce_paypal_args', array(
        $integration,
        'wooModifyPaypalArgs'
), 99);
add_action('valid-paypal-standard-ipn-request', array(
        $integration,
        'wooProcessPaypalIPN'
));
// WooCommerce RevCent
add_filter('revcent_payload_request_args', array(
        $integration,
        'wooAddMetaToRevCent'
), 99);
// AJAX
add_action('wp_ajax_pap_recompute_ajax', array(
        $integration,
        'recomputeCommissionAjaxCallback'
), 99);
