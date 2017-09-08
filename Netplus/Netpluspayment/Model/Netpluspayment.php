<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netplus\Netpluspayment\Model;



/**
 * Pay In Store payment method model
 */
class Netpluspayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'netpluspayment';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;


  

}
