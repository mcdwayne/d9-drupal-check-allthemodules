<?php

namespace Drupal\iots_device\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IotsDeviceTypeForm.
 */
class IotsDeviceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $iots_device_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $iots_device_type->label(),
      '#description' => $this->t("Label for the Device type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $iots_device_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\iots_device\Entity\IotsDeviceType::load',
      ],
      '#disabled' => !$iots_device_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $iots_device_type = $this->entity;
    $status = $iots_device_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Device type.', [
          '%label' => $iots_device_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Device type.', [
          '%label' => $iots_device_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($iots_device_type->toUrl('collection'));
  }

}
