<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents the form for processing the Peytz Mail queue manually.
 */
class PeytzMailSubscribeProcessSettingsForm extends FormBase {

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Queue Worker Manager Interface.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * State Interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Date Formatter Interface.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager, ConfigFactoryInterface $config_factory, StateInterface $state, DateFormatterInterface $date_formatter) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peytz_mail_subscription_queue_process_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get('peytz_mail_subscribe_worker_cron');

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Peytz mail status information'),
      '#open' => TRUE,
    ];
    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('Submitting this form will process the subscription queue which contains @number pending subscribers.', ['@number' => $queue->numberOfItems()]),
    ];

    $next_cron_run = $this->configFactory->get('automated_cron.settings')->get('interval') + $this->state->get('system.cron_last');

    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('Cron will next execute the first time cron runs after %time', [
        '%time' => $this->dateFormatter->formatTimeDiffUntil($next_cron_run),
      ]
      ),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#disabled' => $queue->numberOfItems() <= 0,
      '#value' => $queue->numberOfItems() > 0 ? $this->t('Process subscription queue') : $this->t('No items in queue'),
      '#button_type' => 'primary',
    ];

    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#submit' => ['::deleteQueue'],
      '#disabled' => $queue->numberOfItems() <= 0,
      '#value' => $this->t('Delete queue'),
      '#description' => $this->t('Delete queue and all items in the queue.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get('peytz_mail_subscribe_worker_cron');
    $queue_worker = $this->queueManager->createInstance('peytz_mail_subscribe_worker_cron');

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        drupal_set_message($e->getMessage());
        break;
      }
      catch (\Exception $e) {
        $queue->releaseItem($item);
        drupal_set_message($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Delete queue and all items in the queue.
   */
  public function deleteQueue(array &$form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get('peytz_mail_subscribe_worker_cron');
    $queue->deleteQueue();
    drupal_set_message($this->t('Queue and all items in the queue are deleted successfully.'));
  }

}
