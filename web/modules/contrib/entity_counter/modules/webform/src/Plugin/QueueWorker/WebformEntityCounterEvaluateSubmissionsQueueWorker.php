<?php

namespace Drupal\entity_counter_webform\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_counter\Exception\EntityCounterException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process entity counter transaction queue tasks.
 *
 * @QueueWorker(
 *   id = "webform_entity_counter_evaluate_submissions",
 *   title = @Translation("Evaluate webform submissions and create the associated entity counter transactions"),
 *   cron = {"time" = 60}
 * )
 */
class WebformEntityCounterEvaluateSubmissionsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WebformEntityCounterEvaluateSubmissionsQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
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
    $submission_storage = $this->entityTypeManager->getStorage('webform_submission');
    $entity_counter_storage = $this->entityTypeManager->getStorage('entity_counter');

    try {
      /** @var \Drupal\webform\WebformSubmissionInterface $submission */
      $submission = $submission_storage->load($data['submission_id']);
      /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
      $entity_counter = $entity_counter_storage->load($data['entity_counter_id']);
      /** @var \Drupal\entity_counter\Plugin\EntityCounterSourceWithEntityConditionsInterface $source */
      $source = $entity_counter->getSource($data['entity_counter_source_id']);

      // @TODO Add a function or method for this.
      // @see entity_counter_webform_webform_submission_presave().
      if ($entity_counter->isOpen()) {
        if ($source->isEnabled()) {
          $source->setConditionEntity($submission);
          if ($source->evaluateConditions()) {
            $source->addTransaction(1.00, $submission);
          }
        }
      }
    }
    catch (EntityCounterException $exception) {
      watchdog_exception('entity_counter_webform', $exception);
    }
  }

}
