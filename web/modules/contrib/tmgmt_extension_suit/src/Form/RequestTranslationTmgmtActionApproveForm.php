<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobCheckoutManager;
use Drupal\tmgmt\JobQueue;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\tmgmt\Entity\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class RequestTranslationTmgmtActionApproveForm extends BaseTmgmtActionApproveForm {

  /**
   * Job checkout manager.
   *
   * @var \Drupal\tmgmt\JobCheckoutManager
   */
  protected $checkoutManager;

  /**
   * @var \Drupal\tmgmt\JobQueue
   */
  protected $jobQueue;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The upload queue.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Immutable config instance that contains settings.
   * @param \Drupal\tmgmt\JobCheckoutManager $checkout_manager
   * @param \Drupal\tmgmt\JobQueue $job_queue
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    QueueInterface $queue,
    ImmutableConfig $config,
    JobCheckoutManager $checkout_manager,
    JobQueue $job_queue
  ) {
    parent::__construct(
      $temp_store_factory,
      $entity_type_manager,
      $language_manager,
      $queue,
      $config
    );

    $this->checkoutManager = $checkout_manager;
    $this->jobQueue = $job_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('queue')->get('tmgmt_extension_suit_upload'),
      $container->get('config.factory')->get('tmgmt_extension_suit.settings'),
      $container->get('tmgmt.job_checkout_manager'),
      $container->get('tmgmt.queue')
    );
  }

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'tmgmt_extension_suit_tmgmt_job_operations_request_translation';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_request_translation_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Request translation');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to Request translations for these jobs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Requesting translation can take some time, do not close the browser');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve selected jobs.
    $jobs = $this->getEntities($this->entityIds);

    // Mark jobs as unprocessed for further processing.
    foreach ($jobs as $job) {
      // Requesting translation is possible only for unprocessed jobs.
      // See JobCheckoutManager::checkoutMultiple() method.
      $job->setState(Job::STATE_UNPROCESSED);

      foreach ($job->getItems() as $item) {
        // Reset statistics for the job item in order to have "count_pending"
        // value not equal to "0". See Job::requestTranslation() and
        // Job::getItems() methods.
        $item->setState(JobItem::STATE_ACTIVE);
        $item->resetData();
        $item->recalculateStatistics();
        $item->save();
      }
    }

    // Launch TMGMT's checkout process.
    $this->checkoutManager->checkoutAndRedirect($form_state, $jobs);
    $this->jobQueue->setDestination('/admin/tmgmt/jobs');
  }

  /**
   * {@inheritdoc}
   */
  public static function processBatch($data, &$context) {}
}
