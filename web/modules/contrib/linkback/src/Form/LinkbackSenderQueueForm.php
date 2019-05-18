<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * The class for Linkback sender queue form. Based on FormBase.
 */
class LinkbackSenderQueueForm extends FormBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The quqeue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      QueueFactory $queue,
      QueueWorkerManagerInterface $queue_manager,
      ConfigFactoryInterface $config_factory,
      Messenger $messenger
  ) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Gets the cron or manual queue.
   *
   * @return string
   *   The name of the QueueFactory.
   */
  protected function getQueue() {
    $config = $this->configFactory->get('linkback.settings');
    return $config->get('use_cron_send') ? 'cron_linkback_sender' : 'manual_linkback_sender';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_sender_queue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Submitting this form will process the "@queue" queue which contains @number items.', ['@queue' => $this->getQueue(), '@number' => $queue->numberOfItems()]),
    ];

    $form['erase_if_fails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Erase the failing item if it cannot be processed(option not saved)'),
      '#description' => $this->t('The error will be logged, save item again to retry'),
      '#default_value' => False,
    ];
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process queue'),
      '#button_type' => 'primary',
      '#disabled' => $queue->numberOfItems() < 1,
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete queue'),
      '#button_type' => 'secondary',
      '#submit' => ['::deleteQueue'],
      '#disabled' => $queue->numberOfItems() < 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());
    $queue->deleteQueue();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = $this->queueManager->createInstance($this->getQueue());

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        watchdog_exception('linkback', $e, '%type: @message in %function (line %line of %file). While processing entity(%bundle):%id ', ['%bundle' => $item->data->bundle(), '%id' => $item->data->id()]);
        if($form_state->getValue('erase_if_fails')){
          $queue->deleteItem($item);
          $this->messenger->addError(t('An error occurred while processing @link, the processing of this item will not be retried. Check the log.', ['@link' => $item->data->toLink()->toString()]));
        }
        else{
          $queue->releaseItem($item);
          $this->messenger->addError(t('An error occurred while processing @link, the processing of this item will be retried next time. Check the log.', ['@link' => $item->data->toLink()->toString()]));
        }
        break;
      }
    }
  }

}
