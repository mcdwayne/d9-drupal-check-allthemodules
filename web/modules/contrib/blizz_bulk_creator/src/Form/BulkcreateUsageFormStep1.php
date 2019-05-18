<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BulkcreateUsageFormStep1.
 *
 * Provides the basic config-entity edit form for bulkcreate usages.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateUsageFormStep1 extends ConfigEntityMultistepFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getStepForm(array $form, FormStateInterface $form_state) {

    return [
      'bulkcreate_configuration' => [
        '#type' => 'select',
        '#title' => $this->t('Bulkcreate configuration'),
        '#description' => $this->t('Please select the bulkcreate configuration you wish to use.'),
        '#empty_option' => sprintf('- %s -', $this->t('Please select')),
        '#options' => $this->administrationHelper->getBulkcreateConfigurationOptions(),
        '#required' => TRUE,
        '#default_value' => !empty($this->store->get('bulkcreate_configuration')) ? $this->store->get('bulkcreate_configuration') : NULL,
      ],
      'entity_type_id' => [
        '#type' => 'select',
        '#title' => $this->t('Select the target entity type'),
        '#description' => $this->t('Select the entity on which a bulkcreation should be enabled.'),
        '#required' => TRUE,
        '#empty_option' => sprintf('- %s -', $this->t('Please select')),
        '#options' => $this->entityHelper->getEntityTypeOptions(),
        '#ajax' => [
          'callback' => [$this, 'entityTypeSelected'],
          'wrapper' => 'bundle_select-wrapper',
        ],
        '#states' => [
          'invisible' => [
            'select[name="bulkcreate_configuration"]' => ['value' => ''],
          ],
        ],
      ],
      'bundle_select' => $this->getAjaxWrapperElement('bundle_select'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function submitStep(array $form, FormStateInterface $form_state) {

    // Get the user input. $form_state->getValue() is not used
    // by intent, since it does not feature the bundle
    // variable which gets inserted into the form by ajax.
    $input = $form_state->getUserInput();

    // Save the step data of this step.
    $this->saveData([
      'bulkcreate_configuration' => $input['bulkcreate_configuration'],
      'entity_type_id' => $input['entity_type_id'],
      'bundle' => $input['bundle'],
    ]);

    // Set the form to redirect to the next step.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_usage.add.step2');

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
    return TRUE;
  }

  /**
   * Returns additional form elements after selecting the desired entity type.
   *
   * @param array $form
   *   The form in it's current state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface holding the values.
   *
   * @return array
   *   The form elements to insert into the form.
   */
  public function entityTypeSelected(array &$form, FormStateInterface $form_state) {

    // Determine the triggering element for this callback.
    $select = $form_state->getTriggeringElement();

    // Determine which entity type has been selected.
    $entity_type_id = $select['#value'] ?: FALSE;

    // Prepare the form element to insert (an empty placeholder).
    $element['bundle_select'] = $this->getAjaxWrapperElement('bundle_select');

    // Only if an entity type is selected, we are
    // able to provide further elements.
    if ($entity_type_id !== FALSE) {

      // Get all available options for this entity type id.
      $selectable_options = $this->entityHelper->getEntityBundleOptions($entity_type_id);

      // See if there is already another usage pointing to this
      // configuration/entity_type_id combination.
      $existing_usages = $this->entityTypeManager->getStorage('bulkcreate_usage')->loadByProperties([
        'bulkcreate_configuration' => $form_state->getValue('bulkcreate_configuration'),
        'entity_type_id' => $entity_type_id,
      ]);

      // If there are existing usages, filter out related options to
      // prevent enabling the same configurations multiple times on entities.
      if (!empty($existing_usages)) {
        foreach ($existing_usages as $usage) {
          array_splice(
            $selectable_options,
            array_search($usage->get('bundle'), array_keys($selectable_options)),
            1
          );
        }
      }

      // Prepare a container to hold the additional form elements.
      $element['bundle_select']['bundle'] = [
        '#name' => 'bundle',
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Please select the bundle, on which a bulkcreation should be activated.'),
        '#required' => TRUE,
        '#options' => $selectable_options,
        '#states' => [
          'invisible' => [
            'select[name="bulkcreate_configuration"]' => ['value' => ''],
          ],
        ],
      ];

    }

    // Return the form portion to insert.
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_usage.list');
  }

}
