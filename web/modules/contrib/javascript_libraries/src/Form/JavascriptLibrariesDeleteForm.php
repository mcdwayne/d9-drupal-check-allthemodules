<?php

/**
 * @file
 * Contains \Drupal\javascript_libraries\Form\JavascriptLibrariesDeleteForm.
 */

namespace Drupal\javascript_libraries\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class JavascriptLibrariesDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_libraries_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the library id %id?', array('%id' => $this->id));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('javascript_libraries.custom_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This operation once done cannot be reverted');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $library = NULL) {
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');
    if (!array_key_exists($library, $custom)) {
      drupal_set_message("The library with following identifier doesnot exist");
      return;
    }
    $this->id = $library;
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');
    $library_id = $this->id;
    unset($custom[$library_id]);
    \Drupal::configFactory()->getEditable('javascript_libraries.settings')
      ->set('javascript_libraries_custom_libraries', $custom)
      ->save();
    drupal_set_message("The library has been deleted succesfully");
    return $form_state->setRedirect('javascript_libraries.custom_form');
  }

}
