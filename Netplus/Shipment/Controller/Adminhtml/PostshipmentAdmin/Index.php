<?php
/**
 *
 * Copyright © 2015 Netpluscommerce. All rights reserved.
 */
namespace Netplus\Shipment\Controller\Adminhtml\PostshipmentAdmin;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Netplus\Shipment\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\RedirectFactory;
class Index extends \Magento\Backend\App\Action
{


    protected $_helper;
    protected $_messageManager;
    protected $_orders;
    protected $_resultRedirectFactory;
    protected $_region;

    public function __construct(
        Context $context,
        Data $helper,
        ManagerInterface $messageManager,
        Order $orders,
        \Magento\Directory\Model\Region $region,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->_helper=$helper;
        $this->_messageManager=$messageManager;
        $this->_orders=$orders;
        $this->_resultRedirectFactory=$resultRedirectFactory;
        $this->_region=$region;
        $this->_countryFactory = $countryFactory;

    }
    /**
     * Check the permission to run it
     *
     * @return bool
     */
    /*  protected function _isAllowed()
     {
         return $this->_authorization->isAllowed('Magento_Cms::page');
     } */

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

//get order object details and populate payload
        $order_id=$this->getRequest()->getParam('order_id');
        $shipping_price=$this->getRequest()->getParam('shipping_price');
        $order=$this->_orders->load($order_id);

        $origin_country = $this->_countryFactory->create()->loadByCode($this->_helper->getNetplusConfig('country_id'))->getName();

        $shippingCountry = $this->_countryFactory->create()->loadByCode($order->getShippingAddress()->getData()['country_id'])->getName();

        $pickup_date= $order->getCreatedAt();
        $origin_name=$this->_helper->getNetplusConfig('origin_name');
        $origin_phone=$this->_helper->getNetplusConfig('origin_phone');
        $origin_street=$this->_helper->getNetplusConfig('origin_street');
        $origin_city=$this->_helper->getNetplusConfig('origin_city');
        $origin_email=$this->_helper->getNetplusConfig('origin_email');
        $origin_state=$this->_region->load($this->_helper->getNetplusConfig('region_id'))->getName();
        $client_id=$this->_helper->getNetplusConfig('client_id');
        $client_secret=$this->_helper->getNetplusConfig('client_secret');
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


            $post='{"transaction_id":"'.$order->getIncrementId().'",    
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
"pre_auth":"0",
"status":"0",
"POD": "'.$pod.'"}';


            $url=$this->_helper->getBaseUrl();


            $client_id=$this->_helper->getNetplusConfig("client_id");
            $client_secret=$this->_helper->getNetplusConfig("client_secret");


            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'CLIENT-ID: '.$client_id,
                'CLIENT-SECRET: '.$client_secret,
                'Content-Type: application/json'));


            $resp= curl_exec($ch);

            $response= json_decode($resp);


            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// close the connection, release resources used
            curl_close($ch);
            var_dump($response);
            if(!isset($response->error) && !isset($response->fail))
            {
                $tracking_code=$response->delivery_id;
                $output.=$response->success;
                $output.="; Delivery ID ".$tracking_code;



            }else
            {
                if(isset($response->error)){
                    $output.=$response->error;
                }
                if(isset($response->fail)){
                    $output.=$response->fail;
                }


            }


        }//end for each

        if(!isset($response->error) && !isset($response->fail))
        {
            $this->_helper->make_shipment($order,$tracking_code,"Saddle");
            $this->messageManager->addSuccessMessage('Shipment Created. '.$output);
        }else
        {
            $this->messageManager->addErrorMessage($output);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view',array('order_id' =>$order_id));
        return $resultRedirect;


    }
}





