<?php

namespace Drupal\sitelog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller.
 */
class ContentForm extends FormBase {

  /**
   * Form builder.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['status'] = array(
      '#type' => 'radios',
      '#title' => t('Status'),
      '#options' => array(
        'unpublished' => 'Unpublished',
        'published' => 'Published',
      ),
      '#default_value' => 'published',
    );
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($types as $type) {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['type' => $type->id()]);
      if ($nodes) {
        $options[$type->id()] = $type->label();
      }
    }
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Type'),
      '#options' => $options,
      '#default_value' => key($options),
    );
    return $form;
  }

  /**
   * Form identifier getter method.
   */
  public function getFormId() {}

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
