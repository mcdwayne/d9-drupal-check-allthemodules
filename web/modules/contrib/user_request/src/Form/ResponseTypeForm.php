<?php

namespace Drupal\user_request\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for response types.
 */
class ResponseTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exist' => '\Drupal\user_request\Entity\ResponseType::load',
      ],
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
    ];

    return $this->protectBundleIdElement($form);
  }

}
