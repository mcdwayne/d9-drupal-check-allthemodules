<?php

namespace Drupal\patreon_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PatreonEntityTypeForm.
 *
 * @package Drupal\patreon_entity\Form
 */
class PatreonEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $patreon_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $patreon_entity_type->label(),
      '#description' => $this->t("Label for the Patreon entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $patreon_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\patreon_entity\Entity\PatreonEntityType::load',
      ],
      '#disabled' => !$patreon_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $patreon_entity_type = $this->entity;
    $status = $patreon_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Patreon entity type.', [
          '%label' => $patreon_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Patreon entity type.', [
          '%label' => $patreon_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($patreon_entity_type->toUrl('collection'));
  }

}
