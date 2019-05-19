<?php

namespace Drupal\tmgmt_smartling\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt_smartling\Context\ContextUserAuth;
use Drupal\tmgmt_smartling\Exceptions\SmartlingBaseException;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\SharedTempStoreFactory;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\tmgmt_smartling\Context\ContextUploader;
use Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class SendContextActionApproveForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The shared store factory.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $sharedStoreFactory;

  /**
   * Context user auth.
   *
   * @var \Drupal\tmgmt_smartling\Context\ContextUserAuth
   */
  protected $contextUserAuth;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The upload queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The array of entities to send.
   *
   * @var string[]
   */
  protected $entityIds = array();

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'tmgmt_smartling_send_context';

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\SharedTempStoreFactory $shared_store_factory
   * @param \Drupal\tmgmt_smartling\Context\ContextUserAuth $context_user_auth
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The upload queue.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    SharedTempStoreFactory $shared_store_factory,
    ContextUserAuth $context_user_auth,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    QueueInterface $queue
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sharedStoreFactory = $shared_store_factory;
    $this->contextUserAuth = $context_user_auth;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('user.shared_tempstore'),
      $container->get('tmgmt_smartling.utils.context.user_auth'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('queue')->get('smartling_context_upload'),
      $container->get('logger.channel.smartling')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_smartling_send_context_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to send context for these items to Smartling?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Sending can take some time, do not close the browser');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Send Context to Smartling');
  }

  public function getTempStorageName() {
    return $this->tempStorageName;
  }

  protected function getEntities(array $entity_ids) {
    $entities = [];
    $number_of_entity_types = count(array_unique(array_values($entity_ids)));
    if ($number_of_entity_types == 1) {
      $entity_type = array_values($entity_ids)[0];
      $ids = array_keys($entity_ids);
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $entities = $this->entityTypeManager->getStorage($entity_type)
        ->loadMultiple($ids);
    } else {
      foreach ($entity_ids as $id => $ent_type) {
        $entities[] = $this->entityTypeManager->getStorage($ent_type)->load($id);
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $temp_storage_name = $this->getTempStorageName();
    // Retrieve the content to be sent from the temp store.
    $this->entityIds = $this->tempStoreFactory
      ->get($temp_storage_name)
      ->get($this->currentUser()->id());
    if (!$this->entityIds) {
      return new RedirectResponse($this->getCancelUrl()
        ->setAbsolute()
        ->toString());
    }

    $items = [];
    $entities = $this->getEntities($this->entityIds);
    foreach ($entities as $entity) {
      $items[$entity->id()] = $entity->label();
    }
    $form['items'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Entities to contextualize'),
      '#items' => $items,
    ];

    return ConfirmFormBase::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();
    $temp_storage_name = $this->getTempStorageName();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get($temp_storage_name)
      ->delete($current_user_id);
    if (!$form_state->getValue('confirm')) {
      return;
    }

    //@todo:change this when we add the support for queues.
    $is_batch = TRUE;
    $operations = [];

    foreach ($this->entityIds as $id => $entity_type) {
      // Make sure all submissions exists.
      $item_data = [
        'entity_type' => $entity_type,
        'entity_id' => $id,
      ];

      if ($is_batch) {
        $operations[] = [
          [get_class($this), 'processBatch'],
          [$item_data],
        ];
      }
      else {
        $this->queue->createItem($item_data);
      }
    }

    try {
      $ids = array_keys($this->entityIds);
      $job_item_id = reset($ids);
      $translator = JobItem::load($job_item_id)->getTranslator();

      if (!empty($translator)) {
        $translator_settings = $translator->getSettings();

        // Save user name before user switching. We have to use shared storage because we will switch
        // users but we will need to switch user back in the end of the batch.
        $this->sharedStoreFactory->get($temp_storage_name)->set('user_name_before_switching', $this->currentUser()->getAccountName());
        $this->contextUserAuth->switchUser($translator_settings['contextUsername'], $translator_settings['context_silent_user_switching']);
      }
      else {
        drupal_set_message(
          t('Context was not sent to Smartling, because Smartling provider was not selected for one of these jobs: @jids', [
          '@jids' => implode(' ,', $ids),
        ]), 'error');
      }
    }
    catch (Exception $e) {
      watchdog_exception('tmgmt_smartling', $e);
    }

    if ($is_batch && $operations) {
      $batch = [
        'title' => t('Uploading to Smartling'),
        'operations' => $operations,
        'finished' => [get_class($this), 'finishBatch'],
      ];
      batch_set($batch);
    }
    else {
      $form_state->setRedirect('system.admin_content');
    }
  }

  /**
   * Finish batch callback.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishBatch($success, $results, $operations) {
    // Switch user back after batch. We have to use \Drupal::getContainer() because we don't have $this context in
    // static functions.
    $user_name_before_switching = \Drupal::getContainer()
      ->get('user.shared_tempstore')
      ->get('tmgmt_smartling_send_context')
      ->get('user_name_before_switching');

    \Drupal::getContainer()->get('tmgmt_smartling.utils.context.user_auth')->switchUser($user_name_before_switching);
  }

  /**
   * Processes the sending batch.
   *
   * @param array $data
   *   Keyed array of data to send.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch($data, &$context) {
    if (!isset($context['results']['errors'])) {
      $context['results']['errors'] = [];
      $context['results']['count'] = 0;
    }

    $entity_type_id = $data['entity_type'];
    $entity_id = $data['entity_id'];

    try {
      $job_item = \Drupal::entityTypeManager()->getStorage($entity_type_id)
        ->loadMultiple([$entity_id]);
      $job_item = reset($job_item);

      if (empty($job_item->getTranslator()) || !($job_item->getTranslator()->getPlugin() instanceof SmartlingTranslator)) {
        return;
      }

      $job = $job_item->getJob();
      $filename = $job->getTranslatorPlugin()->getFileName($job);


      /** @var ContextUploader $context_uploader */
      $context_uploader = \Drupal::getContainer()->get('tmgmt_smartling.utils.context.uploader');
      $url = $context_uploader->jobItemToUrl($job_item);

      if ($job->hasTranslator()) {
        $settings = $job->getTranslator()->getSettings();
      } else {
        \Drupal::logger('tmgmt_smartling')->warning("Job with ID=@id has no translator plugin.", ['@id' => $job->id()]);
        return;
      }

      if ($context_uploader->isReadyAcceptContext($filename, $settings)) {
        $result = $context_uploader->upload($url, $filename, $settings);
      }
      else {
        $result = [];
      }
    }
    catch (SmartlingBaseException $e) {
      \Drupal::logger('tmgmt_smartling')->error($e->getMessage());
    }


    if (empty($result)) {
      $context['results']['errors'][] = t('Context wasn\'t uploaded. Please see logs for more info.');
    } else {
      $context['message'] = new FormattableMarkup('Context for "@name" was successfully sent.', [
        '@name' => $job_item->label(),
      ]);
    }
  }
}
