<?php

namespace Drupal\entity_collector\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityCollectionSelectionBlock.
 *
 * @Block(
 *  id = "entity_collection_selection",
 *  admin_label = @Translation("Entity collection selection"),
 *  category = @Translation("Entity Collection"),
 * )
 */
class EntityCollectionSelectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Entity Form Builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity collection manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  private $entityCollectionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * Entity Collection Type.
   *
   * @var EntityCollectionTypeInterface
   */
  protected $entityCollectionType;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplayRepository, EntityFormBuilderInterface $entityFormBuilder, EntityCollectionManagerInterface $entityCollectionManager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->entityFormBuilder = $entityFormBuilder;
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
      $container->get('entity_display.repository'),
      $container->get('entity.form_builder'),
      $container->get('entity_collection.manager'),
      $container->get('current_user')
    );
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

    $form['create_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Collection Create Form'),
      '#description' => $this->t('Show a form to create a new collection.'),
      '#default_value' => isset($config['create_form']) ? $config['create_form'] : FALSE,
    ];

    $form['selection_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Selection List'),
      '#description' => $this->t('Show a selection list to choose a collection to set as the active one.'),
      '#default_value' => isset($config['selection_list']) ? $config['selection_list'] : FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['entity_collection_type'] = $form_state->getValue('entity_collection_type');
    $this->configuration['create_form'] = $form_state->getValue('create_form');
    $this->configuration['selection_list'] = $form_state->getValue('selection_list');
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $entityCollection = $this->entityTypeManager->getStorage('entity_collection')->create(['type' => $this->entityCollectionType->id()]);

    $build = [
      '#attributes' => [
        'class' => [
          'js-entity-collection-selection-block',
          'entity-collection-type-' . $entityCollection->bundle(),
        ],
        'data-entity-collection-type' => $entityCollection->bundle(),
        'data-entity-collection' => $entityCollection->id(),
      ],
    ];

    if ($config['create_form']) {
      $build['entity_collection_create'] = [
        'title' => [
          '#markup' => $this->t('New :label Collection', array(':label' => $this->entityCollectionType->label())),
        ],
        'form' => $this->entityFormBuilder->getForm($entityCollection, 'block'),
      ];
    }

    if ($config['selection_list']) {
      $build['entity_collection_list'] = [
        'title' => [
          '#markup' => $this->t('Select a :label Collection', array(':label' => $this->entityCollectionType->label()))
        ],
        'item_list' => $this->entityCollectionManager->getCollectionItemList($this->entityCollectionType),
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
