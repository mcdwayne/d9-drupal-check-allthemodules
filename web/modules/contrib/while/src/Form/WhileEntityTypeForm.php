<?php

namespace Drupal\white_label_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WhileEntityTypeForm.
 *
 * @package Drupal\white_label_entity\Form
 */
class WhileEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $while_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $while_entity_type->label(),
      '#description' => $this->t("Label for the @entity_type_name.", ['@entity_type_name' => $while_entity_type->getEntityType()->getSingularLabel()]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $while_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\white_label_entity\Entity\WhileEntityType::load',
      ],
      '#disabled' => !$while_entity_type->isNew(),
    ];

    $form['entity_pages_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide entity pages'),
      '#default_value' => $while_entity_type->getEntityPagesActive(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $while_entity_type = $this->entity;

    $status = $while_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label @entity_type_name.', [
          '%label' => $while_entity_type->label(),
          '@entity_type_name' => $while_entity_type->getEntityType()->getSingularLabel(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label @entity_type_name.', [
          '%label' => $while_entity_type->label(),
          '@entity_type_name' => $while_entity_type->getEntityType()->getSingularLabel(),
        ]));
    }
    $form_state->setRedirectUrl($while_entity_type->toUrl('collection'));
  }

}
