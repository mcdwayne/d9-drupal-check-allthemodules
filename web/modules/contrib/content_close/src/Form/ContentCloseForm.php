<?php

namespace Drupal\content_close\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * ContentCloseForm close.
 */
class ContentCloseForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_close_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_close.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_close.settings');
    $form['content_type_name'] = [
      '#type' => 'details',
      '#title' => t('Content Type List'),
      '#open' => TRUE,
    ];

    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $form['content_type_name'][$node_type->id()] = [
        '#type' => 'datetime',
        '#title' => $node_type->label(),
        '#description' => t('Set the expiry date and time'),
        '#default_value' => $config->get($node_type->id()) ? DrupalDateTime::createFromTimestamp($config->get($node_type->id())) : '',
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $this->configFactory->getEditable('content_close.settings')
        ->set($node_type->id(), ($form_state->getValue($node_type->id())) ? $form_state->getValue($node_type->id())->getTimeStamp() : '')
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
