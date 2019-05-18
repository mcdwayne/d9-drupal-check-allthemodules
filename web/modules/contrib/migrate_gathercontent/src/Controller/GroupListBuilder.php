<?php

namespace Drupal\migrate_gathercontent\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 */
class GroupListBuilder extends ConfigEntityListBuilder {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  MigrationPluginManagerInterface $migrationPluginManager) {
    parent::__construct($entity_type, $storage);

    $this->migrationPluginManager = $migrationPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_gathercontent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $params = [
      'group_id' => $entity->id()
    ];
    $operations['mappings'] = [
      'title' => $this->t('List Mappings'),
      'weight' => 10,
      'url' => Url::fromRoute('migrate_gathercontent.mapping.collection', $params),
    ];
    $operations += parent::getDefaultOperations($entity);
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
        'label' => $this->t('Label'),
        'machine_name' => $this->t('Machine Name'),
        'mappings' => $this->t('Mappings'),
      ] + parent::buildHeader();
    return $header;
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $mappings = \Drupal::entityTypeManager()->getStorage('gathercontent_mapping')->loadByProperties([
      'group_id' => $entity->id(),
    ]);
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['mappings'] = count($mappings);
    $row += parent::buildRow($entity);
    return $row;
  }
  /**
   * Gets this list's default operations.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public function render() {
    $build = parent::render();
    return $build;
  }
}
