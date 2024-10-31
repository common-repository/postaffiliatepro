<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_Marketpress extends postaffiliatepro_Form_Base {
    const MARKETPRESS_COMMISSION_ENABLED = 'marketpress-commission-enabled';
    const MARKETPRESS_CONFIG_PAGE = 'marketpress-config-page';
    const MARKETPRESS_PERPRODUCT = 'marketpress-per-product';
    const MARKETPRESS_TRACK_DATA1 = 'marketpress-track-data1';
    const MARKETPRESS_STATUS_UPDATE = 'marketpress-status-update';

    public function __construct() {
        parent::__construct(self::MARKETPRESS_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/MarketpressConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(self::MARKETPRESS_PERPRODUCT);
        $this->addCheckbox(self::MARKETPRESS_STATUS_UPDATE);
        $this->addCheckbox(self::MARKETPRESS_TRACK_DATA1);

        $this->addSubmit();
    }

    public function initSettings() {
        register_setting(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, self::MARKETPRESS_COMMISSION_ENABLED);
          register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_PERPRODUCT);
          register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_STATUS_UPDATE);
          register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_TRACK_DATA1);
    }

    public function addPrimaryConfigMenu() {
        if (get_option(self::MARKETPRESS_COMMISSION_ENABLED) === 'true') {
            add_submenu_page(
                'integrations-config-page-handle',
                __('Marketpress','pap-integrations'),
                __('Marketpress','pap-integrations'),
                'manage_options',
                'marketpressintegration-settings-page',
                array($this, 'printConfigPage')
                );
        }
    }

    public function printConfigPage() {
        $this->render();
        return;
    }

    public function MarketpressThankYouPage($text, MP_Order $order) {
        if (get_option(self::MARKETPRESS_COMMISSION_ENABLED) !== 'true') {
            return false;
        }

        $text .= "<!-- Post Affiliate Pro sale tracking -->\n";
        $contentToAsync = 'PostAffTracker.setAccountId(\''.self::getAccountName()."');\n";


        if (get_option(self::MARKETPRESS_PERPRODUCT) !== 'true') {
            $contentToAsync .= "var sale = PostAffTracker.createSale();
                sale.setTotalCost('".$order->get_meta('mp_order_total', '')."');
                sale.setOrderID('".$order->get_id()."(1)');
                sale.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
            if (get_option(self::MARKETPRESS_TRACK_DATA1) !== 'true') {
                $contentToAsync .= "sale.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
            }
            $contentToAsync .= 'PostAffTracker.register();';
        } else {
            $cart = $order->get_meta('mp_cart_items');
            if (!$cart) {
                $cart = $order->get_cart();
            }
            if (is_array($cart)) {
                $i = 1;
                $count = count($cart);
                $loop = 1;
                foreach ($cart as $product_id => $items) {
                    $innercount = count($items);
                    $j = 1;
                    foreach ($items as $item) {
                        if($item['quantity'] >= MP_BULK_AMOUNT_LEX) {
                            $item['price'] = $item['price'] * MP_BULK_PERCENT_LEX;
                        }

                        $contentToAsync .= "var sale$i = PostAffTracker.createSale();
                            sale$i.setTotalCost('".($item['price']*$item['quantity'])."');
                            sale$i.setOrderID('".$order->get_id()."($i)');
                            sale$i.setProductID('".(($item['SKU'] == '')?$item['name']:$item['SKU'])."');
                            sale$i.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
                        if (get_option(self::MARKETPRESS_TRACK_DATA1) !== 'true') {
                            $contentToAsync .= "sale$i.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
                        }

                        if (($loop != $count) || ($j != $innercount)) {
                            $contentToAsync .= "if (typeof sale$i.doNotDeleteCookies === 'function') {sale$i.doNotDeleteCookies();}
                            PostAffTracker.register();";
                        } else {
                            $contentToAsync .= "if (typeof PostAffTracker.registerOnAllFinished === 'function') {
                                PostAffTracker.registerOnAllFinished();
                            } else {
                                PostAffTracker.register();
                            }";
                        }

                        $i++;$j++;
                    }
                    $loop++;
                }
            } else {
                $contentToAsync .= "var sale = PostAffTracker.createSale();
                    sale.setTotalCost('".$order->get_meta('mp_order_total', '')."');
                    sale.setOrderID('".$order->get_id()."(1)');
                    sale.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
                if (get_option(self::MARKETPRESS_TRACK_DATA1) !== 'true') {
                    $contentToAsync .= "sale.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
                }
                $contentToAsync .= 'PostAffTracker.register();';
            }
        }
        
        if (get_option(postaffiliatepro::ASYNC_ENABLED) !== 'true') {
            $result = postaffiliatepro::getPAPTrackJSDynamicCode()."<script type='text/javascript'>\n";
            $result .= $contentToAsync . "</script>\n";
        } else {
            $result = postaffiliatepro::getPAPTrackJSAsyncCode($contentToAsync);
        }

        return $text.$result."<!-- /Post Affiliate Pro sale tracking -->\n";
    }

    public function MarketpressChangeOrderStatusPaid(MP_Order $order) {
        return $this->changeOrderStatus($order->get_id(), 'A');
    }

    public function MarketpressChangeOrderStatusDeclined(MP_Order $order) {
        return $this->changeOrderStatus($order->get_id(), 'D');
    }
}

$submenuPriority = 21;
$integration = new postaffiliatepro_Form_Settings_Marketpress();
add_action('admin_init', array($integration, 'initSettings'), 99);
add_action('admin_menu', array($integration, 'addPrimaryConfigMenu'), $submenuPriority);

add_filter('mp_order/confirmation_text', array($integration, 'MarketpressThankYouPage'), 99, 2);
add_action('mp_order_order_paid', array($integration, 'MarketpressChangeOrderStatusPaid'));
add_action('mp_order_order_closed', array($integration, 'MarketpressChangeOrderStatusDecline'));
add_action('mp_order_trash', array($integration, 'MarketpressChangeOrderStatusDecline'));