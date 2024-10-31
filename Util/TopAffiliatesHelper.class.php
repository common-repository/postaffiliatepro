<?php
class postaffiliatepro_Util_TopAffiliatesHelper extends postaffiliatepro_Base {
    const COL_SALES_COUNT = 'salesCount';
    const COL_COMMISSIONS = 'commissions';
    const COL_RAW_CLICKS = 'clicksRaw';

    /**
     * @var Gpf_Data_RecordSet
     */
    private static $affiliateList = null;

    /**
     * @return Gpf_Data_RecordSet;
     */
    public function getTopAffiliatesList($orderBy, $orderAsc, $limit) {
        if (self::$affiliateList !== null) {
            return self::$affiliateList;
        }
        $session = $this->getApiSession();
        if ($session === null || $session === '0') {
            return null;
        }
        $request = new Gpf_Rpc_GridRequest("Pap_Merchants_User_TopAffiliatesGrid", "getRows", $session);
        $request->setLimit(0, $limit);
        $columns = array(array('id'), array('userid'), array('firstname'), array('lastname'), array('parentuserid'), array(self::COL_COMMISSIONS), array(self::COL_SALES_COUNT), array(self::COL_RAW_CLICKS));
        for($i = 1; $i <= 25; $i++) {
            $columns[] = array('data' . $i);
        }
        $request->addParam('columns', new Gpf_Rpc_Array($columns));
        $request->addParam('sort_col', $orderBy);
        $request->addParam('sort_asc', 'false');

        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->_log(__("Can not obtain list of top affiliates:" . $e->getMessage()));
            return null;
        }
        $grid = $request->getGrid();
        self::$affiliateList = $grid->getRecordset();
        return self::$affiliateList;
    }

    public function getOrderOptions($orderBy) {
        $out = '';
        $select = ($orderBy==='name')?"selected='selected'":"";
        $out.="<option $select value='name'>Name</option>";
        $select = ($orderBy===self::COL_SALES_COUNT)?"selected='selected'":"";
        $out.="<option $select value='". self::COL_SALES_COUNT."'>Sales count</option>";
        $select = ($orderBy===self::COL_RAW_CLICKS)?"selected='selected'":"";
        $out.="<option $select value='".self::COL_RAW_CLICKS."'>Clicks list</option>";
        $select = ($orderBy===self::COL_COMMISSIONS)?"selected='selected'":"";
        $out.="<option $select value='".self::COL_COMMISSIONS."'>Commissions</option>";
        return $out;
    }
}
