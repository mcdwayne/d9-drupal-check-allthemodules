<?php

namespace Drupal\campaignmonitor_local\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class CampaignMonitorLocalQueueForm extends FormBase {
  /**
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('queue'), $container->get('plugin.manager.queue_worker'));
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'campaignmonitor_local_queue_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get('campaignmonitor_local_subscriptions');

    $text = 'Submitting this form will process the Subscriptions Queue which contains @number items.';
    $text .= '  The queue is created using Drupal Queue so that if this batch fails for some reason all you need to do
    is to run it again and it continues from where it left off.';

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t($text, ['@number' => $queue->numberOfItems()])
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process queue'),
      '#button_type' => 'primary'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('campaignmonitor_local.batch');
    $form_state->setRedirectUrl($url);
  }

}
