<?php

namespace Drupal\field_nif\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'field_nif' element.
 *
 * @WebformElement(
 *   id = "field_nif",
 *   label = @Translation("NIF/NIE/CIF"),
 *   description = @Translation("Provides an element to store Spanish administrative numbers."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Nif extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      // Title.
      'title' => '',
      'default_value' => '',

      // Supported types.
      'supported_types' => [
        'nif',
        'nie',
        'cif',
      ],

      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'disabled' => FALSE,

      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'unique_error' => '',

      // Attributes.
      'wrapper_attributes' => [],
      'attributes' => [],

      // Conditional logic.
      'states' => [],

      // Element access.
      'access_create_roles' => ['anonymous', 'authenticated'],
      'access_create_users' => [],
      'access_update_roles' => ['anonymous', 'authenticated'],
      'access_update_users' => [],
      'access_view_roles' => ['anonymous', 'authenticated'],
      'access_view_users' => [],
    ];

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#type'] = 'nif';
    $element['#element_validate'][] = ['\Drupal\field_nif\Element\Nif', 'validateNif'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['supported_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Supported document types'),
      '#parents' => ['properties', 'supported_types'],
      '#default_value' => $form_state->get('custom_properties')['supported_types'],
      '#options' => [
        'nif' => $this->t('NIF'),
        'cif' => $this->t('CIF'),
        'nie' => $this->t('NIE'),
      ],
      '#required' => TRUE,
      // Add after all fieldset elements, which have a weight of -20.
      /** @see \Drupal\webform\Plugin\WebformElementBase::buildConfigurationForm */
      '#weight' => -10,
    ];

    // Hide maxlength.
    $form['form']['maxlength'] = [
      '#type' => 'value',
      '#value' => 10,
    ];

    return $form;
  }


}
