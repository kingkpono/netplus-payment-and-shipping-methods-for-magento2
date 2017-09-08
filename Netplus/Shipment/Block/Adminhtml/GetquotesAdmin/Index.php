<?php
/**
* Copyright © 2015 Netplus . All rights reserved.
*/
namespace Netplus\Shipment\Block\Adminhtml\GetquotesAdmin;
class Index extends \Magento\Backend\Block\Template
{

public function getOrderId()
{

$requestParams=$this->getRequest()->getParams();
return $requestParams['order_id'];
}
public function getShipmentPostUrl()
{
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$helper= $objectManager->create('\Netplus\Shipment\Helper\Data');
return $helper->getShipmentPostUrl();
}
public function has_weight()
{
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$helper= $objectManager->create('\Netplus\Shipment\Helper\Data');
return $helper->has_weight($this->getOrderId());
}
public function getQuotes() {
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$helper= $objectManager->create('\Netplus\Shipment\Helper\Data');
  
// "Fetch" display
//get order object details and populate payload
$order_id=$this->getOrderId();
$order=null;
$response=null;
$output="";
if(isset($order_id))
{
$order=$helper->getOrder($order_id);
$post= $helper->build_rates_payload($order);
}else{
/*
$this->messageManager->addError($this->__('No Order Id'));
$block=$this->getLayout()->createBlock('core/text','netplus-block')->setText("<h2>No Order Id</h2>");
$this->_addContent($block);
$this->_setActiveMenu("sales");
$this->renderLayout();
*/
}
$url= 'http://saddleng.com/v2/shipping_price';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json')
);


$response= json_decode(curl_exec($ch));

$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// close the connection, release resources used

curl_close($ch);
 if($httpcode==200 ){
$output=$response->{'Shipping Price'};
}else{
$output=$response->{'Warning'};;
}
return $output;
}



public function is_valid_price($order_id) {

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$helper= $objectManager->create('\Netplus\Shipment\Helper\Data');
$order=$helper->getOrder($order_id);
$post= $helper->build_rates_payload($order);

$url= 'http://saddleng.com/v2/shipping_price';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json')
);


$response= json_decode(curl_exec($ch));

$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// close the connection, release resources used
curl_close($ch);

if($httpcode==200 ){
return true;
}else{
return false;
}

}

  
}