<?php

/**
 * @file
 */
namespace Drupal\userregistration;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use \LoginRadiusSDK\LoginRadiusException;
use \LoginRadiusSDK\CustomerRegistration\AccountAPI;
use \LoginRadiusSDK\CustomerRegistration\UserAPI;


/**
 * Returns responses for Simple FB Connect module routes.
 */
class UserRegistrationUserManager {

  public $module_config;
  protected $connection;
  protected $apiSecret;
  protected $apiKey;

  public function __construct() {
    $this->connection = \Drupal\Core\Database\Database::getConnection();
    $this->module_config = \Drupal::config('sociallogin.settings');
    $this->apiSecret = trim($this->module_config->get('api_secret'));
    $this->apiKey = trim($this->module_config->get('api_key'));
  }

  function userregistration_update_user_table($raas_uid, $user_id) {
  try {
      $this->connection->update('users')
        ->fields(array('lr_raas_uid' => $raas_uid))
        ->condition('uid', $user_id)
        ->execute();
  } catch (Exception $e) {
    //watchdog_exception('type', $e);
  }
}
function user_delete($user_id){ 
 $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
 try {  
  return $accountObj->deleteAccount($user_id);
}catch (LoginRadiusException $e) {
}
}
function user_additional_email($uid, $action, $data){ 

 $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
 try {  
  return $accountObj->userAdditionalEmail($uid, $action, $data);
}catch (LoginRadiusException $e) {
    if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    }  
  }
}

  /**
 * get accounts
 *
 * @param $uid user id
 * @return mixed
 */
function userregistration_get_accounts($uid) {
  $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
  try {
  return $accountObj->getAccounts($uid);
}catch (LoginRadiusException $e) {
     if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    } 
}
}
  /**
 * Get Raas uid.
 * @param $user_id user id
 * @return mixed
 */
function userregistration_get_raas_uid($user_id) {
 return  $this->connection->query('SELECT lr_raas_uid FROM {users} WHERE uid = :id', array('id' => $user_id))->fetchField();
}
  /**
 * Get Raas uid.
 * @param $user_id user id
 * @return mixed
 */
function userregistration_get_raas_email($user_id) {
 return  $this->connection->query('SELECT mail FROM {users_field_data} WHERE uid = :id', array('id' => $user_id))->fetchField();
}
  /**
 * Get Raas uid.
 * @param $user_id user id
 * @return mixed
 */
function userregistration_get_raas_uname($user_id) {
 return  $this->connection->query('SELECT name FROM {users_field_data} WHERE uid = :id', array('id' => $user_id))->fetchField();
}

  /**
 * Block user at Raas
 *
 * @param $uid user id
 * @return mixed
 */
function userregistration_block_user($uid) {
  $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
  try {
  return $accountObj->setStatus($uid);
}catch (LoginRadiusException $e) {
}
}

/**
 * Unblock user at raas.
 *
 * @param $uid user id
 * @return mixed
 */
function userregistration_unblock_user($uid) {  
   $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
    try {
  return $accountObj->setStatus($uid, false);
  }catch (LoginRadiusException $e) {
}
}


function userregistration_get_raas_provider_id($account_id) {
  return $this->connection->query('SELECT provider_id FROM {loginradius_mapusers} WHERE user_id = :id and provider = :name', array(
    'id' => $account_id,
    'name' => 'raas',
  ))->fetchField();
}

function update_user_password($uid, $old_password, $new_password){
 try {
  $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
  return $accountObj->changeAccountPassword($uid, $old_password, $new_password);
 } catch (LoginRadiusException $e) {
    if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    }
  }
}

function userregistration_create_user($data){
  try {
  $userObj = new UserAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
return   $userObj->create($data);
}
catch (LoginRadiusException $e) {
    if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    }
  }
}

function userregistration_set_password($uid, $password){
    try {
  $accountObj = new AccountAPI($this->apiKey, $this->apiSecret);
  $accountObj->setPassword($uid, $password);
}
catch (LoginRadiusException $e) {
    //watchdog_exception('type', $e);
  }
}

function create_raas_profile($data){
    $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
  try {  
return $accountObj->createUserRegistrationProfile($data);
}
catch (LoginRadiusException $e) {
    //watchdog_exception('type', $e);
    if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    }
  }
}

function userregistration_unlink_account($raas_uid, $provider, $provider_id){ 
    try {
  $accountObj = new AccountAPI($this->apiKey, $this->apiSecret,  array('output_format' => 'json'));
return $accountObj->accountUnlink($raas_uid, $provider_id, $provider );
}
catch (LoginRadiusException $e) {
    if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
        return $e->getErrorResponse()->description;
    }
  //$message = explode('"description": "', $e->getMessage());
  //return (isset($message[1]) ? (($msg = explode('"errorCode', $message[1])) ? str_replace('",', '.', $msg[0]) : $e->getMessage() ): $e->getMessage());    
  }
}

public function deleteSocialAccount($pid) {
    return $this->connection->delete('loginradius_mapusers')
      ->condition('provider_id', $pid)
      ->execute();
  }
public function deleteMapUser($aid) {
    return $this->connection->delete('loginradius_mapusers')
      ->condition('user_id', $aid)
      ->execute();
  }

}
