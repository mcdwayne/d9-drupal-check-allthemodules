<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\am_registration\Controller\DeleteLinkController;
use Drupal\am_registration\Controller\LoginCountsController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * An example controller.
 */
class EmailLoginController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function emailLogin($uid,$randno,$login_hash) {
   // Retrieves a \Drupal\Core\Database\Connection which is a PDO instance
   $connection = Database::getConnection();

    // Retrieves a PDOStatement object
    // http://php.net/manual/en/pdo.prepare.php
    $sth = $connection->select('am_registration', 'am')
        ->fields('am', array('uid','randno', 'hash','created','mail'))
        ->condition('am.uid', $uid, '=');

    // Execute the statement
    $data = $sth->execute();

    // Get all the results
    $results = $data->fetchAll(\PDO::FETCH_OBJ);
    if(count($results) == 0 ){
       drupal_set_message("You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new login link.","error");
      return new RedirectResponse('/user/login');
    }
    // Iterate results
    foreach ($results as $row) {
      //echo "Field a: {$row->randno}, field b: {$row->hash}, field c: {$row->created}";
      $_created = $row->created;
      $_randno = $row->randno;
      $_login_hash = $row->hash;
      $_mail = $row->mail;
      $_uid = $row->uid;
    }

    // Get current timestamp
    $current_time = time();

    // Check if link has expired. Current time is set to 24 hours.
    if(($current_time - $_created) > 86400){
      drupal_set_message("You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new login link.","error");
      return new RedirectResponse('/user/login');
    }else{
      
      $login_hash_status = strcmp($login_hash,$_login_hash);
      if(($login_hash_status == 0) && ($randno == $_randno)){
        // $user = user_load_by_mail($name);
        $account = \Drupal\user\Entity\User::load($_uid); // pass your uid
        user_login_finalize($account);

        // Login user to drupal and delete the previous one time url.
        $delete_result = new DeleteLinkController;
        $value = $delete_result->delete($_uid);

        //One time Login counts
          $LoginCountsController = new LoginCountsController;
          $status = $LoginCountsController->exists($_mail);
          if($status == FALSE){
            $result = $LoginCountsController->insert($_mail,$_uid);
          }else{
            $login_count = $LoginCountsController->getCount($_mail);
            $LoginCountsController->updateCount($_mail,$login_count);
          }

        drupal_set_message(t('Hello @user, You have just used your one-time login link.', array('@user' => $_mail)));
        return new RedirectResponse('/user');
      }else{
        drupal_set_message("Invalid login Link. Please request a new login link.","error");
        return new RedirectResponse('/user/login');
      }
    }

   

  }

}