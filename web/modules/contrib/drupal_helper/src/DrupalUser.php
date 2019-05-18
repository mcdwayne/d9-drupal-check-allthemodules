<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 11/6/18
 * Time: 4:11 PM
 */

namespace Drupal\drupal_helper;


class DrupalUser {
  public function hasRole($role_name,$uid=null){
    $userCurrent = \Drupal::currentUser();
    if(is_numeric($uid)){
      $userCurrent = \Drupal::entityTypeManager()
        ->getStorage('user')->load($uid);
    }
    $is=false ;
    if (in_array($role_name, $userCurrent->getRoles())) {
      $is = true ;
    }
    return $is;
  }
  public  function is_admin($uid=null) {
    return $this->hasRole('administrator',$uid);
  }
}