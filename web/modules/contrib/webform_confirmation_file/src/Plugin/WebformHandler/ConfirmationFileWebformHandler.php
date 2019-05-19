<?php

namespace Drupal\webform_confirmation_file\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the contents of a file to a user after completing a webform.
 *
 * @WebformHandler(
 *   id = "confirmation_file",
 *   label = @Translation("Confirmation file"),
 *   category = @Translation("Confirmation"),
 *   description = @Translation("Streams the contents of a file after submitting a webform."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED
 * )
 */
class ConfirmationFileWebformHandler extends WebformHandlerBase {

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, MimeTypeGuesserInterface $mime_type_guesser, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->tokenManager = $token_manager;
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
      $container->get('file.mime_type.guesser.extension'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name' => '',
      'content' => '',
      'download' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();
    $summary['#settings']['mimetype'] = $this->getMimeType();
    $summary['#settings']['filesize'] = format_size($this->getFilesize());
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File settings'),
    ];
    $form['file']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name with extension'),
      '#default_value' => $this->configuration['name'],
      '#required' => TRUE,
    ];
    $form['file']['content'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('File content'),
      '#default_value' => $this->configuration['content'],
      '#required' => TRUE,
    ];
    $form['file']['download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force the user to download the file'),
      '#default_value' => $this->configuration['download'],
      '#return_value' => TRUE,
    ];

    $this->setSettingsParentsRecursively($form);
    $this->tokenManager->elementValidate($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['name'] = $form_state->getValue('name');
    $this->configuration['content'] = $form_state->getValue('content');
    $this->configuration['download'] = $form_state->getValue('download');
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $filename = $this->tokenManager->replace($this->configuration['name'], $webform_submission);
    $attachment = ($this->configuration['download'] ? 'attachment;' : '');

    $headers = [
      'Content-Length' => $this->getFilesize(),
      'Content-Type' => $this->getMimeType(),
      'Content-Disposition' => $attachment . 'filename="' . $filename . '"',
    ];

    $response = new Response($this->configuration['content'], 200, $headers);
    $form_state->setResponse($response);
  }

  /**
   * Get the mime type from the file name.
   *
   * @return string
   *   The mime type.
   */
  protected function getMimeType() {
    return $this->mimeTypeGuesser->guess($this->configuration['name']);
  }

  /**
   * Get the file size from the file content.
   *
   * @return int
   *   The file size.
   */
  protected function getFilesize() {
    return mb_strlen($this->configuration['content'], '8bit');
  }

}
