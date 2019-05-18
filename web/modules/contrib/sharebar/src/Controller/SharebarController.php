<?php

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

namespace Drupal\sharebar\Controller;

use Drupal\Core\Url;

/**
 * Controller routines for sharebar routes.
 */
class SharebarController {

  /**
   * Provide a single block from the administration menu as a page.
   */
  public function admin() {
    $item = menu_get_item();
    if ($content = system_admin_menu_block($item)) {
      $output = theme('admin_block_content', array('content' => $content));
    }
    else {
      $output = t('You do not have any administrative items.');
    }
    return $output;
  }

  public function admin_settings() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sharebar\Form\SharebarSettingsForm');
    return $form;
  }

  public function add_button() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sharebar\Form\SharebarAddButtonForm');
    return $form;
  }

  public function edit_button() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sharebar\Form\SharebarAddButtonForm');
    return $form;
  }
  public function del_button() {
    $item = menu_get_item();
    if ($content = system_admin_menu_block($item)) {
      $output = theme('admin_block_content', array('content' => $content));
    }
    else {
      $output = t('You do not have any administrative items.');
    }
    return $output;
  }


}