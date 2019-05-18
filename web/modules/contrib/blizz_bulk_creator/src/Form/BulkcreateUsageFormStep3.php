<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BulkcreateUsageFormStep3.
 *
 * Provides the form portion for the third step of
 * creating new bulkcreate usages.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateUsageFormStep3 extends ConfigEntityMultistepFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getStepForm(array $form, FormStateInterface $form_state) {

    // Extract the stages, that can hold more than a single value.
    $multiValueStages = $this->getMultiStages();

    // Prepare a form element.
    if (!empty($multiValueStages)) {
      $form['multi_stage'] = [
        '#type' => 'radios',
        '#title' => $this->t('Multi-instantiated field'),
        '#description' => $this->t('Please select the field that will get multi-instantiated for every given value of the bulkcreation (Hint: only fields that can hold more than a single value are presented here for selection).'),
        '#options' => $multiValueStages,
        '#required' => TRUE,
      ];
    }
    else {
      $form['multi_stage'] = [
        '#type' => 'markup',
        '#markup' => $this->t('In the target definition chosen is no field included that can hold more than a single value. Please return to the previous screen and select a different target field.'),
      ];
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function submitStep(array $form, FormStateInterface $form_state) {

    // Shortcut to the configuration entity as well as the type hint.
    /* @var \Drupal\blizz_bulk_creator\Entity\BulkcreateUsage $entity */
    $entity = $this->entity;

    // Get the form input the user filled in.
    $input = $form_state->getUserInput();

    // Load the referenced bulkcreate configuration.
    $bulkcreate_configuration = $this->entityTypeManager->getStorage('bulkcreate_configuration')->load($this->store->get('bulkcreate_configuration'));

    // Get human readable label names.
    $entity_type_label = $this->entityHelper->getEntityTypeOptions()[$this->store->get('entity_type_id')];
    $bundle_label = $this->entityHelper->getEntityBundleOptions($this->store->get('entity_type_id'))[$this->store->get('bundle')];

    // Set the entity values.
    $entity->set('label', "{$entity_type_label}: {$bundle_label}");
    $entity->set('bulkcreate_configuration', $this->store->get('bulkcreate_configuration'));
    $entity->set('entity_type_id', $this->store->get('entity_type_id'));
    $entity->set('bundle', $this->store->get('bundle'));
    $entity->set('target_field', $this->store->get('target_field'));
    $entity->set('multi_stage', (int) $input['multi_stage']);

    // Generate an ID for this entity.
    $entity->set('id', "{$this->store->get('entity_type_id')}-{$this->store->get('bundle')}-{$this->store->get('bulkcreate_configuration')}");

    // Save the entity.
    $entity->save();

    // Invalidate the caches containing base field information.
    $this->cacheTagInvalidator->invalidateTags(['entity_field_info']);

    // Set a message on the frontend.
    drupal_set_message($this->t(
      'Bulkcreations of type %config were enabled on %target_entity.',
      [
        '%config' => $bulkcreate_configuration->label(),
        '%target_entity' => $entity->label(),
      ]
    ));

    // Log a notice to watchdog (or whereever).
    $this->logger->notice(
      'Bulkcreations of type %config were enabled on %target_entity by user %user.',
      [
        '%config' => $bulkcreate_configuration->label(),
        '%target_entity' => $entity->label(),
        '%user' => $this->currentUser()->getAccountName(),
      ]
    );

    // Redirect back to the list view.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_usage.list');

  }

  /**
   * {@inheritdoc}
   */
  protected function isFinalStep() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasSubmitAction() {
    return !empty($this->getMultiStages());
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_usage.add.step2');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_usage.list');
  }

  /**
   * Helper function.
   *
   * Determines the fields in the target field definition that can
   * hold more than a single value.
   *
   * @return array
   *   An array of fields keyed by their respective machine names
   *   that can hold more than a single value.
   */
  private function getMultiStages() {
    $multiValueStages = drupal_static('multiValueStages', FALSE);
    if ($multiValueStages === FALSE) {
      // Extract the target information of the current settings.
      $targetstages = $this->administrationHelper->getStructuredBulkcreateTargetFieldArray(
        $this->store->get('entity_type_id'),
        $this->store->get('bundle'),
        $this->store->get('target_field')
      );

      // Extract the stages, that can hold more than a single value.
      $multiValueStages = [];
      foreach ($targetstages as $stage) {
        if ($stage->cardinality == -1 || $stage->cardinality > 1) {
          $multiValueStages[$stage->fieldname] = $stage->fieldDefinition->label();
          if ($stage->cardinality != -1) {
            $multiValueStages[$stage->fieldname] .= sprintf(' (max. %d items)', $stage->cardinality);
          }
        }
      }
    }
    return $multiValueStages;
  }

}
