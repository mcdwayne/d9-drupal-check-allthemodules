<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\SendMultipleConfirmForm.
 */

namespace Drupal\smartling\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\smartling\Entity\SmartlingSubmission;
use Drupal\smartling\SubmissionStorageInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class DownloadSubmissionApproveForm extends SendMultipleConfirmForm {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

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
   * The submission storage.
   *
   * @var \Drupal\smartling\SubmissionStorageInterface
   */
  protected $submissionStorage;

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
  protected $tempStorageName = 'smartling_smartling_submission_operations_download';

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\smartling\SubmissionStorageInterface $submission_storage
   *   The submission storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The upload queue.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Immutable config instance that contains smartling settings.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, SubmissionStorageInterface $submission_storage, LanguageManagerInterface $language_manager, QueueInterface $queue, ImmutableConfig $config) {
//    $this->tempStoreFactory = $temp_store_factory;
//    $this->entityTypeManager = $entity_type_manager;
//    $this->submissionStorage = $submission_storage;
//    $this->languageManager = $language_manager;
//    $this->queue = $queue;
//    $this->config = $config;

    parent::__construct($temp_store_factory, $entity_type_manager, $submission_storage, $language_manager, $queue, $config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')
        ->getStorage('smartling_submission'),
      $container->get('language_manager'),
      $container->get('queue')->get('smartling_upload'),
      $container->get('config.factory')->get('smartling.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartling_send_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to send these content to Smartling?');
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
    return $this->t('Send to Smartling');
  }

  public function getTempStorageName() {
    return $this->tempStorageName;
  }


  protected function getEntities(array $entityIds) {
    $entities = [];
    $number_of_entity_types = count(array_unique(array_values($entityIds)));
    if ($number_of_entity_types == 1) {
      $entity_type = array_values($entityIds)[0];
      $ids = array_keys($entityIds);
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $entities = $this->entityTypeManager->getStorage($entity_type)
        ->loadMultiple($ids);
    } else {
      foreach($entityIds as $id => $ent_type) {
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

    $entities = $this->getEntities($this->entityIds);



//    $form['batch'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Execute operation immediately'),
//      '#default_value' => TRUE,
//    ];

    $items = [];
    foreach ($entities as $entity) {
      $items[$entity->id()] = $entity->label();
    }
    $form['items'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Entities to download'),
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

    $entities = $this->getEntities($this->entityIds);
    //print_r($entities);die();

    $is_batch = $this->config->get('expert.async_mode');//$form_state->getValue('batch');
    $operations = [];

    foreach ($entities as $submission) {
      // Make sure all submissions exists.
      if (isset($submission)) {
        $locale = $submission->get('target_language')->value;
        $item_data = [
          'entity_type' => $submission->get('entity_type')->value,
          'entity_id' => $submission->get('entity_id')->value,
          'file_name' => $submission->getFileName(),
          'locales' => [$locale => $locale],
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
    }
    if ($is_batch && $operations) {
      $batch = [
        'title' => t('Downloading from Smartling'),
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

    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_type_id = $data['entity_type'];
    $entity_id = $data['entity_id'];

    $submission = reset($entity_type_manager
      ->getStorage('smartling_submission')
      ->loadByProperties(['entity_id' => $entity_id, 'entity_type' => $entity_type_id, 'target_language' => reset($data['locales'])]));

    if (!$submission) {
      $context['results']['errors'][] = t('Entity @entity_type:@entity_id not found', [
        '@entity_type' => $entity_type_id,
        '@entity_id' => $entity_id,
      ]);
    }
    elseif ($entity_type_manager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $entity_type_manager->getHandler($entity_type_id, 'smartling');

      if ($handler->downloadTranslation($submission)) {
        $context['results']['count']++;
      }
      else {
        $context['results']['errors'][] = new FormattableMarkup('Error downloading %name', [
          '%name' => $submission->label(),
        ]);
      }
      $context['message'] = new FormattableMarkup('Processed %name.', [
        '%name' => $submission->label(),
      ]);
    }
    else {
      $context['message'] = new FormattableMarkup('Skipped %name.', [
        '%name' => $entity_type_id, //$entity->label(),
      ]);
    }
  }

}
