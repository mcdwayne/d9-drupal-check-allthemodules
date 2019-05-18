<?php
/**
 * @file
 * Contains \Drupal\formdefaults\Controller\FormDefaultsController
 */
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

namespace Drupal\formdefaults\Controller;
/**
 * Controller routines for formdefaults routes.
 */
class FormDefaultsController {
  /**
   * Edit form / field 
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function formDefaultsEdit($formid = '', $field = '') {
    if (!$field) {
      return \Drupal::formBuilder()->getForm('Drupal\formdefaults\Form\EditForm');
    }
    elseif ($formid) {
      return \Drupal::formBuilder()->getForm('Drupal\formdefaults\Form\EditFieldForm');
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  public function getFormDefaultsEditTitle($formid) {
    return t('Edit Form ') . $formid;
  }

  public function getFormDefaultsEditFieldTitle($formid, $field) {
    return t('Edit field @fieldname in @formid', array( '@fieldname' => $field, '@formid' => $formid));
  }

  /**
   * formdefaults admin settings to enable / disable form label editing
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function formDefaultsAdmin() {
    return \Drupal::formBuilder()->getForm('Drupal\formdefaults\Form\AdminForm');
  }

  /**
   * formdefaults manage menu
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function formDefaultsManage() {
    return \Drupal::formBuilder()->getForm('Drupal\formdefaults\Form\ManageForm');
  }

  /**
   * formdefaults export menu
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function formDefaultsExport() {
    // :TODO: implement
    return array('#markup' => '<h2>Coming soon...</h2>'. $formdefaults_export . '</p>');
  }

  /**
   * formdefaults import menu
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function formDefaultsImport() {
    // :TODO: implement
    return array('#markup' => '<h2>Coming soon...</h2>'. $formdefaults_import . '</p>');
  }

}
