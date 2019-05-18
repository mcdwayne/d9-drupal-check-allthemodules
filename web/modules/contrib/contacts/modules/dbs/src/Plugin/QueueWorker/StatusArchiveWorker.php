<?php

namespace Drupal\contacts_dbs\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Marks dbs status items as archived once they expire.
 *
 * @QueueWorker(
 *   id = "contacts_dbs_archive",
 *   title = @Translation("Archive status items"),
 *   cron = {"time" = 10}
 * )
 */
class StatusArchiveWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The DBS status storage handler.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $dbsStatusStorage;

  /**
   * Constructs a new StatusArchiveWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dbsStatusStorage = $entity_type_manager->getStorage('dbs_status');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data)) {
      /* @var \Drupal\contacts_dbs\Entity\DBSStatusInterface[] $entities */
      $entities = $this->dbsStatusStorage->loadMultiple($data);

      foreach ($entities as $entity) {
        $entity->set('status', 'dbs_expired')
          ->save();
      }
    }
  }

}
