<?php

namespace Drupal\entity_pilot_git;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class EntityOperations.
 *
 * @package Drupal\entity_pilot_git
 */
class EntityOperations implements EntityOperationsInterface {

  /**
   * Config factory service..
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repo
   *   The entity type repository service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueryFactory $entity_query, EntityFieldManager $entity_field_manager, EntityTypeRepositoryInterface $entity_type_repo) {
    $this->configFactory = $config_factory;
    $this->entityQuery = $entity_query;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeRepository = $entity_type_repo;
  }

  /**
   * {@inheritdoc}
   */
  public function checkForUpdates($from_date, array $entity_types = []) {
    // Get all content entity type ids if none were provided.
    if (empty($entity_types)) {
      $labels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
      $entity_types = array_keys($labels['Content']);
    }

    $skip_types = $this->configFactory->get('entity_pilot_git.settings')->get('skip_entity_types');
    // Filter down our list with what we've chosen to skip.
    if (!empty($skip_types)) {
      $entity_types = array_diff($entity_types, array_keys(array_filter($skip_types)));
    }

    // If any of the entity types have entities from the given date, return
    // true.
    foreach ($entity_types as $entity_type_id) {
      if (!empty($this->getEntitiesFromDate($entity_type_id, $from_date))) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesFromDate($entity_type_id, $from_date = 0) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->entityQuery->get($entity_type_id);

    // Make sure the changed field exists.
    $fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

    // Add a condition on from date if it exists.
    if ($from_date) {
      // Scheduled updates are updated when the fail to be run, so only check
      // for newly created ones.
      if (!empty($fields['changed']) && $entity_type_id != 'scheduled_update') {
        $query->condition('changed', $from_date, '>');
      }
      else {
        $query->condition('created', $from_date, '>');
      }
    }

    $entity_ids = $query->execute();
    return $entity_ids;
  }

}
