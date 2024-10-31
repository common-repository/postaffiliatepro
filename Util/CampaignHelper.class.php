<?php
class postaffiliatepro_Util_CampaignHelper extends postaffiliatepro_Base {

    const CAMPAIGN_NAME = 'name';
    const CAMPAIGN_STATUS = 'rstatus';
    const CAMPAIGN_TYPE = 'rtype';
    const CAMPAIGN_ID = 'id';

    const CAMPAIGN_TYPE_PRIVATE = 'I';
    const CAMPAIGN_TYPE_PUBLIC = 'P';
    const CAMPAIGN_TYPE_PUBLIC_MANUAL = 'M';

    /**
     *
     * @var Gpf_Data_RecordSet
     */
    private static $campaignList = null;

    public function getTypeAsText($type) {
        switch ($type) {
            case self::CAMPAIGN_TYPE_PUBLIC:
                return __('Public');
            case self::CAMPAIGN_TYPE_PUBLIC_MANUAL:
                return __('Public with manual approval');
            case self::CAMPAIGN_TYPE_PRIVATE:
                return __('Private');
        }
    }

    public function getCampaignsCount($type = null) {
        $campaignList = $this->getCampaignsList();
        if (empty($campaignList)) {
            return 0;
        }
        if ($type === null) {
            return $campaignList->getSize();
        }
        $count = 0;
        foreach ($campaignList as $campaign) {
            if ($campaign->get(self::CAMPAIGN_TYPE) === $type) {
                $count ++;
            }
        }
        return $count;
    }

    public function getCampaignType($campaignId) {
        $campaignList = $this->getCampaignsList();
        foreach ($campaignList as $campaign) {
            if ($campaign->get(self::CAMPAIGN_ID) === $campaignId) {
                return $campaign->get(self::CAMPAIGN_TYPE);
            }
        }
    }

    public function getCampaignName($campaignId) {
        $campaignList = $this->getCampaignsList();
        foreach ($campaignList as $campaign) {
            if ($campaign->get(self::CAMPAIGN_ID) === $campaignId) {
                return htmlspecialchars($campaign->get(self::CAMPAIGN_NAME));
            }
        }
    }

    /**
     * @return Gpf_Data_RecordSet;
     */
    public function getCampaignsList() {
        if (self::$campaignList !== null) {
            return self::$campaignList;
        }
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            return array();
        }
        $request = new Gpf_Rpc_GridRequest('Pap_Merchants_Campaign_CampaignsGrid', 'getRows', $session);
        $request->setLimit(0, 9999);
        $request->addParam('columns', new Gpf_Rpc_Array(array(array('id'), array('id'))));

        $filters = new Gpf_Rpc_Array();
        $filters->add(new Gpf_Data_Filter('rstatus', 'NE', 'D'));
        $filters->add(new Gpf_Data_Filter('rstatus', 'NE', 'S'));
        $filters->add(new Gpf_Data_Filter('rstatus', 'NE', 'W'));

        $request->addParam('filters', $filters);
        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->_log(__('Can not obtain campaign list:') . $e->getMessage());
            return array();
        }
        $grid = $request->getGrid();
        self::$campaignList = $grid->getRecordset();
        return self::$campaignList;
    }

}