<?php

namespace Drupal\permanent_entities\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PermanentEntityTypeForm.
 */
class PermanentEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $permanent_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $permanent_entity_type->label(),
      '#description' => $this->t("Label for the Permanent Entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $permanent_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\permanent_entities\Entity\PermanentEntityType::load',
      ],
      '#disabled' => !$permanent_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $permanent_entity_type = $this->entity;
    $status = $permanent_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Permanent Entity type.', [
          '%label' => $permanent_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Permanent Entity type.', [
          '%label' => $permanent_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($permanent_entity_type->toUrl('collection'));
  }

}
