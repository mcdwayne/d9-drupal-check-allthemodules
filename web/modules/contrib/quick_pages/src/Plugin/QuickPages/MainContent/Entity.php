<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Plugin\QuickPages\MainContent\Entity.
 */

namespace Drupal\quick_pages\Plugin\QuickPages\MainContent;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\quick_pages\MainContentBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows to use an entity view as main content.
 *
 * @MainContent(
 *   id = "entity",
 *   title = @Translation("Entity"),
 * )
 */
class Entity extends MainContentBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs the plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('logger.channel.quick_pages')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $entity_options = ['' => t('- Select -')];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasViewBuilderClass()) {
        $entity_options[$entity_type_id] = $entity_type->getLabel();
      }
    }

    asort($entity_options);

    $entity_type_wrapper = 'entity_type_settings';
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#options' => $entity_options,
      '#ajax' => [
        'wrapper' => $entity_type_wrapper,
        'callback' => [__CLASS__, 'entitySettings'],
        'event' => 'change',
      ],
      '#default_value' => $this->configuration['entity_type'],
      '#required' => TRUE,
    ];

    $form['#id'] = $entity_type_wrapper;
    $form['#theme_wrappers'][] = 'container';

    $entity_type = $this->configuration['entity_type'];

    $configuration = $this->configuration;

    $entity = NULL;
    if (!$form_state->isRebuilding() && isset($configuration[$entity_type]['entity_id'])) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $storage->load($this->configuration[$entity_type]['entity_id']);
      if (!$entity) {
        $message = t(
          'Could not load @entity_type #@entity.',
          [
            '@entity_type' => $entity_type,
            '@entity' => $configuration[$entity_type]['entity_id'],
          ]
        );
        drupal_set_message($message, 'error');
      }
    }

    if ($entity_type) {

      $entity_type_label = $this->entityTypeManager
        ->getDefinition($entity_type)
        ->getLabel();

      $form[$entity_type]['entity_id'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $entity_type_label,
        '#default_value' => $entity,
        '#maxlength' => 2048,
        '#target_type' => $entity_type,
        '#required' => TRUE,
      ];

      $default_view_mode = isset($this->configuration[$entity_type]['view_mode']) ?
        $this->configuration[$entity_type]['view_mode'] : 'default';

      $form[$entity_type]['view_mode'] = [
        '#type' => 'select',
        '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type),
        '#title' => t('View mode'),
        '#default_value' => $default_view_mode,
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function entitySettings(array $form, FormStateInterface $form_state) {
    return $form['main_content_provider']['configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMainContent() {

    $build = NULL;

    $entity_type = $this->configuration['entity_type'];

    $entity = $this->entityTypeManager->getStorage($entity_type)
      ->load($this->configuration[$entity_type]['entity_id']);

    if ($entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
      $build = $view_builder->view($entity, $this->configuration[$entity_type]['view_mode']);
    }
    else {
      $this->logger->error(
        'Could not load @entity_type #@entity_id',
        ['@entity_type' => $entity_type, '@entity_id' => $this->configuration[$entity_type]['entity_id']]
      );
    }

    return $build;
  }

}
