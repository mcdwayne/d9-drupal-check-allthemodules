<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\Plugin\migrate\source\d7\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns bare-bones information about every available file entity.
 *
 * @MigrateSource(
 *   id = "media_entity_generator_d7",
 * )
 */
class MediaEntityGeneratorD7 extends Node implements ContainerFactoryPluginInterface {

  /**
   * @var array
   */
  protected $source_fields = [];

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entity_field_manager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entity_type_manager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $entity_query;

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

  /**
   * MediaEntityGenerator constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    QueryFactory $entity_query,
    StateInterface $state,
    EntityManagerInterface $entity_manager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager, $module_handler);
    $this->entity_field_manager = $entity_field_manager;
    $this->entity_type_manager = $entity_type_manager;
    $this->connection = $connection;
    $this->entity_query = $entity_query;

    // Do not joint source tables.
    $this->configuration['ignore_map'] = TRUE;

    foreach ($this->configuration['field_names'] as $name) {
      $this->source_fields[$name] = $name;
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('entity.query'),
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('module_handler')
    );
  }

  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'target_id' => $this->t('The file entity ID.'),
      'file_id' => $this->t('The file entity ID.'),
      'file_path' => $this->t('The file path.'),
      'file_name' => $this->t('The file name.'),
      'file_alt' => $this->t('The file arl.'),
      'file_title' => $this->t('The file title.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'target_id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')
      ->fields('n', [
        'nid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'sticky',
        'tnid',
        'translate',
      ])
      ->fields('nr', [
        'vid',
        'title',
        'log',
        'timestamp',
      ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);

    // If the content_translation module is enabled, get the source langcode
    // to fill the content_translation_source field.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $query->leftJoin('node', 'nt', 'n.tnid = nt.nid');
      $query->addField('nt', 'language', 'source_langcode');
    }
    $this->handleTranslations($query);

    if (isset($this->configuration['bundle'])) {
      $query->condition('n.type', $this->configuration['bundle']);
    }

    return $query;
  }

  public function getProcess() {
    if ($this->init) {
      return parent::getProcess();
    }
    $this->init = TRUE;
    if (!\Drupal::moduleHandler()->moduleExists('field')) {
      return parent::getProcess();
    }
    $definition['source'] = [
        'ignore_map' => TRUE,
      ] + $this->getSourceConfiguration();
    $definition['source']['plugin'] = 'd7_field_instance';
    $definition['destination']['plugin'] = 'null';
    $definition['idMap']['plugin'] = 'null';
    $field_migration = $this->migrationPluginManager->createStubMigration($definition);
    foreach ($field_migration->getSourcePlugin() as $row) {
      $field_name = $row->getSourceProperty('field_name');
      $field_type = $row->getSourceProperty('type');
      if ($this->fieldPluginManager->hasDefinition($field_type)) {
        if (!isset($this->fieldPluginCache[$field_type])) {
          $this->fieldPluginCache[$field_type] = $this->fieldPluginManager->createInstance($field_type, [], $this);
        }
        $info = $row->getSource();
        $this->fieldPluginCache[$field_type]->defineValueProcessPipeline($this, $field_name, $info);
      }
      else {
        $this->setProcessOfProperty($field_name, $field_name);
      }
    }
    return parent::getProcess();
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {

    $query_files = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('uri', 'temporary://%', 'NOT LIKE')
      ->orderBy('f.timestamp');

    $all_files = $query_files->execute()->fetchAllAssoc('fid');

    $files_found = [];

    foreach ($this->source_fields as $name => $source_field) {

      $parent_iterator = parent::initializeIterator();

      foreach ($parent_iterator as $entity) {
        $nid = $entity['nid'];
        $vid = $entity['vid'];
        $field_value = $this->getFieldValues('node', $name, $nid, $vid, $this->configuration['langcode']);

        foreach ($field_value as $reference) {

          // Support remote file urls.
          $file_url = $all_files[$reference['fid']]['uri'];
          if(!empty($this->configuration['d7_file_url'])) {
            $file_url = str_replace('public://', '', $file_url);
            $file_path = rawurlencode($file_url);
            $file_url = $this->configuration['d7_file_url'] . $file_path;
          }

          if (!empty($all_files[$reference['fid']]['uri'])) {

            $files_found[] = [
              'nid' => $entity['nid'],
              'target_id' => $reference['fid'],
              'alt' => $reference['alt'],
              'title' => $reference['title'],
              'langcode' => $this->configuration['langcode'],
              'entity' => $entity,
              'file_name' => $all_files[$reference['fid']]['filename'],
              'file_path' => $file_url,
            ];
          }
        }
      }
    }
    return new \ArrayIterator($files_found);
  }
}
