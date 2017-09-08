<?php
/**
* Copyright © 2015 Netplus . All rights reserved.
*/
namespace Netplus\Netpluspayment\Block\Response;

class Index extends \Magento\Framework\View\Element\Template
{
protected $_orders;
protected $_region;
protected $_helper;
protected $_countryFactory;
public function __construct(
\Magento\Framework\View\Element\Template\Context $context,
\Magento\Sales\Model\Order $orders,
\Netplus\Shipment\Helper\Data $helper,
 \Magento\Directory\Model\CountryFactory $countryFactory,
\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
\Magento\Directory\Model\Region $region,
array $data = []
) {
$this->scopeConfig = $scopeConfig;
$this->_region=$region;
$this->_orders=$orders;
$this->_helper=$helper;
 $this->_countryFactory = $countryFactory;
parent::__construct($context, $data);
}
public function getNetplusConfig($param) {
$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
return $this->scopeConfig->getValue("shipment/parameters/".$param, $storeScope);
}
  

  public function getOrderId()
{

$requestParams=$this->getRequest()->getParams();

$status_code=$requestParams['code'];
$status_from_netplus=$requestParams['description'];
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$status= $objectManager->create("\Magento\Sales\Model\Order\Status")->load($status_code);

if(!$status->getStatus())
{//status deos not  exist
$status->setData('status',  $status_code)->setData('label',  $status_from_netplus)->save();
}else{

$order=$this->_orders->loadByIncrementId($requestParams['order_id']);
$order->setState("processing")->setStatus($status_code);
$order->save();
}


if($status_code=="00")
{

$origin_state=$this->_region->load($this->getNetplusConfig('region_id'))->getName();

$origin_city=$this->getNetplusConfig('origin_city');
$client_id=$this->getNetplusConfig('client_id');

$origin_country = $this->_countryFactory->create()->loadByCode($this->_helper->getNetplusConfig('country_id'))->getName();

$shippingCountry = $this->_countryFactory->create()->loadByCode($order->getShippingAddress()->getData()['country_id'])->getName();
$origin_name=$this->_helper->getNetplusConfig('origin_name');
$origin_phone=$this->_helper->getNetplusConfig('origin_phone');
$origin_street=$this->_helper->getNetplusConfig('origin_street');

$origin_email=$this->_helper->getNetplusConfig('origin_email');
$client_secret=$this->_helper->getNetplusConfig('client_secret');

$post='{ "delivery_state": "'.$order->getShippingAddress()->getRegion().'",
"delivery_lga": "'.$order->getShippingAddress()->getCity().'",
"pickup_state": "'.$origin_state.'",
"pickup_lga": "'.$origin_city.'",
"weight":'.$order->getWeight().',
"courier_id":"ksixga9",
"client_id":"'. $client_id.'" }';

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
   if(!isset($response->error) && !isset($response->fail) && !isset($response->Warning))
   {
$shipping_price=$response->{'Shipping Price'};

//post to delivery


$pickup_date= $order->getCreatedAt();   

//shipping street
 $shipping_street="";
 $i=0;

 $shipping_str_length=count($order->getShippingAddress()->getStreet());
 foreach ($order->getShippingAddress()->getStreet() as $str)
 {

 if($i != ($shipping_str_length-1))
 {
  $shipping_street.=$str.",";
  }
  else
 {
  $shipping_street.=$str;
  }

  $i++;
  
  }//end foreach
 $output="";
 $httpcode="";
 $tracking_code="";
 $pod=0;
 $payment_method="";
if($order->getPayment()->getMethodInstance()!=null)
{
 $payment_method=$order->getPayment()->getMethodInstance()->getCode();
}
 $pod=0;
 if($payment_method=="cashondelivery")
    $pod=1;
foreach($order->getAllItems() as $item)
{


$delivery_post='{"transaction_id":"'.$order->getIncrementId().'",    
"client_id":"'.$client_id.'",  
"item_cost":"'.$item->getPrice().'",
"delivery_cost":'.$shipping_price.',       
"courier_id":"ksixga9", 
"pickup_address":"'.$origin_street.'",  
"pickup_location":"'.$origin_city.'",   
"pickup_contactname":"'.$origin_name.'",    
"pickup_contactnumber":"'.$origin_phone.'", 
"pickup_contactemail":"'.$origin_email.'",  
"delivery_address":"'.$shipping_street.'",  
"delivery_location":"'.$order->getShippingAddress()->getCity().'",  
"delivery_contactname":"'.$order->getShippingAddress()->getName().'",   
"delivery_contactnumber":"'.$order->getShippingAddress()->getTelephone().'",    
"delivery_contactemail":"'.$order->getShippingAddress()->getEmail().'", 
"item_name":"'.$item->getName().'", 
"item_size":"", 
"item_weight":"'.$item->getWeight().'", 
"item_color":"-",   
"item_quantity":"'.$item->getQtyOrdered().'",   
"image_location":"'.$this->_helper->getWebsiteUrl().'pub/media/catalog/product'.$item->getProduct()->getData('image').'",  
"fragile":"1",  
"perishable":"1",
"pre_auth":"1",
"status":"0",
"POD": "'.$pod.'"

}';


$url=$this->_helper->getBaseUrl();


$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $delivery_post);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'client-id: '.$client_id,
'client-secret: '.$client_secret
));

 


$resp= curl_exec($ch);

$delivery_response= json_decode($resp);



$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// close the connection, release resources used
curl_close($ch);

if(!isset($delivery_response->error) && !isset($delivery_response->fail))
{
    $tracking_code=$delivery_response->delivery_id;
    $output.=$delivery_response->success;
    $output.="; Delivery ID ".$tracking_code;



}else
{
    if(isset($delivery_response->error)){
        $output.=$delivery_response->error;
    }
    if(isset($delivery_response->fail)){
        $output.=$delivery_response->fail;
    }


}


  
}//end for each

if(!isset($delivery_response->error) && !isset($delivery_response->fail))
{
$this->_helper->make_shipment($order,$tracking_code,"Saddle");
 
}
}//end outer if response is successful
}//if succesful
return $requestParams['order_id'];
}
    
public function getDescription()
{

$requestParams=$this->getRequest()->getParams();
return $requestParams['description'];
}
    public function getAmount()
{

$requestParams=$this->getRequest()->getParams();
return $requestParams['amount_paid'];
}
    
    public function getBank()
{

$requestParams=$this->getRequest()->getParams();
return $requestParams['bank'];
}

    public function getTransactionId()
{

$requestParams=$this->getRequest()->getParams();
return $requestParams['transaction_id'];
}


  
}