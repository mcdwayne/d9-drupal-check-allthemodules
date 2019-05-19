<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\am_registration\Controller\InsertLinkController;
use Drupal\am_registration\Controller\DeleteLinkController;
use Drupal\am_registration\Controller\SendLinkController;
use Drupal\am_registration\Controller\LoginCountsController;

class CreateLoginLinkController extends ControllerBase {

  public function createLoginLink($user) {

  	global $base_url;

  		 
     // Delete any previous link
        $delete_result = new DeleteLinkController;
        $value = $delete_result->delete($user->id());

  	// Prepare one time login link.
         $uid = $user->id();
         $user_mail = $user->getEmail();
         $six_digit_random_number = mt_rand(100000, 999999);
         $login_hash = md5($user->getEmail().time()); // encrypted email+timestamp
         $created = time();

    //One time Login Link
         $link = $base_url.'/user/amlogin/'.$uid.'/'.$six_digit_random_number.'/'.$login_hash;

    // Insert the created link into db.
          $insert_result = new InsertLinkController;
          $result = $insert_result->insert($uid,$user_mail,$six_digit_random_number,$login_hash,$created);

    // Send/Mail link to user.
          $send_result = new SendLinkController;
          $value = $send_result->sendMail($user,$link,$user_mail);

    //One time generate counts
          $LoginCountsController = new LoginCountsController;
          $status = $LoginCountsController->exists($user_mail);
          if($status == FALSE){
            $result = $LoginCountsController->insert($user_mail,$uid);
          }else{
            $generate_count = $LoginCountsController->getGenerateCount($user_mail);
            $LoginCountsController->updateGenerateCount($user_mail,$generate_count);
          }

    return $result;
  }

}