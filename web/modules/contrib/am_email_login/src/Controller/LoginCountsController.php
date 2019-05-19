<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

class LoginCountsController extends ControllerBase {

  // Insert the record
  public function insert($user_mail,$_uid) {

    $created = time();
    $changed = time();
    
    $query = \Drupal::database()->insert('am_registration_login_count');
         $query->fields([
           'uid',
           'mail',
           'logincount',
           'generatecount',
           'created',
           'changed',
         ]);
         $query->values([
           $_uid,
           $user_mail,
           0,
           1,
           $created,
           $changed,
         ]);
     $result = $query->execute();
    
    return $result;
  }

  // Update the login counts
  public function updateCount($user_mail,$login_count) {

    $changed = time();
    $login_count++;
    
    $query = \Drupal::database()->update('am_registration_login_count');
    $query->fields([
      'logincount' => $login_count,
      'changed' => $changed,
    ]);
    $query->condition('mail', $user_mail);
    $query->execute();
    
    return $result;
  }

  // Get the login counts.
  public function getCount($user_mail) {

    $query = \Drupal::database()->select('am_registration_login_count', 'am');
    $query->addField('am', 'logincount');
    $query->condition('am.mail', $user_mail);
    $query->range(0, 1);
    $count = $query->execute()->fetchField();

    return $count;

  }

  // Update the login link generate counts
  public function updateGenerateCount($user_mail,$generate_count) {

    $changed = time();
    $generate_count++;
    
    $query = \Drupal::database()->update('am_registration_login_count');
    $query->fields([
      'generatecount' => $generate_count,
      'changed' => $changed,
    ]);
    $query->condition('mail', $user_mail);
    $query->execute();
    
    return $result;
  }

  // Get the login link generate counts.
  public function getGenerateCount($user_mail) {

    $query = \Drupal::database()->select('am_registration_login_count', 'am');
    $query->addField('am', 'generatecount');
    $query->condition('am.mail', $user_mail);
    $query->range(0, 1);
    $count = $query->execute()->fetchField();

    return $count;

  }

  // Check if mail exists in the records.
  public function exists($user_mail) {

    $query = \Drupal::database()->select('am_registration_login_count', 'am');
    $query->fields('am', ['mail']);
    $query->condition('am.mail', $user_mail);
    $query->range(0, 1);
    $result = $query->execute()->fetchAssoc();

    return $result;

  }

}