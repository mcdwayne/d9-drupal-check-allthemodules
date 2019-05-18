<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BulkcreateConfigurationFormStep1.
 *
 * Provides the form portion for the first step of
 * creating a new bulkcreate configuration.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateConfigurationFormStep1 extends ConfigEntityMultistepFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getStepForm(array $form, FormStateInterface $form_state) {

    return [
      '#tree' => TRUE,
      'admin_title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Administrative title'),
        '#description' => $this->t('This title is only used to identify this bulkcreation configuration and has no functional impact.'),
        '#required' => TRUE,
      ],
      'custom_entity_name' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Custom naming'),
        '#description' => $this->t('Do you want to provide a custom name for the bulk-created entities?'),
      ],
      'bundle' => [
        '#type' => 'select',
        '#title' => $this->t('Target bundle'),
        '#description' => $this->t('Please select the media entity target bundle when using this bulkcreation configuration.'),
        '#required' => TRUE,
        '#empty_option' => sprintf('- %s -', $this->t('Please select')),
        '#options' => $this->entityHelper->getEntityBundleOptions('media'),
        '#ajax' => [
          'callback' => [$this, 'bundleSelected'],
          'wrapper' => 'bundle_select-wrapper',
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
    // by intent, since it does not feature the bulkcreate_field
    // variable which gets inserted into the form by ajax.
    $input = $form_state->getUserInput();

    // Save the step data of this step.
    $this->saveData([
      'admin_title' => $input['admin_title'],
      'custom_entity_name' => $input['custom_entity_name'],
      'bundle' => $input['bundle'],
      'bulkcreate_field' => $input['bulkcreate_field'],
    ]);

    // Set the form to redirect to the next step.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_configuration.add.step2');

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
   * {@inheritdoc}
   */
  protected function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_configuration.list');
  }

  /**
   * Returns additional form elements after selecting the desired bundle.
   *
   * @param array $form
   *   The form in it's current state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface holding the values.
   *
   * @return array
   *   The form elements to insert into the form.
   */
  public function bundleSelected(array &$form, FormStateInterface $form_state) {

    // Determine the triggering element for this callback.
    $select = $form_state->getTriggeringElement();

    // Determine which entity type has been selected.
    $bundle = $select['#value'] ?: FALSE;

    // Prepare the form element to insert (an empty placeholder).
    $element['bundle_select'] = $this->getAjaxWrapperElement('bundle_select');

    // Only if an entity type is selected, we are
    // able to provide further elements.
    if ($bundle !== FALSE) {

      // Prepare a container to hold the additional form elements.
      $element['bundle_select']['bulkcreate_field'] = [
        '#name' => 'bulkcreate_field',
        '#type' => 'select',
        '#title' => $this->t('Bulkcreation field'),
        '#description' => $this->t('Please select the field that is unique to each media entity of the selected bundle.'),
        '#required' => TRUE,
        '#options' => $this->entityHelper->getBundleFieldOptions('media', $bundle),
      ];

    }

    // Return the form portion to insert.
    return $element;
  }

}
