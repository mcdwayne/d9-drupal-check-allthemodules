<?php

namespace Drupal\scheduled_message\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Scheduled message plugins.
 */
abstract class ScheduledMessageBase extends PluginBase implements ScheduledMessageInterface, ContainerFactoryPluginInterface {


  protected $uuid;
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityFieldManager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
                              LoggerInterface $logger,
                              EntityTypeManager $entity_type_manager,
                              EntityFieldManagerInterface $entityFieldManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('scheduled_message'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
      'uuid' => '',
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->uuid = $configuration['uuid'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => NULL,
      'date_field' => 'created',
      'offset' => NULL,
      'state' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $messageStorage */
    $messageStorage = $this->entityTypeManager->getStorage('message_template');
    $message_type_configs = $messageStorage->loadMultiple();

    $message_types = $workflow_types = $datefield_types = [];
    foreach ($message_type_configs as $k => $type) {
      /** @var \Drupal\membership\Entity\MembershipTypeInterface $type */
      $message_types[$k] = $type->label();
    }

    $parentFields = $this->entityFieldManager->getFieldDefinitions($form['entity_type']['#value'], $form['entity_id']['#value']);

    foreach ($parentFields as $fieldName => $fieldDefinition) {
      switch ($fieldDefinition->getType()) {
        case 'state':
          // TODO: load state values for this field.
          break;

        case 'daterange':
          // Load end date as well as start.
          $datefield_types[$fieldName . '.end_value'] = $fieldDefinition->getLabel() . ' - ' . t('End date');
          // Continue to date...
        case 'datetime':
          $datefield_types[$fieldName . '.value'] = $fieldDefinition->getLabel();
          break;

        case 'created':
        case 'changed':
          $datefield_types[$fieldName] = $fieldDefinition->getLabel();
          break;
      }
    }

    $form['message'] = [
      '#type' => 'select',
      '#title' => t('Message Template'),
      '#options' => $message_types,
      '#default_value' => $this->configuration['message'],
      '#description' => t('Message Template to send.'),
      '#required' => TRUE,
    ];

    $form['date_field'] = [
      '#type' => 'select',
      '#title' => t('Date Field'),
      '#options' => $datefield_types,
      '#default_value' => $this->configuration['date_field'],
      '#description' => t('Date Field to base schedule on.'),
      '#required' => TRUE,
    ];

    $form['offset'] = [
      '#type' => 'textfield',
      '#title' => t('Offset'),
      '#default_value' => $this->configuration['offset'],
      '#field_prefix' => t('Date') . ' ',
      '#description' => t('Provide an offset such as "-30 days"'),
      '#size' => 60,
    ];
    $form['state'] = [
      '#type' => 'textfield',
      '#title' => t('State'),
      '#default_value' => $this->configuration['state'],
      '#description' => t('If not empty, message will only be sent if related entity is in this state (e.g. "active", "expired"). Currently depends on state_machine. Separate multiple states with a comma.'),
      '#size' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => '',
      '#message' => [
        'id' => $this->pluginDefinition['id'],
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['date_field'] = $form_state->getValue('date_field');
    $this->configuration['state'] = $form_state->getValue('state');
    $this->configuration['offset'] = $form_state->getValue('offset');
  }

}
