<?php

namespace Drupal\commerce_salesforce_connector\Controller;
use Symfony\Component\HttpFoundation\Response;


class getProductsController {


    const MINTIME = 3600;  //Time up to which users Data will be Fetched in seconds
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getProductsData() {

  $request = \Drupal::request();
  $securityKeySF = $this->base64_url_decode(trim($request->headers->get('securityKey')));

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
  $result = $connection->query("select variation_id,product_id, sku, type,title,price__number,price__currency_code from        commerce_product_variation_field_data where (variation_id NOT IN (select entity_id from commerce_product_variation__field_salesforce_id) or created >= :time or changed >= :time) LIMIT 20",array(':time'=>$mintime))->fetchAll();

   return json_encode($result);
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
