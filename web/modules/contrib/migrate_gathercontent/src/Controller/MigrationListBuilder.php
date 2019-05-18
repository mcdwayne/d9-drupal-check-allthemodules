<?php

namespace Drupal\migrate_gathercontent\Controller;

use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides a listing of migration entities in a given group.
 *
 * @package Drupal\migrate_tools\Controller
 *
 * @ingroup migrate_tools
 */
class MigrationListBuilder extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getOperations($group) {
    $operations = [];

    $params = [
      'group_id' => $group->id(),
    ];

    $operations['execute'] = [
      'title' => $this->t('Execute'),
      'weight' => 10,
      'url' => Url::fromRoute('migrate_gathercontent.execute', $params),
    ];
    return $operations;
  }

  public function buildOperations($group) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($group),
    ];

    return $build;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header = [
      'name' => $this->t('Group'),
      'count' => $this->t('Items'),
      'operations' => $this->t('Operations'),
    ];
    return $header;
  }

  /**
   * Builds a row for a migration plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $migration_entity
   *   The migration plugin for which to build the row.
   *
   * @return array|null
   *   A render array of the table row for displaying the plugin information.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow($group) {
    $mapping_entities = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadByProperties([
      'status' => TRUE,
      'group_id' => $group->id(),
    ]);

    // Getting all items in that group.
    $items = [];
    foreach ($mapping_entities as $mapping) {
      $template = $mapping->get('template');
      if (empty($items[$template])) {
        $id = $mapping->id();
        $migration = $this->migrationPluginManager->createInstance($mapping->getMigrationId());
        if (!empty($migration)) {
          $source = $migration->getSourcePlugin();
          $items[$template] = $source->count();
        }
        else {
          $items[$template] = 0;
        }
      }
    }

    $row = [
      'name' =>  $group->label(),
      'count' => array_sum($items),
      'operations' => ['data' => $this->buildOperations($group)],
    ];
    return $row;
  }

  /**
   * Render the migration row.
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function render() {

    $rows = [];
    $group_entities = $this->entityTypeManager->getStorage('gathercontent_group')->loadMultiple();
    foreach ($group_entities as $group) {
      $rows[] = $this->buildRow($group);
    }

    $form['migrations'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $rows,
      '#empty' => $this
        ->t('No migrations found'),
    ];

    return $form;
  }

}
