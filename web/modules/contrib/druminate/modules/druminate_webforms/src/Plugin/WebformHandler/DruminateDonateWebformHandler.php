<?php

namespace Drupal\druminate_webforms\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Submits webform submissions to Convio Surveys.
 *
 * @WebformHandler(
 *  id = "druminate_donation",
 *  label = @Translation("Druminate Donation"),
 *  category = @Translation("Druminate"),
 *  description = @Translation("Sends a form submission to a Luminate Donation Form."),
 *  cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *  results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *  submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DruminateDonateWebformHandler extends DruminateSurveyWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'markup',
      '#markup' => $this->t('Luminate Donation Form: @df_id', ['@df_id' => $this->configuration['df_id']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'df_id' => '',
      'convio_mapping' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_state->disableCache();
    $form_state->setCached(FALSE);

    $form['donation'] = [
      '#title' => $this->t('Send to'),
      '#type' => 'fieldset',
    ];

    $form['donation']['df_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donation Form Id'),
      '#required' => TRUE,
      '#default_value' => isset($this->configuration['df_id']) ? $this->configuration['df_id'] : '',
      '#ajax' => [
        'callback' => [$this, 'convioMappingCallback'],
        'wrapper' => 'convio-mapping',
      ],
    ];

    $form['donation']['convio_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field Mapping'),
      '#attributes' => ['id' => 'convio-mapping'],
      '#tree' => TRUE,
    ];

    $donationIdValue = $form_state->getValue(['donation', 'df_id']);
    $donationId = isset($donationIdValue) ? $donationIdValue : $this->configuration['df_id'];
    if (!empty($donationId)) {

      /** @var \Drupal\druminate_webforms\Plugin\DruminateEndpoint\GetDonation $donationForm */
      $donationForm = $this->druminateEndpointManager->createInstance('getDonationForm', ['form_id' => $donationId]);
      $donationFormResult = $donationForm->loadData();
      if (!empty($donationFormResult->getDonationFormInfoResponse->donationFields->donationField)) {
        $donationFields = $donationFormResult->getDonationFormInfoResponse->donationFields->donationField;

        // Add other amount and recurring fields manually.
        $donationFields[] = (object) ['elementName' => 'extproc'];
        $donationFields[] = (object) ['elementName' => 'other_amount'];
        $donationFields[] = (object) ['elementName' => 'level_autorepeat'];

        $donationFields[] = (object) ['elementName' => 'card_number'];
        $donationFields[] = (object) ['elementName' => 'card_exp_date_year'];
        $donationFields[] = (object) ['elementName' => 'card_exp_date_month'];
        $donationFields[] = (object) ['elementName' => 'card_cvv'];
        $donationFields[] = (object) ['elementName' => 'tribute'];

        // Allow developers to add additional donation fields.
        \Drupal::moduleHandler()->alter('druminate_webforms_donation_fields', $donationFields);

        foreach ($donationFields as $field) {
          if ($field->elementName != 'form_id') {
            $this->buildFieldMapping($form, $field);
          }
        }
      }
    }
    else {
      $form['donation']['convio_mapping']['description'] = [
        '#markup' => $this->t('Please enter a valid "Donation Form Id" in order to view mappings.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function recursiveMapping(array $element, array &$webform_elements_options) {
    $isContainer = FALSE;
    foreach ($element as $key => $partial) {
      // Container elements are the only elements in the array that do not begin
      // with "#".
      if (preg_match('/^[^#]/', $key)) {
        $isContainer = TRUE;
        $this->recursiveMapping($partial, $webform_elements_options);
      }
    }

    // If element does not have a title its not a true form element and should
    // not be added to the listing.
    if (!$isContainer && isset($element['#title'])) {
      // Flatten the array keys to that it matches the submission structure.
      if (!empty($element['#webform_composite_elements'])) {
        // Composite Elements.
        foreach ($element['#webform_composite_elements'] as $subKey => $subElement) {
          // Only allow elements that are enabled.
          if (!isset($subElement['#access'])) {
            $subElementParents = $element['#webform_parents'];
            $subElementParents[] = $subKey;

            $subElementParentLabel = implode(' / ', $subElementParents);
            $webform_elements_options[$element['#webform_key'] . ':' . $subKey] = $subElementParentLabel;
          }
        }
      }
      else {
        // TODO: Hierachical selects and option groups. There has to be a better
        // way of doing this.
        $parents = $element['#webform_parents'];
        $index = array_pop($parents);
        $parentLabel = implode(' / ', $element['#webform_parents']);
        $webform_elements_options[$index] = $parentLabel;
      }
    }
  }

  /**
   * Helper function used to map webform elements to convio survey fields.
   *
   * @param array $form
   *   The form array.
   * @param object $field
   *   The individual survey field returned from the api.
   */
  protected function buildFieldMapping(array &$form, $field) {
    // Create options array from list of existing webform elements.
    $webform_elements_options = [];
    $webform_elements = $this->getWebform()->getElementsInitialized();
    foreach ($webform_elements as $element) {
      $this->recursiveMapping($element, $webform_elements_options);
    }

    $fieldKey = str_replace('.', '-', $field->elementName);
    $form['donation']['convio_mapping'][$fieldKey] = [
      '#type' => 'select',
      '#title' => $field->elementName,
      '#options' => $webform_elements_options,
      '#empty_option' => t('- None -'),
      '#default_value' => isset($this->configuration['convio_mapping'][$fieldKey]) ? $this->configuration['convio_mapping'][$fieldKey] : '',
    ];
  }

  /**
   * Callback for the survey_id select field.
   */
  public function convioMappingCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['settings']['donation']['convio_mapping'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // $form_state->getValues() doesn't give me the ajax fields.
    $values = $form_state->getUserInput();
    if (!empty($values['settings']['donation'])) {
      foreach ($this->configuration as $name => $value) {
        if (!empty($values['settings']['donation'][$name])) {
          $this->configuration[$name] = $values['settings']['donation'][$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {}

}
