<?php

namespace Drupal\acquia_contenthub_publisher\Form;

use Drupal\acquia_contenthub_publisher\ContentHubExportQueue;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a form to Process items from the Content Hub Export Queue.
 */
class ContentHubExportQueueForm extends FormBase {

  /**
   * The Export Queue Service.
   *
   * @var \Drupal\acquia_contenthub_publisher\ContentHubExportQueue
   */
  protected $contentHubExportQueue;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_contenthub.export_queue_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ContentHubExportQueue $export_queue) {
    $this->contentHubExportQueue = $export_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_contenthub_publisher.acquia_contenthub_export_queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t('Instruct the content hub module to manage content export with a queue.'),
    ];

    $queue_count = intval($this->contentHubExportQueue->getQueueCount());

    $form['run_export_queue'] = [
      '#type' => 'details',
      '#title' => $this->t('Run Export Queue'),
      '#description' => '<strong>For development & testing use only!</strong><br /> Running the export queue from the UI can cause php timeouts for large datasets.
                         A cronjob to run the queue should be used instead.',
      '#open' => TRUE,
    ];
    $form['run_export_queue']['queue-list'] = [
      '#type' => 'item',
      '#title' => $this->t('Number of queue items in the Export Queue'),
      '#description' => $this->t('%num @items.', [
        '%num' => $queue_count,
        '@items' => $queue_count === 1 ? $this->t('item') : $this->t('items'),
      ]),
    ];
    $form['run_export_queue']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Export Items'),
      '#name' => 'run_export_queue',
    ];
    if ($queue_count > 0) {
      $form['run_export_queue']['purge_queue'] = [
        '#type' => 'item',
        '#title' => $this->t('Purge existing queues'),
        '#description' => $this->t('It is possible an existing queue has becomed orphaned, use this function to wipe all existing queues'),
      ];
      $form['run_export_queue']['purge'] = [
        '#type' => 'submit',
        '#value' => t('Purge'),
        '#name' => 'purge_export_queue',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queue_count = intval($this->contentHubExportQueue->getQueueCount());
    $trigger = $form_state->getTriggeringElement();
    switch ($trigger['#name']) {
      case 'run_export_queue':
        if (!empty($queue_count)) {
          $this->contentHubExportQueue->processQueueItems();
        }
        else {
          drupal_set_message($this->t('You cannot run the export queue because it is empty.'), 'warning');
        }
        break;

      case 'purge_export_queue':
        $this->contentHubExportQueue->purgeQueues();
        drupal_set_message($this->t('Purged all contenthub export queues.'));
        break;

      default:
        break;
    }
  }

}
