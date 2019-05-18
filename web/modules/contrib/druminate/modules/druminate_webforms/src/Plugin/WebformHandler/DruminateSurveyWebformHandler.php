<?php

namespace Drupal\druminate_webforms\Plugin\WebformHandler;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\druminate\Plugin\DruminateEndpointManager;

/**
 * Submits webform submissions to Convio Surveys.
 *
 * @WebformHandler(
 *  id = "druminate_survey",
 *  label = @Translation("Druminate Survey"),
 *  category = @Translation("Druminate"),
 *  description = @Translation("Sends a form submission to a Luminate Survey."),
 *  cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *  results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *  submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DruminateSurveyWebformHandler extends WebformHandlerBase {

  /**
   * Drupal\druminate\Plugin\DruminateEndpointManager definition.
   *
   * @var \Drupal\druminate\Plugin\DruminateEndpointManager
   */
  protected $druminateEndpointManager;

  /**
   * Psr\Log\LoggerInterface definition.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, DruminateEndpointManager $plugin_manager_druminate_endpoint) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->druminateEndpointManager = $plugin_manager_druminate_endpoint;
    $this->logger = $logger_factory->get('druminate.webforms');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('plugin.manager.druminate_endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'markup',
      '#markup' => $this->t('Luminate Survey: @survey_id', ['@survey_id' => $this->configuration['survey_id']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'survey_id' => '',
      'convio_mapping' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Helper function used to build configuration form.
   *
   * @param array $form
   *   The survey config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The survey config form form_state.
   *
   * @return array
   *   The completed survey config form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_state->disableCache();
    $form_state->setCached(FALSE);

    // Build list of surveys from the api.
    /** @var \Drupal\druminate_webforms\Plugin\DruminateEndpoint\ListSurveys $listSurveys */
    $listSurveys = $this->druminateEndpointManager->createInstance('listSurveys', ['list_page_size' => 500]);
    $surveys = $listSurveys->loadData();
    $survey_options = ['' => $this->t('- Select -')];
    if (isset($surveys->listSurveysResponse) && isset($surveys->listSurveysResponse->surveys)) {
      foreach ($surveys->listSurveysResponse->surveys as $survey) {
        $survey_options[$survey->surveyId] = $survey->surveyName;
      }
    }
    else {
      drupal_set_message($this->t('There are no fields attached to this survey'), 'warning');
    }

    $form['survey'] = [
      '#title' => $this->t('Send to'),
      '#type' => 'fieldset',
    ];

    $form['survey']['survey_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Survey Id'),
      '#required' => TRUE,
      '#options' => $survey_options,
      '#default_value' => isset($this->configuration['survey_id']) ? $this->configuration['survey_id'] : '',
      '#ajax' => [
        'callback' => [$this, 'convioMappingCallback'],
        'wrapper' => 'convio-mapping',
      ],
    ];

    $form['survey']['convio_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field Mapping'),
      '#attributes' => ['id' => 'convio-mapping'],
      '#tree' => TRUE,
    ];

    $survey_value = $form_state->getValue(['survey', 'survey_id']);
    $survey_id = isset($survey_value) ? $survey_value : $this->configuration['survey_id'];
    if (!empty($survey_id)) {

      /** @var \Drupal\druminate_webforms\Plugin\DruminateEndpoint\GetSurvey $survey */
      $survey = $this->druminateEndpointManager->createInstance('getSurvey', ['survey_id' => $survey_id]);
      $fields = $survey->loadData();

      if (isset($fields->getSurveyResponse) && isset($fields->getSurveyResponse->survey) && isset($fields->getSurveyResponse->survey->surveyQuestions)) {
        $survey_fields = $fields->getSurveyResponse->survey->surveyQuestions;

        // API returns a single object if just there is just one field for the
        // selected survey.
        if (!is_array($survey_fields)) {
          $this->buildFieldMapping($form, $survey_fields);
        }
        else {
          foreach ($survey_fields as $survey_field) {
            $this->buildFieldMapping($form, $survey_field);
          }
        }

      }
    }
    else {
      $form['survey']['convio_mapping']['description'] = [
        '#markup' => $this->t('Please select a survey in order to view mappings.'),
      ];
    }

    return $form;
  }

  /**
   * Helper function used to "flatten" recursive mappings for nested elements.
   *
   * @param array $element
   *   The individual form element.
   * @param array $webform_elements_options
   *   Array of options that contains webform elements.
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
   * @param object $survey_field
   *   The individual survey field returned from the api.
   */
  protected function buildFieldMapping(array &$form, $survey_field) {
    // Create options array from list of existing webform elements.
    $webform_elements_options = [];
    $webform_elements = $this->getWebform()->getElementsInitialized();
    foreach ($webform_elements as $element) {
      $this->recursiveMapping($element, $webform_elements_options);
    }

    // If this is a ConsQuestion field then loop through contactInfoField
    // to find fields.
    if ($survey_field->questionType == 'ConsQuestion') {
      if (isset($survey_field->questionTypeData) &&
        isset($survey_field->questionTypeData->consRegInfoData) &&
        isset($survey_field->questionTypeData->consRegInfoData->contactInfoField)) {

        if (is_array($survey_field->questionTypeData->consRegInfoData->contactInfoField)) {
          foreach ($survey_field->questionTypeData->consRegInfoData->contactInfoField as $field) {
            $form['survey']['convio_mapping'][$field->fieldName] = [
              '#type' => 'select',
              '#title' => $field->fieldName,
              '#options' => $webform_elements_options,
              '#empty_option' => t('- None -'),
              '#default_value' => isset($this->configuration['convio_mapping'][$field->fieldName]) ? $this->configuration['convio_mapping'][$field->fieldName] : '',
            ];
          }
        }
        else {
          $field_name = $survey_field->questionTypeData->consRegInfoData->contactInfoField->fieldName;
          $form['survey']['convio_mapping'][$field_name] = [
            '#type' => 'select',
            '#title' => $field_name,
            '#options' => $webform_elements_options,
            '#empty_option' => t('- None -'),
            '#default_value' => isset($this->configuration['convio_mapping'][$field_name]) ? $this->configuration['convio_mapping'][$field_name] : '',
          ];
        }

      }
    }
    else {
      $form['survey']['convio_mapping']['question_' . $survey_field->questionId] = [
        '#type' => 'select',
        '#title' => $survey_field->questionText,
        '#options' => $webform_elements_options,
        '#empty_option' => t('- None -'),
        '#default_value' => isset($this->configuration['convio_mapping']['question_' . $survey_field->questionId]) ? $this->configuration['convio_mapping']['question_' . $survey_field->questionId] : '',

      ];
    }
  }

  /**
   * Callback for the survey_id select field.
   */
  public function convioMappingCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['settings']['survey']['convio_mapping'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // $form_state->getValues() doesn't give me the ajax fields.
    $values = $form_state->getUserInput();
    if (!empty($values['settings']['survey'])) {
      foreach ($this->configuration as $name => $value) {
        if (!empty($values['settings']['survey'][$name])) {
          $this->configuration[$name] = $values['settings']['survey'][$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {

    $submissions = $webform_submission->getData();

    $convio_params = [];
    if (isset($this->configuration['survey_id'])) {
      $convio_params['survey_id'] = $this->configuration['survey_id'];
    }
    else {
      $message = $this->t('Survey could not be submitted. Missing survey_id');
      drupal_set_message($message, 'error');
      $this->logger->error($message);
      return;
    }

    // Add additional survey params.
    if (isset($this->configuration['convio_mapping']) && is_array($this->configuration['convio_mapping'])) {
      $mapping = array_filter($this->configuration['convio_mapping']);
      foreach ($mapping as $key => $value) {

        // Find out if the mapping comes from a composite element and grab the
        // correct value.
        if (strpos($value, ':') !== FALSE) {
          $parts = explode(':', $value);
          if (is_array($parts) && isset($parts[0]) && isset($parts[1]) && isset($submissions[$parts[0]][$parts[1]])) {
            $convio_params[$key] = $submissions[$parts[0]][$parts[1]];
          }
          else {
            $convio_params[$key] = $submissions[$value];
          }
        }
        else {
          $convio_params[$key] = $submissions[$value];
        }
      }
    }
    else {
      $message = $this->t('Survey: @survey_id could not be submitted. Missing additional params.', ['@survey_id' => $this->configuration['survey_id']]);
      drupal_set_message($message, 'error');
      $this->logger->error($message);
      return;
    }

    /** @var \Drupal\druminate_webforms\Plugin\DruminateEndpoint\SubmitSurvey $survey */
    $survey = $this->druminateEndpointManager->createInstance('submitSurvey', $convio_params);
    $surveyResult = $survey->postData();

    if (isset($surveyResult->submitSurveyResponse) && isset($surveyResult->submitSurveyResponse->success) && $surveyResult->submitSurveyResponse->success != 'false') {
      $this->logger->info($this->t('Survey: @survey_id was submitted successfully.', ['@survey_id' => $this->configuration['survey_id']]));
    }
    else {
      if (isset($surveyResult->submitSurveyResponse->errors->errorMessage) && isset($surveyResult->submitSurveyResponse->errors->errorField)) {
        $errorField = Html::escape($surveyResult->submitSurveyResponse->errors->errorField);
        $errorMessage = Html::escape($surveyResult->submitSurveyResponse->errors->errorMessage);

        $message = t('@field: @message', [
          '@field' => $errorField,
          '@message' => $errorMessage,
        ]);
      }
      elseif (isset($surveyResult->submitSurveyResponse->errorResponse->message)) {
        $message = Html::escape($surveyResult->submitSurveyResponse->errorResponse->message);
      }
      elseif (isset($surveyResult->errorResponse->message)) {
        $message = Html::escape($surveyResult->errorResponse->message);
      }
      else {
        $message = t('Convio Survey submission failed');
      }
      drupal_set_message($message, 'error');
      $this->logger->error($message);
    }
  }

}
