<?php

namespace Drupal\commerce_salesforce_connector\Controller;
use Symfony\Component\HttpFoundation\Response;


class getUsersController {


    const MINTIME = 3600;  //Time up to which users Data will be Fetched in seconds
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getUsersData() {

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
      $result = $connection->query("select name,uid from {users_field_data} where (created >= :time or changed >= :time or uid NOT IN (select entity_id from {user__field_salesforce_id})) LIMIT 20", array(':time'=>$mintime))->fetchAll();
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
