<?php
class Cammino_Googleanalytics_Block_Ga extends Mage_GoogleAnalytics_Block_Ga
{
    /**
     * Render regular page tracking javascript code
     * The custom "page name" may be set from layout or somewhere else. It must start from slash.
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._trackPageview
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApi_gaq.html
     * @param string $accountId
     * @return string
     */
    protected function _getPageTrackingCode($accountId)
    {
        $pageName   = trim($this->getPageName());
        $optPageURL = '';
        if ($pageName && preg_match('/^\/.*/i', $pageName)) {
            $optPageURL = ", '{$this->jsQuoteEscape($pageName)}'";
        }
    
        
        return "
ga('create','{$this->jsQuoteEscape($accountId)}', 'auto');
ga('require', 'displayfeatures');
ga('send', 'pageview'{$optPageURL});
";
    }

    /**
     * Render information about specified orders and their items
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html#_gat.GA_Tracker_._addTrans
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));
        
        $result = array("ga('require', 'ecommerce', 'ecommerce.js');");

        foreach ($collection as $order) {
            if ($order->getIsVirtual()) {
                $address = $order->getBillingAddress();
            } else {
                $address = $order->getShippingAddress();
            }
            $result[] = sprintf("ga('ecommerce:addTransaction', {id: '%s', affiliation: '%s', revenue: '%s', tax: '%s', shipping: '%s' });",
                $order->getIncrementId(),
                $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                number_format($order->getBaseGrandTotal(), 2, '.', ''),
                number_format($order->getBaseTaxAmount(), 2, '.', ''),
                number_format($order->getBaseShippingAmount(), 2, '.', '')
            );
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf("ga('ecommerce:addItem', {id: '%s', sku: '%s', name: '%s', category: '%s', price: '%s', quantity: '%s'});",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getSku()),
                    $this->jsQuoteEscape($item->getName()),
                    null, // there is no "category" defined for the order item
                    number_format($item->getBasePrice(), 2, '.', ''),
                    number_format($item->getQtyOrdered(), 2, '.', '')
                );
            }
            $result[] = "ga('ecommerce:send');";
        }
        return implode("\n", $result);
    }
}
