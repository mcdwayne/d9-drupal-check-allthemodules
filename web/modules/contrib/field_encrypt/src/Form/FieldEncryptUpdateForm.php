<?php

namespace Drupal\field_encrypt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the field_encrypt field update page.
 */
class FieldEncryptUpdateForm extends FormBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The QueueWorker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Constructs a new FieldEncryptUpdateForm.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The QueueWorker manager.
   */
  public function __construct(QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_encrypt_field_update';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get('cron_encrypted_field_update');
    $num_items = $queue->numberOfItems();

    $status_message = $message = $this->formatPlural($num_items, 'There is one field queued for encryption updates.', 'There are @count fields queued for encryption updates.');
    $form['status'] = array(
      '#markup' => '<p>' . $status_message . '</p>',
    );

    if ($num_items > 0) {
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Update encryption on existing fields'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => t('Updating field encryption'),
      'operations' => array(
        array(array(get_class($this), 'processBatch'), array()),
      ),
      'finished' => array(get_class($this), 'finishBatch'),
    ];
    batch_set($batch);
  }

  /**
   * Processes batch updating of encryption fields.
   *
   * @param array $context
   *   The batch API context.
   */
  public static function processBatch(&$context) {
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $config = \Drupal::config('field_encrypt.settings');

    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('cron_encrypted_field_update');
    $num_items = $queue->numberOfItems();

    /** @var QueueWorkerInterface $queue_worker */
    $queue_worker = $queue_manager->createInstance('cron_encrypted_field_update');

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $num_items;
    }

    // Process entities in groups. Default batch size is 5.
    for ($i = 1; $i <= $config->get('batch_size'); $i++) {
      if ($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);

          $context['results']['items'][] = $item->data['entity_id'];
          $message = t('Updating @field_name on @entity_type with ID @entity_id', array(
            '@field_name' => $item->data['field_name'],
            '@entity_type' => $item->data['entity_type'],
            '@entity_id' => $item->data['entity_id'],
          ));
          $context['message'] = $message;
          $context['sandbox']['progress']++;
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
        }
        catch (\Exception $e) {
          watchdog_exception('field_encrypt', $e);
          if (!isset($context['results']['errors'])) {
            $context['results']['errors'] = array();
          }
          $context['results']['errors'][] = $e->getMessage();
        }
      }
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finish batch encryption updates of fields.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results['items']), 'One field updated.', '@count fields updated.');
      drupal_set_message($message);
    }
    else {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
        }
      }
    }
  }

}
