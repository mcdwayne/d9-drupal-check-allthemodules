<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns bare-bones information about every available file entity.
 *
 * @MigrateSource(
 *   id = "media_entity_generator",
 * )
 */
class MediaEntityGenerator extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var array*/
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
    QueryFactory $entity_query
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entity_field_manager = $entity_field_manager;
    $this->entity_type_manager = $entity_type_manager;
    $this->connection = $connection;
    $this->entity_query = $entity_query;

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
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set source file.
    if (!empty($row->getSource()['target_id'])) {
      $file = File::load($row->getSource()['target_id']);
      if ($file) {
        $row->setSourceProperty('file_path', $file->getFileUri());
        $row->setSourceProperty('file_name', $file->getFilename());
      }
    }
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
  public function __toString() {
    return '';
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
  protected function initializeIterator() {

    $entityDefinition = $this->entity_type_manager->getDefinition($this->configuration['entity_type']);
    $bundleKey = $entityDefinition->getKey('bundle');

    $files_found = [];

    foreach ($this->source_fields as $name => $source_field) {

      $query = $this->entity_query->get($this->configuration['entity_type']);

      $query->condition($bundleKey, $this->configuration['bundle']);
      $query->condition("{$name}.target_id", 0, '>', $this->configuration['langcode']);
      $results = $query->execute();

      if ($results) {

        $entitites = $this->entity_type_manager->getStorage($this->configuration['entity_type'])
          ->loadMultiple($results);

        foreach ($entitites as $id => $entity) {
          if ($entity->hasTranslation($this->configuration['langcode'])) {
            $entity = $entity->getTranslation($this->configuration['langcode']);
          }

          foreach ($entity->{$name}->getValue() as $reference) {
            $files_found[] = [
              'nid' => $entity->id(),
              'target_id' => $reference['target_id'],
              'alt' => $reference['alt'],
              'title' => $reference['title'],
              'langcode' => $this->configuration['langcode'],
              'entity' => $entity,
            ];
          }
        }
      }
    }
    return new \ArrayIterator($files_found);
  }

}
