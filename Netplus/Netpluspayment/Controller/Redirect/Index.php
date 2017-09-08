<?php
    namespace Netplus\Netpluspayment\Controller\Redirect;
  use \Magento\Framework\View\Result\PageFactory;
class Index extends \Magento\Framework\App\Action\Action
{

  protected $resultJsonFactory;
  protected $resultPageFactory;
   protected $_checkoutSession;
      protected $_config;
      protected $_storeManager;
  

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
      \Magento\Framework\View\Result\PageFactory $resultPageFactory,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
         \Magento\Store\Model\StoreManagerInterface $storeManager,
      \Magento\Checkout\Model\Session $checkoutSession
     ){
        parent::__construct($context);
         $this->_config =   $scopeConfig;
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager=$storeManager;
    }

    public function execute()
    {

          $order = $this->_checkoutSession->getLastRealOrder();
        $order_id=$order->getIncrementId();

         $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $merchant_id=$this->_config->getValue('payment/netpluspayment/merchant_id', $storeScope);
        $websiteUrl=$this->_storeManager->getStore()->getBaseUrl();
        if($this->_config->getValue('payment/netpluspayment/test_mode', $storeScope))
           $gateway_url='https://netpluspay.com/testpayment/paysrc/';
        else
            $gateway_url='https://netpluspay.com/payment/';

      
        $form='<labe>...redirecting to Netplus Payment Gateway to complete your payment..</label>
        <form class="demo-fm" method="POST" id="netpluspay_form" name="netpluspay_form"
action="'.$gateway_url.'" >
 <div class="col-md-12">
 <div class="form-group">
 <input type="text" required="required" class="form-control"  hidden="hidden" name="full_name" value="'.$order->getShippingAddress()->getName().'"
placeholder="John Doe">
 </div>
 </div>
 <div class="col-md-12">
 <div class="form-group">
 <input type="text" required="required" class="form-control"  hidden="hidden" name="email"  value="'.$order->getShippingAddress()->getEmail().'"
placeholder="example@email.com">
 </div>
 </div>
 <div class="col-md-6">
 <div class="form-group">
 <input type="number" hidden="hidden" required="required" class="form-control" name="total_amount"
placeholder="&#8358;1000" value="'. $order->getGrandTotal().'">
 </div>
 </div>
 <div class="col-md-6">
 <button  hidden="hidden" type="submit" class="btn btn-default">Pay</button>
 </div>
 <input type="hidden" name="merchant_id" value="'.$merchant_id.'">
 <input type="hidden" name="currency_code" value="NGN">
 <input type="hidden" name="narration" value="Online order">
 <input type="hidden" name="order_id" value="'.$order_id.'">
 <input type="hidden" name="return_url" value="'. $websiteUrl.'netpluspayment/response">
 <input type="hidden" name="recurring" value="no">

 </form>
<script type="text/javascript">document.getElementById("netpluspay_form").submit();</script>
 ';
       
 echo $form;
    }
}