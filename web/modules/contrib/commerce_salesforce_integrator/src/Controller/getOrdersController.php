<?php

namespace Drupal\commerce_salesforce_connector\Controller;
use Symfony\Component\HttpFoundation\Response;


class getOrdersController{


    const MINTIME = 3600;  //Time up to which users Data will be Fetched in seconds
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getOrdersData() {


  $request = \Drupal::request();
  $securityKeySF =$this->base64_url_decode(trim($request->headers->get('securityKey')));
 
  $securityKeyDrupal  = trim(\Drupal::config('form.adminsettings')->get('securityKey'));  

  if($securityKeySF == $securityKeyDrupal){
     return  $this->sendResponseData($this->getData());
  }
  else{
     return $this->unauthorizedRequest();
  }
}
 
   private function getData(){

  $mintime = time() - self::MINTIME;  
      $connection = \Drupal::database();

      $getOrders = $connection->query("SELECT order_id,uid  FROM commerce_order where order_id NOT IN (SELECT entity_id FROM commerce_order__field_salesforce_id)");



      $ordersAll = $getOrders->fetchAll();
      
      $retData = array();
      $i = 0;
      foreach($ordersAll as $order) {

          $orderId = $order->order_id;
          $getProductsOrder = $this->getProductsOrder($orderId, $connection);
          $UserSfid = $this->getUserSfid($order->uid,$connection);

          $empty=0;
          $count =0;

          foreach($getProductsOrder as $getOrder) {
              $count=$count+1;
          $ProductSfid=$this->getProductSfid($getOrder->purchased_entity,$connection);

          if (empty($ProductSfid)){
            $empty= 1;
            break;
          }
         }     
          if ($empty==0){
            if($count+$i>20)
              break;
         
            foreach($getProductsOrder as $getOrder) {

            $ProductSfid=$this->getProductSfid($getOrder->purchased_entity,$connection);

            $quantity = $getOrder->quantity;
            
              $retData[$i]= array(
                      "usersfid" => $UserSfid,
                      "productsfid" => $ProductSfid,
                      "quantity" => $quantity,
                      "order_id" => $orderId
              );
              $i=$i+1;  
            }
          }
          
     }
     $result = json_encode($retData);


    return $result;
}


  private function getUserSfid($uid,$connection){
      $UserSfid =array();
        $UserSfid = $connection->select("user__field_salesforce_id", "user__field_salesforce_id")
              ->fields("user__field_salesforce_id",array("field_salesforce_id_value"))
              ->condition("user__field_salesforce_id.entity_id",$uid,"=");

    $UserSfid = $UserSfid->execute()->fetchAll();
    $UserSfid = $UserSfid[0]->field_salesforce_id_value;
    return $UserSfid;
  }

  private function getProductSfid($purchased_entity,$connection){
    
    $ProductSfid =array();
        $ProductSfid = $connection->select("commerce_product_variation__field_salesforce_id", "commerce_product_variation__field_salesforce_id")
              ->fields("commerce_product_variation__field_salesforce_id",array("field_salesforce_id_value"))
              ->condition("commerce_product_variation__field_salesforce_id.entity_id",$purchased_entity,"=");

    $ProductSfid = $ProductSfid->execute()->fetchAll();
    
  $ProductSfid=$ProductSfid[0]->field_salesforce_id_value;
  return $ProductSfid;
  }

  private function getProductsOrder($orderId, $connection) {
   $getProductsArray = array();

   $getProductsOrder = $connection->select("commerce_order_item", "commerce_order_item")
              ->fields("commerce_order_item",array("purchased_entity","quantity"))
              ->condition("commerce_order_item.order_id",$orderId,"=");


   $getProductsArray = $getProductsOrder->execute()->fetchAll();
   return $getProductsArray;
  }

   private function base64_url_decode($input) {
 	 return base64_decode(strtr($input, '._-', '+/='));
   }

   private function sendResponseData($data) {
     return  new Response(
      $data,
      Response::HTTP_OK,
      array());
   }

   private function  unauthorizedRequest() {
             return  new Response(
      'INVALID REQUEST',
      Response::HTTP_UNAUTHORIZED,
      array());
  }

}
?>
