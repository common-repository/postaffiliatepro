<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class Shortcode_Cache {

    private function getCahce() {
        return unserialize(get_option(Shortcode_Affiliate::AFFILAITE_SHORTCODE_CACHE));
    }

    public function update($username, $sessionid) {
        $cache = $this->getCahce();
        $cache[$username] = array('sessionid' => $sessionid, 'created' => time());
        update_option(Shortcode_Affiliate::AFFILAITE_SHORTCODE_CACHE, serialize($cache));
    }

    public function getSessionId($username) {
        $cache = $this->getCahce();
        if ($cache == null) {
            return null;
        }
        if (array_key_exists($username, $cache) && (time() - $cache[$username]['created'] < 120)) {
            return $cache[$username]['sessionid'];
        }
        return null;
    }
}