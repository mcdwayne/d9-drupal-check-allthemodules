<?php

namespace Drupal\webform_digests\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\webform_digests\WebformDigestsMailHandlerInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;

/**
 * Send out any pending webform digests.
 *
 * @QueueWorker(
 *   id = "webform_digest_queue",
 *   title = @Translation("Webform digest queue"),
 *   cron = {"time" = 30}
 * )
 */
class WebformDigestQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The webform submission (server-side) conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  protected $webformSubmissionStorage;

  protected $webformDigestMailHandler;

  protected $webformDigest;

  /**
   * Constructs a new WebformMassEmailQueue instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, EntityTypeManager $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformDigestsMailHandlerInterface $webform_digests_mail_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->webformSubmissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->conditionsValidator = $conditions_validator;
    $this->webformDigestsMailHandler = $webform_digests_mail_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform_digests.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->webformDigest = $data['digest'];
    $query = $this->webformSubmissionStorage->getQuery();
    $query->condition('changed', [$data['start'], $data['end']], 'BETWEEN');
    $query->condition('webform_id', $this->webformDigest->getWebform());
    $submissionIds = $query->execute();
    if (!empty($submissionIds)) {
      $totalSent = 0;
      $relevantSubmissions = $this->loadRelevantSubmissions($submissionIds);
      $submissionsBySource = $this->getSubmissionsBySourceEntity($relevantSubmissions);
      foreach ($submissionsBySource as $source => $submissions) {
        $this->webformDigest->setSubmissions($submissions);
        $firstSubmission = current($submissions);
        $this->webformDigestsMailHandler->sendMessage($this->webformDigest, $firstSubmission->getSourceEntity());
        $totalSent++;
      }
      $this->getLogger()->notice('@sent webform digest email(s) sent.', [
        '@sent' => $totalSent,
      ]);
    }
    else {
      $this->getLogger()->notice('No webform digest emails sent.');
    }
  }

  /**
   * Load all submissions and filter if its a conditional digest.
   */
  protected function loadRelevantSubmissions($submissionIds) {
    $submissions = $this->webformSubmissionStorage->loadMultiple($submissionIds);
    if ($this->webformDigest->isConditional()) {
      $conditions = $this->webformDigest->getConditions();
      $state = key($conditions);
      $conditions = $conditions[$state];
      $submissions = array_filter($submissions, function ($submission) use ($conditions) {
        return $this->conditionsValidator->validateConditions($conditions, $submission);
      });
    }
    return $submissions;
  }

  /**
   * Get an instance of the logger for webform digest.
   */
  protected function getLogger() {
    return $this->loggerFactory->get('webform_digests');
  }

  /**
   * Get the webform submissions by source entity.
   */
  protected function getSubmissionsBySourceEntity(array $submissions) {
    $sourceSubmissions = [];
    foreach ($submissions as $submission) {
      $sourceEntity = $submission->getSourceEntity();
      if ($sourceEntity) {
        $sourceSubmissions[$sourceEntity->id()][] = $submission;
      }
    }
    return $sourceSubmissions;
  }

}
