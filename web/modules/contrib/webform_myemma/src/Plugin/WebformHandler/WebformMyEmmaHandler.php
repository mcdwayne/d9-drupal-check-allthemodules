<?php

namespace Drupal\webform_myemma\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use MarkRoland\Emma\Client;

/**
 * Form submission to MyEmma handler.
 *
 * @WebformHandler(
 *   id = "myemma",
 *   label = @Translation("MyEmma"),
 *   category = @Translation("MyEmma"),
 *   description = @Translation("Sends a form submission to a MyEmma group."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformMyEmmaHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $tokenManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $tokenManager;
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
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'email' => '',
      'group_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['myemma'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MyEmma settings'),
    ];
    $form['myemma']['group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group Id'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['group_id'],
      '#description' => $this->t('Group ID of the list to sign them up for.'),
      '#size' => 60,
      '#maxlength' => 128,
    ];

    $fields = $this->getWebform()->getElementsDecoded();
    $options = [];
    $options[''] = $this->t('- Select an option -');
    foreach ($fields as $field_name => $field) {
      if ($field['#type'] == 'email') {
        $options[$field_name] = $field['#title'];
      }
    }

    $form['myemma']['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Email field'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['email'],
      '#options' => $options,
    ];

    $form['myemma']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values['myemma'][$name])) {
        $this->configuration[$name] = $values['myemma'][$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // If update, do nothing.
    if ($update) {
      return;
    }

    $fields = $webform_submission->toArray(TRUE);

    // Replace tokens.
    $configuration = $this->tokenManager->replace($this->configuration, $webform_submission);

    $email = $fields['data'][$configuration['email']];
    $fields = [];

    // Get account info.
    $emma_account = $this->config('webform_myemma.settings');

    try {
      $emmaClient = new Client($emma_account->get('account_id'), $emma_account->get('public_key'), $emma_account->get('private_key'));

      $response = $emmaClient->import_single_member($email, $fields, [$configuration['group_id']]);

      if (!$response) {
        $this->logger('webform_myemma')->error('MyEmma settings are incorrect, set at /admin/config/services/webform_myemma and confirm the Group ID is correct in webform handler');
      }
    }
    catch (\Exception $e) {
      $this->logger('webform_myemma')->error('MyEmma settings not set, set at /admin/config/services/webform_myemma');
    }
  }

}
