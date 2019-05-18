<?php

namespace Drupal\gathercontent;

use Drupal\gathercontent\Import\MenuCreator;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_tools\MigrateExecutable as MigrateExecutableBase;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\node\Entity\Node;

/**
 * Defines a migrate executable class.
 */
class MigrateExecutable extends MigrateExecutableBase {

  /**
   * Migration options.
   *
   * @var array
   */
  protected $importOptions = [];

  /**
   * Gathercontent client.
   *
   * @var \Drupal\gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * Latest GatherContent status.
   *
   * @var \Cheppers\GatherContent\DataTypes\Status
   */
  protected $latestGcStatus;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, array $options = []) {
    parent::__construct($migration, $message, $options);

    if (isset($options['import_options'])) {
      $this->importOptions = $options['import_options'];
    }

    if (isset($options['client'])) {
      $this->client = $options['client'];
    }

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->session = \Drupal::service('session');
  }

  /**
   * {@inheritdoc}
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    parent::onPrepareRow($event);
    $row = $event->getRow();

    $this->latestGcStatus = $row->getSourceProperty('status');

    $migration = $event->getMigration();
    $source_id = array_merge(array_flip(array_keys($migration->getSourcePlugin()
      ->getIds())), $row->getSourceIdValues());

    /** @var \Drupal\gathercontent\Import\ImportOptions $options */
    $options = $this->importOptions[$source_id['id']];

    if (empty($options)) {
      throw new MigrateSkipRowException(NULL, FALSE);
    }

    // TODO: change to use entity specific status field if exists.
    $row->setDestinationProperty('status', $options->getPublish());
    $row->setDestinationProperty('gc_import_options/new_revision', $options->getCreateNewRevision());
  }

  /**
   * {@inheritdoc}
   */
  public function onPostImport(MigrateImportEvent $event) {
    parent::onPostImport($event);
    $rows = [];

    $migration = $event->getMigration();
    $destination_configuration = $migration->getDestinationConfiguration();
    $plugin = explode(':', $destination_configuration['plugin']);
    $source_configuration = $migration->getSourceConfiguration();

    foreach ($this->idlist as $item) {
      $rows[] = $event->getMigration()->getIdMap()->getRowBySource($item);
    }

    if (empty($rows)) {
      return;
    }

    foreach ($rows as $row) {
      // Ignore sub-entities.
      if (isset($row['destid2'])) {
        continue;
      }

      /** @var \Drupal\gathercontent\Import\ImportOptions $options */
      $options = $this->importOptions[$row['sourceid1']];
      $parent_menu_item = $options->getParentMenuItem();

      if (!empty($parent_menu_item) && $parent_menu_item != '0') {
        // TODO: Use the entity type from the mapping, not the node!
        $entity = Node::load($row['destid1']);

        // TODO: Rewrite menu creator to support none node entities too.
        MenuCreator::createMenu($entity, $parent_menu_item);
      }

      $new_gc_status = $options->getNewStatus();

      if ($new_gc_status && is_int($new_gc_status)) {
        $status = $this->client->projectStatusGet($source_configuration['projectId'], $new_gc_status);

        // Update only if status exists.
        if ($status !== NULL) {
          // Update status on GC.
          $this->client->itemChooseStatusPost($row['sourceid1'], $new_gc_status);

          $this->latestGcStatus = $status;
        }
      }

      $this->trackEntities($row, $plugin[1], $source_configuration['templateName'], $migration->id());
    }
  }

  /**
   * Tracks the entity changes, to show in a table after the migration run.
   */
  protected function trackEntities(array $row, string $plugin, string $templateName, $migrationId) {
    // Ignore sub-entities.
    if (isset($row['destid2'])) {
      return;
    }

    $tracked = $this->session->get('gathercontent_tracked_entities', []);

    $tracked[$row['sourceid1']] = [
      'id' => $row['destid1'],
      'entity_type' => $plugin,
      'status' => $this->latestGcStatus,
      'template_name' => $templateName,
    ];

    $this->session->set('gathercontent_tracked_entities', $tracked);

    $connection = \Drupal::service('database');
    $connection->insert('gathercontent_entity_mapping')
      ->fields([
        'entity_id' => $row['destid1'],
        'entity_type' => $plugin,
        'gc_id' => $row['sourceid1'],
        'migration_id' => $migrationId,
      ])
      ->execute();
  }

}
