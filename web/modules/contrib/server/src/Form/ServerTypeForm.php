<?php

namespace Drupal\server\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ServerTypeForm.
 */
class ServerTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $server_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $server_type->label(),
      '#description' => $this->t("Label for the Server type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $server_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\server\Entity\ServerType::load',
      ],
      '#disabled' => !$server_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $server_type = $this->entity;
    $status = $server_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Server type.', [
          '%label' => $server_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Server type.', [
          '%label' => $server_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($server_type->toUrl('collection'));
  }

}
