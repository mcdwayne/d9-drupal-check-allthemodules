<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BulkcreateUsageFormStep2.
 *
 * Provides the form portion for the second step of
 * creating new bulkcreate usages.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateUsageFormStep2 extends ConfigEntityMultistepFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getStepForm(array $form, FormStateInterface $form_state) {

    // Determine potential target fields for the desired bulkcreation.
    $potentialTargetFields = $this->getPotentialTargetFields(
      $this->entityTypeManager->getStorage('bulkcreate_configuration')->load($this->store->get('bulkcreate_configuration')),
      $this->store->get('entity_type_id'),
      $this->store->get('bundle')
    );

    if (!empty($potentialTargetFields)) {
      $return = [
        'target_field' => [
          '#type' => 'select',
          '#title' => $this->t('Target field'),
          '#description' => $this->t('Please select the target field referencing the automatically created entities.'),
          '#empty_option' => sprintf('- %s -', $this->t('Please select')),
          '#options' => $potentialTargetFields,
          '#required' => TRUE,
          '#default_value' => !empty($this->store->get('target_field')) ? $this->store->get('target_field') : NULL,
        ],
      ];
    }
    else {
      $return = [
        'no_target_fields_available' => [
          '#type' => 'markup',
          '#markup' => $this->t('The chosen entity type/bundle combination does not feature a field capable of holding references to the bulk generated entities.'),
        ],
      ];
    }

    return $return;

  }

  /**
   * {@inheritdoc}
   */
  protected function submitStep(array $form, FormStateInterface $form_state) {

    // Get the form input the user filled in.
    $input = $form_state->getUserInput();

    // Save the step data of this step.
    $this->saveData([
      'target_field' => $input['target_field'],
    ]);

    // Set the form to redirect to the next step.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_usage.add.step3');

  }

  /**
   * {@inheritdoc}
   */
  protected function isFinalStep() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasSubmitAction() {
    return !empty($this->getPotentialTargetFields(
      $this->entityTypeManager->getStorage('bulkcreate_configuration')->load($this->store->get('bulkcreate_configuration')),
      $this->store->get('entity_type_id'),
      $this->store->get('bundle')
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_usage.add.step1');
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
   * Determines potential target fields for a given bulkconfiguration
   * within a given entity type/bundle.
   *
   * @param \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface $bulkcreateConfiguration
   *   The BulkcreateConfiguration in question.
   * @param string $entity_type_id
   *   The entity type id this BulkcreateConfiguration should get enabled on.
   * @param string $bundle
   *   The bundle of the above entity type.
   *
   * @return array
   *   An array of potential target fields capable of holding
   *   references to the bulkcreated entities.
   */
  private function getPotentialTargetFields(BulkcreateConfigurationInterface $bulkcreateConfiguration, $entity_type_id, $bundle) {
    return drupal_static(
      'potentialTargetFields',
      // Determine potential target fields for the desired bulkcreation.
      $this->administrationHelper->getApplicableTargetFields(
        $bulkcreateConfiguration,
        $entity_type_id,
        $bundle
      )
    );
  }

}
