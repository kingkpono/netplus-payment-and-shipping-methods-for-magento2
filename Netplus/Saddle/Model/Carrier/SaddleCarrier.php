<?php
namespace Netplus\Saddle\Model\Carrier;
use Magento\Quote\Model\Quote\Address\RateRequest;
class SaddleCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
\Magento\Shipping\Model\Carrier\CarrierInterface
{
/**
* @var string
*/
protected $_code = 'saddlecarrier';
    
    protected $_logger;
/**
* @var bool
*/
protected $_isFixed = true;
 protected $scopeConfig;
protected $_orders;
protected $_region;
/**
* @var \Magento\Shipping\Model\Rate\ResultFactory
*/
protected $_rateResultFactory;
/**
* @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
*/
protected $_rateMethodFactory;
/**
* @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
* @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
* @param \Psr\Log\LoggerInterface $logger
* @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
* @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
* @param array $data
*/
public function __construct(
\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
\Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
\Psr\Log\LoggerInterface $logger,
\Magento\Sales\Model\Order $orders,
\Magento\Directory\Model\Region $region,
\Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
\Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
array $data = []
) {
$this->_rateResultFactory = $rateResultFactory;
 $this->scopeConfig = $scopeConfig;
$this->_rateMethodFactory = $rateMethodFactory;
        $this->_logger = $logger;
         $this->_region=$region;
parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
}
public function getNetplusConfig($param) {
$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
return $this->scopeConfig->getValue("shipment/parameters/".$param, $storeScope);
}
/**
* @param RateRequest $request
* @return \Magento\Shipping\Model\Rate\Result|bool
*/
public function collectRates(RateRequest $request)
{
if (!$this->getConfigFlag('active')) {
return false;
}

/** @var \Magento\Shipping\Model\Rate\Result $result */
$result = $this->_rateResultFactory->create();
        
        $shippingPrice = $this->getConfigData('price');
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));
//get quotes from saddle
$origin_state=$this->_region->load($this->getNetplusConfig('region_id'))->getName();

$origin_city=$this->getNetplusConfig('origin_city');
$client_id=$this->getNetplusConfig('client_id');

$weight=$request['package_weight'];
$city='Lagos';
if($request['dest_city']!=null && $request['dest_city']!="")
$city=$request['dest_city'];
	
$origin_state=$this->_region->load($this->getNetplusConfig('region_id'))->getName();

$state=$this->_region->load($request->getDestRegionId())->getName();

$post='{ "delivery_state": "'.$state.'",
"delivery_lga": "'.$city.'",
"pickup_state": "'.$origin_state.'",
"pickup_lga": "'.$origin_city.'",
"weight":'.$weight.',
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
$shippingPrice=$response->{'Shipping Price'};
} else{
return false;

}
        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        $result->append($method);

return $result;
}
/**
* @return array
*/
public function getAllowedMethods()
{
        
return [$this->_code=> $this->getConfigData('name')];
}
}