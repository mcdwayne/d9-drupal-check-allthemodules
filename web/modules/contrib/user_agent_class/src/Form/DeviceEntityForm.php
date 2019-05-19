<?php

namespace Drupal\user_agent_class\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DeviceEntityForm.
 */
class DeviceEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $device_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger in header'),
      '#maxlength' => 255,
      '#default_value' => $device_entity->label(),
      '#description' => $this->t("Label for the Device."),
      '#required' => TRUE,
    ];

    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class in tag body'),
      '#maxlength' => 255,
      '#default_value' => $device_entity->getClassName(),
      '#description' => $this->t("Class for the User agent in body."),
      '#required' => TRUE,
    ];

    $form['enableCheck'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to check'),
      '#default_value' => $device_entity->getEnableCheck(),
      '#required' => FALSE,
    ];

    $form['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude'),
      '#maxlength' => 255,
      '#default_value' => $device_entity->getExclude(),
      '#description' => $this->t("Exclude trigger phrase from User-Agent"),
      '#required' => FALSE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $device_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_agent_class\Entity\DeviceEntity::load',
      ],
      '#disabled' => !$device_entity->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $device_entity = $this->entity;
    $status = $device_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Device.', [
          '%label' => $device_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Device.', [
          '%label' => $device_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($device_entity->toUrl('collection'));
  }

}
