<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

class InsertLinkController extends ControllerBase {

  public function insert($uid,$user_mail,$six_digit_random_number,$login_hash,$created) {
    
    $query = \Drupal::database()->insert('am_registration');
         $query->fields([
           'uid',
           'mail',
           'randno',
           'hash',
           'created',
         ]);
         $query->values([
           $uid,
           $user_mail,
           $six_digit_random_number,
           $login_hash,
           $created,
         ]);
     $result = $query->execute();
    
    return $result;
  }

}