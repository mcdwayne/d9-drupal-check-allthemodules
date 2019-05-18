<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the ajax demo form controller.
 *
 * This example demonstrates using ajax callbacks to populate the options of a
 * color select element dynamically based on the value selected in another
 * select element in the form.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class CloudwordsTranslatableAddToProjectCheckbox extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = null) {
    $uid = \Drupal::currentUser()->id();
    $form['cloudwords_translatable_'.$entity->id()] = [
      '#type' => 'checkbox',
      '#return_value' => $entity->id(),
    ];

    if ($cache = cloudwords_project_user_get($uid)) {
      if(in_array($entity->id(), $cache)){
        $form['cloudwords_translatable_'.$entity->id()]['#default_value'] = $entity->id();
      }
    }

    if($entity->get('status')->value == CLOUDWORDS_QUEUE_IN_PROJECT) {
      $form['cloudwords_translatable_' . $entity->id()]['#disabled'] = true;
    }
    return $form;
  }
  public function ajaxSelectCallback(array $form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_ajax_selection';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
