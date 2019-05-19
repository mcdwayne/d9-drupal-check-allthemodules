<?php

/**
 * @file
 * Contains \Drupal\temporal\Form\TemporalForm.
 */

namespace Drupal\temporal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Temporal edit forms.
 *
 * @ingroup temporal
 */
class TemporalForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\temporal\Entity\Temporal */
    $entity = $this->entity;
    $delta = $entity->getDelta();
    $status = $entity->getStatus();
    $future = $entity->getFuture();

    /* @var $bundle_entity_type \Drupal\temporal\Entity\TemporalType */
    $bundle_entity_type = $entity->type->entity;
    $entity_type = $bundle_entity_type->getTemporalEntityType();
    $entity_bundle = $bundle_entity_type->getTemporalEntityBundle();
    $entity_field_type = $bundle_entity_type->getTemporalEntityFieldType();
    $entity_field = $bundle_entity_type->getTemporalEntityField();
    $form = parent::buildForm($form, $form_state);

    // Prepopulate the fields we can, and disable them as there is no need to change them thorugh this form
    // Custom temporal entities should be create via service call
    if($bundle_entity_type) {
      $form['entity_type']['widget'][0]['value']['#default_value'] = $entity_type;
      $form['entity_type']['#disabled'] = true;
      $form['entity_bundle']['widget'][0]['value']['#default_value'] = $entity_bundle;
      $form['entity_bundle']['#disabled'] = true;
      $form['entity_field_type']['widget'][0]['value']['#default_value'] = $entity_field_type;
      $form['entity_field_type']['#disabled'] = true;
      $form['entity_field']['widget'][0]['value']['#default_value'] = $entity_field;
      $form['entity_field']['#disabled'] = true;

      if($entity->getEntityId()) {
        $referenced_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity->getEntityId());
      }
      else {
        $referenced_entity = NULL;
      }

      // Handle delta
      $form['delta'] = array(
        '#type' => 'weight',
        '#delta' => 10,
        '#title' => 'Delta',
        '#default_value' => ($delta != NULL ? $delta : 0),
        '#disabled' => false,
        '#weight' => -50,
      );

      // Handle status field
      $form['status'] = array(
        '#type' => 'checkbox',
        '#title' => 'Status',
        '#default_value' => ($status != NULL ? $status : 1),
        '#weight' => -40,
      );

      // Handle future state
      $form['future'] = array(
        '#type' => 'checkbox',
        '#title' => 'Future value',
        '#default_value' => ($future != NULL ? $future : 1),
        '#weight' => -30,
      );

      if($entity_type == 'user') {
        $selection_settings = [
          'include_anonymous' => FALSE,
        ];
      }
      else {
        // Force the entity list to match the original bundle so that field assignments always match
        $selection_settings = [
          'target_bundles' => array($entity_bundle)
        ];
      }
      // This field isn't showing by default due to config in Temporal.php
      // Which makes it easier to apply a custom autocomplete depending on the entity type
      $form['entity_id'] = array(
        '#title' => 'Entity ID',
        '#description' => 'The '.$entity_type.':'.$entity_bundle.' the temporal entry references',
        '#type' => 'entity_autocomplete',
        '#target_type' => $entity_type,
        '#default_value' => $referenced_entity,
        '#selection_settings' => $selection_settings,
        '#required' => true,
        '#weight' => -100,
      );
    }

    // Handle different data input types for the field value (Boolean/Integer/Decimal/String
    // TODO: Field type override
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Temporal.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Temporal.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.temporal.canonical', ['temporal' => $entity->id()]);
  }

}
