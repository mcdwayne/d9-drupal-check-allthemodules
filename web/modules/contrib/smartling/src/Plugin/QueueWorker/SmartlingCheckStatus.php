<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\QueueWorker\SmartlingCheckStatus.
 */

namespace Drupal\smartling\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\smartling\ApiWrapper\SmartlingApiWrapper;
use Drupal\smartling\SubmissionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes checking of submission status.
 *
 * @QueueWorker(
 *   id = "smartling_check_status",
 *   title = @Translation("Check Smartling translation status"),
 *   cron = {"time" = 30}
 * )
 */
class SmartlingCheckStatus extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The API wrapper service.
   *
   * @var \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   */
  protected $smartlingApiWrapper;

  /**
   * Submission storage.
   *
   * @var \Drupal\smartling\SubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new SmartlingCheckStatus object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smartling\ApiWrapper\SmartlingApiWrapper $smartling_api_wrapper
   *   The API wrapper service
   * @param \Drupal\smartling\SubmissionStorageInterface $entity_storage
   *   Submission storage.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, SmartlingApiWrapper $smartling_api_wrapper, SubmissionStorageInterface $entity_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->smartlingApiWrapper = $smartling_api_wrapper;
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('smartling.api_wrapper'),
      $container->get('entity.manager')->getStorage('smartling_submission')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($submission_id) {
    /* @var \Drupal\smartling\SmartlingSubmissionInterface $submission */
    $submission = $this->entityStorage->load($submission_id);

    if ($this->smartlingApiWrapper->getStatus($submission)) {
      // @todo Queue download task on entity save when needed.
      $submission->save();
    }
  }
}
