<?php
/**
 * @file
 * Helper functions that utilize Canvas' Accounts APIs
 *
 * See @link https://canvas.instructure.com/doc/api/accounts.html @endlink
 *
 */
namespace Drupal\canvas_api;


/**
 * {@inheritdoc}
 */
class CanvasAccounts extends Canvas {

  /**
   * List Canvas accounts
   *
   * See @link https://canvas.instructure.com/doc/api/accounts.html#method.accounts.index @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.accounts');
   *  $accounts = $canvas_api->listAll();
   *
   * @return array
   */
  public function listAll(){
    $this->path = "accounts";
    return $this->get();
  }

  /**
   * List Canvas sub accounts
   *
   * See @link https://canvas.instructure.com/doc/api/accounts.html#method.accounts.sub_accounts @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.accounts');
   *  $accounts = $canvas_api->listSubAccounts(1);
   *
   * @return array
   */
  public function listSubAccounts($accountID){
    $this->path = "accounts/$accountID/sub_accounts";
    return $this->get();   
  }

  /**
   * Create Canvas sub account
   *
   * See @link https://canvas.instructure.com/doc/api/accounts.html#method.sub_accounts.create @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.accounts');
   *  $canvas_api->params = array(
   *    'account' => array(
   *       'name' => 'New Test Sub Account',
   *       'sis_account_id' => 'TEST',
   *    ),
   *  );
   *  $accounts = $canvas_api->createSubAccount(1);
   *
   * @return array
   *  The newly created account
   */  
  public function createSubAccount($accountID){
    $this->path = "accounts/$accountID/sub_accounts";
    return $this->post();
  }
 
  /**
   * Create Canvas sub account
   *
   * See @link https://canvas.instructure.com/doc/api/accounts.html#method.accounts.show @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.accounts');
   *  $account = $canvas_api->getAccount(sis_account_id:DEMO123);
   *
   * @return array
   */  
  public function getAccount($accountID){
    $this->path = "accounts/$accountID";
    return $this->get();
  }
  
  /**
   * List active courses in an account
   *
   * See @link https://canvas.instructure.com/doc/api/accounts.html#method.accounts.courses_api @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.accounts');
   *  $canvas_api->params = array(
   *    'state' => array('created','available'),
   *  );
   *  $courses = $canvas_api->listActiveCourses();
   *
   * @return array
   */   
  public function listActiveCourses($accountID = 1){
    $this->path = "accounts/$accountID/courses";
    return $this->get();
  }
}
