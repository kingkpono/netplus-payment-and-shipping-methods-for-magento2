<?php
    namespace Netplus\Netpluspayment\Controller\Response;
  use \Magento\Framework\View\Result\PageFactory;
class Index extends \Magento\Framework\App\Action\Action
{

  protected $resultJsonFactory;
  protected $resultPageFactory;
  

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
      \Magento\Framework\View\Result\PageFactory $resultPageFactory
     ){
        parent::__construct($context);
    
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {

          $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->initMessages();
      
        return $resultPage;
    }
}