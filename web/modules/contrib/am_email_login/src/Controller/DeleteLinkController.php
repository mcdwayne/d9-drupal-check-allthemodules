<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

class DeleteLinkController extends ControllerBase {

  public function delete($_uid) {
    $query = \Drupal::database()->delete('am_registration');
    $query->condition('uid', $_uid);
    $result = $query->execute();
    
    return $result;
  }

}