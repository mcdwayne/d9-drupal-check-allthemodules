<?php

namespace Drupal\fac\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\fac\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of FacConfig.
 */
class FacConfigListBuilder extends ConfigEntityListBuilder {

  protected $searchPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.search_plugin')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\fac\SearchPluginManager $search_plugin_manager
   *   The Search Plugin Manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, SearchPluginManager $search_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->searchPluginManager = $search_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['id'] = $this->t('Machine name');
    $header['search_plugin'] = $this->t('Search plugin');
    $header['input_selectors'] = $this->t('Input selectors');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $search_plugin = $this->searchPluginManager->getDefinition($entity->getSearchPluginId());

    $status_label = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $status_icon = [
      '#theme' => 'image',
      '#uri' => $entity->status() ? 'core/misc/icons/73b355/check.svg' : 'core/misc/icons/e32700/error.svg',
      '#width' => 18,
      '#height' => 18,
      '#alt' => $status_label,
      '#title' => $status_label,
    ];

    $row = [
      'label' => $entity->label(),
      'id' => $entity->id(),
      'search_plugin' => $search_plugin['name']->render(),
      'input_selectors' => $entity->getInputSelectors(),
      'status' => [
        'data' => $status_icon,
      ],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $operations['delete_files'] = [
      'title' => $this->t('Delete json files'),
      'weight' => 100,
      'url' => Url::fromRoute('entity.fac_config.delete_files', [
        'fac_config_id' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
