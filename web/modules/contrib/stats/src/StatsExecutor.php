<?php

namespace Drupal\stats;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\stats\Entity\StatProcessorInterface;
use Drupal\stats\Plugin\StatDestinationManager;
use Drupal\stats\Plugin\StatStepManager;
use Drupal\stats\Plugin\StatSourceManager;

/**
 * Class StatsExecutor.
 */
class StatsExecutor {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\stats\Plugin\StatSourceManager
   */
  protected $statSourceManager;

  /**
   * @var \Drupal\stats\Plugin\StatDestinationManager
   */
  protected $statDestinationManager;

  /**
   * @var \Drupal\stats\Plugin\StatStepManager
   */
  protected $statStepManager;

  /**
   * Constructs a new StatsExecutor object.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    StatSourceManager $statSourceManager,
    StatDestinationManager $statDestinationManager,
    StatStepManager $StatStepManager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->statSourceManager = $statSourceManager;
    $this->statDestinationManager = $statDestinationManager;
    $this->statStepManager = $StatStepManager;
  }

  /**
   * Executes a stat set for a given entity.
   *
   * @param string $entity_type
   * @param integer $entity_id
   * @param string $stat_processor_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function executeByIds($entity_type, $entity_id, $stat_processor_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $processor = $this->entityTypeManager->getStorage('stat_processor')->load($stat_processor_id);

    if (!$processor->supportsTriggerEntity($entity)) {
      throw new \Exception('Trigger entity not supported');
    }

    $this->executeSingle($entity, $processor);
  }

  public function execute(ContentEntityInterface $entity) {
    // Load processors.
    $processors = $this->getStatProcessorsForEntity($entity);

    // @todo: Order by dependencies.

    // Process in order of dependencies.
    foreach ($processors as $processor) {
      $this->executeSingle($entity, $processor);
    }
  }

  public function executeSingle(ContentEntityInterface $entity, StatProcessorInterface $processor) {

    // @todo refactor to use factory?
    $execution = new StatExecution($processor, $entity);

    // Load rows via source plugin.
    $source = $this->statSourceManager->createInstance($processor->getSourcePluginID(), $processor->getSource(), $execution);
    $collection = $source->getRows();

    // Foreach process step
    // run each process plugin in a step
    // and store the stat in destination key.
    foreach ($processor->getSteps() as $prop => $step) {
      $plugin_id = $step['plugin'];
      /** @var \Drupal\stats\Plugin\StatStepInterface $stepPlugin */
      $stepPlugin = $this->statStepManager->createInstance($plugin_id, $step, $execution);
      $stepPlugin->process($collection);
    }

    // Process each row with the destination plugin.
    $destination = $this->statDestinationManager->createInstance($processor->getDestinationPluginID(), $processor->getDestination(), $execution);
    foreach ($collection as $row) {
      $destination->import($row);
    }
  }

  /**
   * Retrieve stat processors that match the given trigger entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\stats\Entity\StatProcessorInterface[]
   */
  public function getStatProcessorsForEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\stats\Entity\StatProcessorInterface[] $processors */
    $processors = $this->entityTypeManager->getStorage('stat_processor')->loadMultiple();

    $return = [];
    foreach ($processors as $processor) {
      if ($processor->supportsTriggerEntity($entity)) {
        $return[] = $processor;
      }
    }
    return $return;
  }


}
