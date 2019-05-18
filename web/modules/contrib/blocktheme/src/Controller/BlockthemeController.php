<?php

namespace Drupal\blocktheme\Controller;

use Drupal\Core\Controller\ControllerBase;



/**
 * Controller routines for blocktheme routes.
 */
class BlockthemeController extends ControllerBase {

  /**
   * Render 'Admin page'
   * @return array
   */
  public function adminPage() {
    $build['#title'] = t('Block Theme');
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\blocktheme\Form\BlockthemeAdminSettingsForm');
    return $build;
  }

}
