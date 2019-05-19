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
class SendMultipleConfirmForm extends ConfirmFormBase {

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
  protected $tempStorageName = 'smartling_node_operations_send';

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
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->submissionStorage = $submission_storage;
    $this->languageManager = $language_manager;
    $this->queue = $queue;
    $this->config = $config;
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

    $languages = $this->languageManager->getLanguages();
    // Hide default site language from mapping.
    unset($languages[$this->languageManager->getDefaultLanguage()->getId()]);

    $config = $this->config->get('account_info.enabled_languages');
    $locales = [];
    foreach ($config as $language_code) {
      $locales[$language_code] = $languages[$language_code]->getName();
    }

    $form['locales'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select locales'),
      '#options' => $locales,
      '#default_value' => array_keys($locales),
      '#required' => TRUE,
    );

    $items = [];
    foreach ($entities as $entity) {
      $items[$entity->id()] = $entity->label();
    }
    $form['items'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Entities to send'),
      '#items' => $items,
    ];

    return parent::buildForm($form, $form_state);
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
    if ($form_state->getValue('confirm')) {
      $entities = $this->getEntities($this->entityIds);
      $locales = $form_state->getValue('locales');


      $is_batch = $this->config->get('expert.async_mode');//$form_state->getValue('batch');
      $operations = [];

      foreach ($entities as $entity) {
        // Make sure all submissions exists.
        foreach ($locales as $language_code) {
          $submission = $this->submissionStorage->loadByProperties([
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
            'target_language' => $language_code,
          ]);
          if (!$submission) {
            $submission = SmartlingSubmission::getFromDrupalEntity($entity, $language_code);
            $submission->save();
          }
          else {
            // Use first loaded submission,
            $submission = reset($submission);
          }
        }
        if (isset($submission)) {
          $item_data = [
            'entity_type' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
            'file_name' => $submission->getFileName(),
            'locales' => $locales,
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
          'title' => t('Sending to Smartling'),
          'operations' => $operations,
          'finished' => [get_class($this), 'finishBatch'],
        ];
        batch_set($batch);
      }
      else {
        $form_state->setRedirect('system.admin_content');
      }
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
    $entity = $entity_type_manager
      ->getStorage($entity_type_id)
      ->load($entity_id);
    if (!$entity) {
      $context['results']['errors'][] = t('Entity @entity_type:@entity_id not found', [
        '@entity_type' => $entity_type_id,
        '@entity_id' => $entity_id,
      ]);
    }
    elseif ($entity_type_manager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $entity_type_manager->getHandler($entity_type_id, 'smartling');
      if ($handler->uploadTranslation($entity, $data['file_name'], $data['locales'])) {
        $context['results']['count']++;
      }
      else {
        $context['results']['errors'][] = new FormattableMarkup('Error uploading %name', [
          '%name' => $entity->label(),
        ]);
      }
      $context['message'] = new FormattableMarkup('Processed %name.', [
        '%name' => $entity->label(),
      ]);
    }
    else {
      $context['message'] = new FormattableMarkup('Skipped %name.', [
        '%name' => $entity->label(),
      ]);
    }
  }

  /**
   * Finish batch.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
        }
        drupal_set_message(\Drupal::translation()
          ->translate('Entities were sent with errors.'), 'warning');
      }
      drupal_set_message(\Drupal::translation()
        ->formatPlural($results['count'], 'One entity has been sent successfully.', '@count entities have been sent successfully.'));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $message = \Drupal::translation()
        ->translate('An error occurred while sending.');
      drupal_set_message($message, 'error');
    }
  }
}
