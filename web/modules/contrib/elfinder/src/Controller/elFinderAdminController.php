<?php

/**
 * @file
 * Contains \Drupal\elfinder\Controller\elFinderAdminController.
 */

namespace Drupal\elfinder\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Controller routines for elFinder Admin routes.
 */
class elFinderAdminController extends ControllerBase {

  /**
   * Returns an administrative settings
   */
  public function adminSettings(Request $request) {

    $output['profile_list'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('elfinder-profile-list')),
      'title' => array('#markup' => '<h2>' . $this->t('Profiles') . '</h2>'),
      'list' => $this->entityManager()->getListBuilder('elfinder_profile')->render(),
    );
    

    $output['settings_form'] = \Drupal::formBuilder()->getForm('Drupal\elfinder\Form\AdminForm') + array('#weight' => 10);
    
    return $output;
  }


  public function page($scheme, Request $request) {
   return array();
  }

  public function checkAccess($scheme) {
    return AccessResult::allowedIf(TRUE);
  }

}
