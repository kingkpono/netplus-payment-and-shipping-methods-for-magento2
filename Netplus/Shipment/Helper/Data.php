<?php
/**
 * Copyright © 2015 Netplus . All rights reserved.
 */
namespace Netplus\Shipment\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
     protected $scopeConfig;
     protected $_context;
     protected $_orders;
      protected $_region;
      protected $_storeManager;
      protected $_convertOrder;
      protected $_shipmentNotifier;
    protected $_trackFactory;
     

  /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
  public function __construct(\Magento\Framework\App\Helper\Context $context,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Sales\Model\Order $orders,
    \Magento\Directory\Model\Region $region,
      \Magento\Store\Model\StoreManagerInterface $storeManager,
      \Magento\Sales\Model\Convert\Order $convertorder,
     \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
   \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory

  ) {
  
      $this->scopeConfig = $scopeConfig;
      $this->_context=$context;
        $this->_orders=$orders;
         $this->_region=$region;
     
        $this->_storeManager = $storeManager;
        $this->_convertOrder=$convertorder;
        $this->_shipmentNotifier=$shipmentNotifier;
    $this->_trackFactory=$trackFactory;
          parent::__construct($context);

  }


   public function getNetplusConfig($param) {
           $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

           return $this->scopeConfig->getValue("shipment/parameters/".$param, $storeScope);


        }
        public function getWebsiteUrl()
        {
          return $this->_storeManager->getStore()->getBaseUrl();
        }

        public function getOrder($id)
        {
          return $this->_orders->load($id);
        }




    public function getShipmentPostUrl()
    {
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
       $url= $objectManager->create('Magento\Backend\Helper\Data')->getUrl("shipment/postshipmentadmin/index");

        return $url;
    }

public function getBaseUrl()
{
if($this->getNetplusConfig('test_mode'))
  return "http://test.saddleng.com/v2/delivery";
  else
     return "http://saddleng.com/v2/delivery";

}
public function getBaseTrackUrl()
{
if($this->getNetplusConfig('test_mode'))
  return "http://test.saddleng.com/v2/delivery";
  else
     return "http://saddleng.com/v2/delivery";

}
 public function make_shipment($order,$tracking_code,$carrier_code)
  {

    if($order->canShip())
{
  
// Initialize the order shipment object

$shipment = $this->_convertOrder->toShipment($order);

// Loop through order items
foreach ($order->getAllItems() AS $orderItem) {
    // Check if order item has qty to ship or is virtual
    if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
        continue;
    }

 $qtyShipped = $orderItem->getQtyToShip();

    // Create shipment item with qty
    $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

    // Add shipment item to shipment
    $shipment->addItem($shipmentItem);
}

// Register shipment
$shipment->register();

$shipment->getOrder()->setIsInProcess(true);

try {
    // Save created shipment and order
    $shipment->save();
    $shipment->getOrder()->save();

  
 
   $order->addStatusHistoryComment(
        __('Netplus Tracking Code : <strong>'.$tracking_code.'<strong>')
    )->save();  


    // Send email
    $this->_shipmentNotifier->notify($shipment);

    $shipment->save();
} catch (\Exception $e) {
    throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
}


         return true;
        
}else{
  return false;

}
  }
 
 



   public function has_weight($orderId)
  {
    $order= $this->_orders->load($orderId);
     foreach ($order->getAllItems() as $item) 
         {
           
            if($item->getWeight()=="" || $item->getWeight()==null )
              return false;
          
         }//end for each item

         return true;
  }
  public function build_rates_payload($order)
  {
  $origin_state=$this->_region->load($this->getNetplusConfig('region_id'))->getName();
    
   $origin_city=$this->getNetplusConfig('origin_city');
        $client_id=$this->getNetplusConfig('client_id');
 
  $post='{ "delivery_state": "'.$order->getShippingAddress()->getRegion().'", 
"delivery_lga": "'.$order->getShippingAddress()->getCity().'", 
"pickup_state": "'.$origin_state.'",
"pickup_lga": "'.$origin_city.'", 
"weight":'.$order->getWeight().',
"courier_id":"ksixga9",
"client_id":"'. $client_id.'" }';
return $post;

       
  }






}