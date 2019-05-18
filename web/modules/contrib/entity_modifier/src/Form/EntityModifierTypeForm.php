<?php

namespace Drupal\entity_modifier\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityModifierTypeForm.
 */
class EntityModifierTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_modifier_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_modifier_type->label(),
      '#description' => $this->t("Label for the Entity modifier type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_modifier_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_modifier\Entity\EntityModifierType::load',
      ],
      '#disabled' => !$entity_modifier_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_modifier_type = $this->entity;
    $status = $entity_modifier_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Entity modifier type.', [
          '%label' => $entity_modifier_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity modifier type.', [
          '%label' => $entity_modifier_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_modifier_type->toUrl('collection'));
  }

}
