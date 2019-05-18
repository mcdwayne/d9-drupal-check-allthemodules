<?php

namespace Drupal\phones_contact\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhonesContactTypeForm.
 */
class PhonesContactTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $phones_contact_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $phones_contact_type->label(),
      '#description' => $this->t("Label for the Phones contact type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $phones_contact_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\phones_contact\Entity\PhonesContactType::load',
      ],
      '#disabled' => !$phones_contact_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $phones_contact_type = $this->entity;
    $status = $phones_contact_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Phones contact type.', [
          '%label' => $phones_contact_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Phones contact type.', [
          '%label' => $phones_contact_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($phones_contact_type->toUrl('collection'));
  }

}
