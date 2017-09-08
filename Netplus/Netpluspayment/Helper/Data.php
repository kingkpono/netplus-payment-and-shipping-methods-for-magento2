<?php
/**
 * Copyright © 2015 Netplus . All rights reserved.
 */
namespace Netplus\Netpluspayment\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

     protected $_context;

     

  /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
  public function __construct(\Magento\Framework\App\Helper\Context $context
   

  ) {
  
    
      $this->_context=$context;
   
          parent::__construct($context);

  }


  

}