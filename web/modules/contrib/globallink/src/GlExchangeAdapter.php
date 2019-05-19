<?php

namespace Drupal\globallink;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides an interface to the provided library.
 */
class GlExchangeAdapter {

  /**
   * Max completed translations per project to return on api calls.
   */
  const COMPLETED_BY_PROJECT_MAX_RESULT = 500;

  /**
   * The translator.
   *
   * @var \Drupal\tmgmt\TranslatorInterface|null
   */
  protected $translator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * GlExchangeAdapter constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler_interface) {
    $this->entityTypeManager = $entity_type_manager;
    // @todo Handle multiple translators of the same type.
    $this->translator = $this->entityTypeManager->getStorage('tmgmt_translator')->load('globallink');
    $this->moduleHandler = $module_handler_interface;
  }

  /**
   * Get translator.
   *
   * @return \Drupal\tmgmt\TranslatorInterface|null
   *   The loaded translator if successfully loaded, otherwise null.
   */
  public function getTranslator() {
    return $this->translator;
  }

  /**
   * Get default settings.
   *
   * @return array
   *   Array of default settings.
   */
  public function getDefaultSettings() {
    return [
      'pd_url' => '',
      'pd_username' => '',
      'pd_password' => '',
      'pd_projectid' => '',
      'pd_submissionprefix' => '',
      'pd_classifier' => '',
      'pd_agent' => 'Drupal 8',
    ];
  }

  /**
   * Get the PDConfig object.
   *
   * @param array $settings
   *   Array of settings. Defaults to plugin settings. See getDefaultSettings
   *   for info about the expected structure.
   *
   * @return \PDConfig
   *   PDConfig object with settings.
   */
  public function getPDConfig($settings) {

    $settings += $this->getDefaultSettings();
    $pd_config = new \PDConfig();
    $url = strrev($settings['pd_url']);
    if (ord($url) == 47) {
      $url = substr($url, 1);
    }
    $pd_config->url = strrev($url);
    $pd_config->username = $settings['pd_username'];
    $pd_config->password = $settings['pd_password'];
    $pd_config->userAgent = $settings['pd_agent'];

    return $pd_config;
  }

  /**
   * Get the GLExchange object.
   *
   * @param \PDConfig $pd_config
   *   The PDConfig object.
   *
   * @return \GLExchange
   *   The initiated object.
   */
  public function getGlExchange(\PDConfig $pd_config) {
    return new \GLExchange($pd_config);
  }

  /**
   * Get the PDDocument object.
   *
   * @param array $parameters
   *   Array of parameters, where the expected structure is:
   *    - name: (string) the name for this document,
   *    - source_language: (string) source language,
   *    - target_languages: (array) list of target languages,
   *    - data': xliff data
   *
   * @return \PDDocument
   *   The pdd document object.
   */
  public function getPdDocument($parameters) {

    $document = new \PDDocument();
    $document->fileformat = $parameters['classifier'];
    $document->name = $parameters['name'];
    $document->sourceLanguage = $parameters['source_language'];
    $document->targetLanguages = $parameters['target_languages'];
    $document->data = $parameters['data'];
    $document->clientIdentifier = $parameters['client_identifier'];

    return $document;
  }

  /**
   * Get the PDSubmission object.
   *
   * @param \PDProject $project
   *   The project object.
   * @param array $parameters
   *   Array of parameters, where the expected structure is:
   *    - name: (string) the name for this submission,
   *    - urgent: (bool) whether or not it's an urgent job,
   *    - comment: (string) additional notes for the job,
   *    - due': (string) unix timestamp
   *
   * @return \PDSubmission
   */
  public function getSubmission(\PDProject $project, $parameters) {

    $submission = new \PDSubmission();
    $submission->name = $parameters['name'];
    $submission->submitter = $parameters['submitter'];
    $submission->isUrgent = $parameters['urgent'];
    $submission->instructions = $parameters['comment'];
    $submission->dueDate = $parameters['due'];
    $submission->project = $project;

    return $submission;
  }
}
