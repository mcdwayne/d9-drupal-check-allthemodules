<?php

namespace Drupal\entity_collector\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a 'Entity Collection' Block.
 *
 * @Block(
 *  id = "entity_collection_block",
 *  admin_label = @Translation("Entity Collection block"),
 *  category = @Translation("Entity Collection"),
 * )
 */
class EntityCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity collection manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * Entity Collection Type.
   *
   * @var EntityCollectionTypeInterface
   */
  protected $entityCollectionType;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, EntityCollectionManagerInterface $entityCollectionManager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityCollectionManager = $entityCollectionManager;
    $this->currentUser = $currentUser;

    /** @var EntityCollectionTypeInterface $entityCollectionType */
    $config = $this->getConfiguration();
    $this->entityCollectionType = $this->entityTypeManager->getStorage('entity_collection_type')
      ->load($config['entity_collection_type']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('entity_collection.manager'),
      $container->get('current_user')
    );
  }

  public function defaultConfiguration() {
    return [
      'entity_collection_type' => NULL,
      'entity_collection_view_mode' => NULL,
      'always_show' => FALSE,
      'show_title_as_link' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $entityCollectionTypes = $this->entityTypeManager->getStorage('entity_collection_type')
      ->loadMultiple();

    $entityCollectionTypeOptions = [];
    foreach ($entityCollectionTypes as $entityCollectionType) {
      $entityCollectionTypeOptions[$entityCollectionType->id()] = $entityCollectionType->label();
    }
    $form = $this->entityCollectionManager->getCollectionTypeFormField($form, $entityCollectionTypeOptions, $config);

    $entityCollectionViewModeOptions = $this->getEntityCollectionViewModeOptions();

    $form['entity_collection_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Collection View Mode'),
      '#description' => $this->t(''),
      '#empty_option' => $this->t('Please select'),
      '#options' => $entityCollectionViewModeOptions,
      '#default_value' => isset($config['entity_collection_view_mode']) ? $config['entity_collection_view_mode'] : NULL,
      '#required' => TRUE,
    ];

    $form['always_show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show block always'),
      '#description' => $this->t('Show this block alway, even when no collection is active.'),
      '#default_value' => isset($config['always_show']) ? $config['always_show'] : NULL,
    ];

    $form['show_title_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show collection title as a link'),
      '#description' => $this->t('Make the title link to the detail page of the collection.'),
      '#default_value' => isset($config['show_title_as_link']) ? $config['show_title_as_link'] : NULL,
    ];

    return $form;
  }

  /**
   * Returns an array with the view modes for the entity_collection entity.
   *
   * @param array $entityCollectionViewModeOptions
   *
   * @return array
   */
  protected function getEntityCollectionViewModeOptions() {
    $viewModes = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'entity_collection')
      ->execute();

    if (empty($viewModes)) {
      return [];
    }

    foreach ($viewModes as $viewMode) {
      /** @var \Drupal\Core\Entity\EntityViewModeInterface $mode */
      $mode = EntityViewMode::load($viewMode);
      $label = $mode->label();
      $key = substr($mode->id(), strpos($mode->id(), ".") + 1);
      $entityCollectionViewModeOptions[$key] = $label;
    }

    return $entityCollectionViewModeOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['entity_collection_type'] = $form_state->getValue('entity_collection_type');
    $this->configuration['entity_collection_view_mode'] = $form_state->getValue('entity_collection_view_mode');
    $this->configuration['always_show'] = $form_state->getValue('always_show');
    $this->configuration['show_title_as_link'] = $form_state->getValue('show_title_as_link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
    $entityCollectionType = $this->entityTypeManager->getStorage('entity_collection_type')
      ->load($config['entity_collection_type']);
    $entityCollection = $this->entityCollectionManager->getActiveCollection($entityCollectionType);

    $build = [
      '#attributes' => [
        'class' => [
          'js-entity-collection-block',
          'entity-collection-type-' . $entityCollectionType->id(),
        ],
        'data-entity-collection-type' => $entityCollectionType->id(),
      ],
    ];

    if ($config['always_show'] && empty($entityCollection)) {
      $build['title'] = [
        '#markup' => $this->t('No active collection available, please create or switch to a collection.'),
      ];
    }

    if (empty($entityCollection)) {
      return $build;
    }

    $build['#attributes']['data-entity-collection'] = $entityCollection->id();
    $build['#attached']['library'] = [
      'entity_collector/entity-collection-field-operations',
      'core/drupal.ajax',
    ];
    $build['entity_collection_block'] = $this->entityTypeManager->getViewBuilder('entity_collection')
      ->view($entityCollection, $config['entity_collection_view_mode']);

    $title = $entityCollection->label();

    if ($config['show_title_as_link']) {
      $build['title'] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => $entityCollection->toUrl(),
      ];
    }
    else {
      $build['title'] = [
        '#markup' => $title,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cacheContexts = parent::getCacheContexts();
    $listCacheTags = $this->entityCollectionManager->getListCacheContexts($this->entityCollectionType->id(), $this->currentUser->id());
    return Cache::mergeContexts($cacheContexts, $listCacheTags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cacheTags = parent::getCacheTags();
    $listCacheTags = $this->entityCollectionManager->getListCacheTags($this->entityCollectionType->id(), $this->currentUser->id());
    return Cache::mergeTags($cacheTags, $listCacheTags);
  }

}
