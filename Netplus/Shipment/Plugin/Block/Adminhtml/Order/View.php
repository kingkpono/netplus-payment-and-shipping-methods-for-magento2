<?php
namespace Netplus\Shipment\Plugin\Block\Adminhtml\Order;

class View{
 

	public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
{
  
  
$url=$this->getAdminUrl('shipment/getquotesadmin/index/',$view->getOrderId());
if($view->getOrder()->canShip())
{
    $view->addButton(
        'order_netplusshipping',
        [
            'label' => __('Ship via Saddle'),
            'class' => 'reset',
            'onclick' => "setLocation('{$url}')"
        ]
    );
}

}
   public function getAdminUrl($route,$order_id)
    {
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
       $adminUrl = $objectManager->create('Magento\Backend\Helper\Data')->getUrl($route,array("order_id"=>$order_id));

        return $adminUrl;
    }

}