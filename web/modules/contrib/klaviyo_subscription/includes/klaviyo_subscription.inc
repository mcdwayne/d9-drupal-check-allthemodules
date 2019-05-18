<?php

use Drupal\Core\Database\Database;
/**
 * @file
 * Subscribe user on register.
 */

 /**
  * This is helper function thhat will return array of all lists from kalviyo(that are created from given api)
  */
 function klaviyo_subscription_get_klaviyo_list() {
	$config = \Drupal::config('klaviyo_subscription.config');
	$record_array = array();
	if(!empty($config)) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		curl_setopt_array($curl, array(
		  CURLOPT_RETURNTRANSFER => 1,
		  CURLOPT_URL => 'https://a.klaviyo.com/api/v1/lists?api_key=' . $config->get('kl_api'),
		));
		
		$response = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($response);
		$records = $data->{'data'};
		foreach ($records as $key => $record) {
		  $record_array[$record->id] = $record->name;
		} 
	}

	return $record_array;
 }
 
 /**
  * This is helper function will return all klaviyo lists already added in drupal database.
  */ 
 function klaviyo_subscription_get_drupal_list() {
	$connection = Database::getConnection();
	$sth = $connection->select('klaviyo_lists', 'kl')
		->fields('kl', array('klaviyo_id', 'kl_title'));
	$data = $sth->execute();

	$results = $data->fetchAll(\PDO::FETCH_OBJ);
	foreach ($results as $row) {
		$kl_lists[$row->klaviyo_id] = $row->kl_title;
	}
	return $kl_lists;
 }
 
 /**
  * This function will accept parameter klaviyo id and check in drupal database if any list already created with that klaviyo id then it will return all related info.
  * @param $klid
  *   This is klaviyo id  
  */
 function klaviyo_subscription_single_list($klid){
	 $connection = Database::getConnection();
	$sth = $connection->select('klaviyo_lists', 'kl')
		->fields('kl', array())
		->condition('kl.klaviyo_id', $klid, '=');
	$data = $sth->execute();

	$results = $data->fetchAll(\PDO::FETCH_OBJ);

	if(!empty($results)) {
		return $results[0];	
	} else {
		return false;
	}
 }
 
 /**
  * Subscribe to klaviyo 
  * @param $klid
  *   This is klaviyo list id
  * @param $email
  *   This is email that need to subscribe in klaviyo
  */
  function klaviyo_subscription_subscribe_list($klid, $email){
	$config = \Drupal::config('klaviyo_subscription.config'); 
	$data = array();
	if(!empty($config)) {
		$data = json_encode($data);
		$curl = curl_init();
		 curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'email= ' . $email . ' &confirm_optin=false');
		curl_setopt($curl, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/list/' . $klid . '/members?api_key=' . $config->get('kl_api'));
		$response = curl_exec($curl);
		$data = json_decode($response);
		if (!empty($data->person->id)) {
			return $data->person->id;
		} else {
			return false;
		}
	} else {
			return false;
		}
  }
 
 /**
  * Check if usr subscribe or not in given list to klaviyo
  * @param $list
  *  This is  klaviyo list Id
  * @param $email
  *   This is email which need to check in kalviyo
  */
 function klaviyo_subscription_check_subscribe($list, $email) {
	$config = \Drupal::config('klaviyo_subscription.config');
	if(!empty($config)) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/list/'.$list.'/members?api_key=' . $config->get('kl_api') . '&email=' . $email);
		$response = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($response);
		
		if($data->page_size > 0) {
			return true;
		} else {
			return false;
		}
	} else {
			return false;
	}
 }
 
 /**
  * Unsubscribe klaviyo function.
  */
 function klaviyo_subscription_unsubscribe_list($list, $email){
	$config = \Drupal::config('klaviyo_subscription.config');
	if(!empty($config)) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'email='.$email.'&reason=unsubscribe');
		curl_setopt($curl, CURLOPT_URL, 'https://a.klaviyo.com/api/v1/list/'.$list.'/members/exclude?api_key=' . $config->get('kl_api'));
		 $response = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($response);
	}
 }