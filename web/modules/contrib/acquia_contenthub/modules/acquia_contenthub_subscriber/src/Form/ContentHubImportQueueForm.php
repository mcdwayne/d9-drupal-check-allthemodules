<?php

namespace Drupal\acquia_contenthub_subscriber\Form;

use Drupal\acquia_contenthub_subscriber\ContentHubImportQueue;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentHubImportQueueForm.
 *
 * @package Drupal\acquia_contenthub\Form
 */
class ContentHubImportQueueForm extends FormBase {

  /**
   * The Import Queue Service.
   *
   * @var \Drupal\acquia_contenthub_subscriber\ContentHubImportQueue
   */
  protected $contentHubImportQueue;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContentHubImportQueue $import_queue) {
    $this->contentHubImportQueue = $import_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_contenthub_subscriber.acquia_contenthub_import_queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_contenthub.import_queue_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t('Instruct the content hub module to manage content syndication with a queue.'),
    ];

    $form['run_import_queue'] = [
      '#type' => 'details',
      '#title' => $this->t('Run the import queue'),
      '#description' => '<strong>For development & testing use only!</strong><br /> Running the import queue from the UI can cause php timeouts for large datasets.
                         A cronjob to run the queue should be used instead.',
      '#open' => TRUE,
    ];

    $form['run_import_queue']['actions'] = [
      '#type' => 'actions',
    ];

    $queue_count = $this->contentHubImportQueue->getQueueCount();

    $form['run_import_queue']['queue_list'] = [
      '#type' => 'item',
      '#title' => $this->t('Number of items in the import queue'),
      '#description' => $this->t('%num @items', [
        '%num' => $queue_count,
        '@items' => $queue_count == 1 ? 'item' : 'items',
      ]),
    ];

    $form['run_import_queue']['actions']['run'] = [
      '#type' => 'submit',
      '#name' => 'run_import_queue',
      '#value' => $this->t('Run import queue'),
      '#op' => 'run',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queue_count = intval($this->contentHubImportQueue->getQueueCount());
    $trigger = $form_state->getTriggeringElement();

    switch ($trigger['#name']) {
      case 'run_import_queue':
        if (!empty($queue_count)) {
          $this->contentHubImportQueue->process();
        }
        else {
          drupal_set_message($this->t('You cannot run the import queue because it is empty.'), 'warning');
        }
        break;
    }
  }

}
