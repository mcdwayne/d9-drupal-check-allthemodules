<?php

namespace Drupal\migrate_gathercontent\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class SettingsForm.
 */
class MappingListBuilder extends ConfigEntityListBuilder {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\migrate_gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $routeMatch;


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('migrate_gathercontent.client'),
      $container->get('plugin.manager.migration'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entityTypeManager, DrupalGatherContentClient $gathercontent_client, MigrationPluginManagerInterface $migrationPluginManager, RouteMatchInterface $routeMatch) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entityTypeManager;
    $this->client = $gathercontent_client;
    $this->migrationPluginManager = $migrationPluginManager;
    $this->routeMatch = $routeMatch;
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
    $operations = parent::getDefaultOperations($entity);
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
        'label' => $this->t('Label'),
        'machine_name' => $this->t('Machine Name'),
        'template' => $this->t('Template'),
        'bundle' => $this->t('Bundle'),
      ] + parent::buildHeader();
    return $header;
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    // Getting template name.
    // TODO: Need a more efficient/elegant way to load this data.
    $template = $this->client->templateGet($entity->get('template'));

    // Get status.
    $status = (!$entity->isEnabled()) ? ' ' . $this->t('(disabled)') : '';

    $row['label'] = $entity->label() . $status;
    $row['machine_name'] = $entity->id();
    $row['template'] = $template->name;
    $row['bunde'] = $entity->get('entity_type') . ':' . $entity->get('bundle');
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
    $rows = [];
    $group = $this->routeMatch->getParameter('group_id');
    $mapping_entities = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadByProperties([
      'group_id' => $group,
    ]);
    foreach ($mapping_entities as $group) {
      $rows[] = $this->buildRow($group);
    }

    $form['mappings'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $rows,
      '#empty' => $this
        ->t('No mappings found'),
    ];

    return $form;
  }
}
