<?php

namespace Drupal\warmer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\warmer\HookImplementations;
use Drupal\warmer\Plugin\WarmerPluginBase;
use Drupal\warmer\Plugin\WarmerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * A form to manually enqueue warming operations.
 */
final class EnqueueForm extends FormBase {

  /**
   * The warmer plugin manager.
   *
   * @var \Drupal\warmer\Plugin\WarmerPluginManager
   */
  private $warmerManager;

  /**
   * The queue manager.
   *
   * @var \Drupal\warmer\QueueManager
   */
  private $queueManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\warmer\Form\EnqueueForm $form_object */
    $form_object = parent::create($container);
    $form_object->setWarmerManager($container->get('plugin.manager.warmer'));
    $form_object->setQueueManager($container->get('warmer.queue_manager'));
    $form_object->setMessenger($container->get('messenger'));

    return $form_object;
  }

  /**
   * Set the warmer manager.
   *
   * @param \Drupal\warmer\Plugin\WarmerPluginManager $warmer_manager
   *   The plugin manager.
   */
  public function setWarmerManager(WarmerPluginManager $warmer_manager) {
    $this->warmerManager = $warmer_manager;
  }

  /**
   * Set the queue manager.
   *
   * @param \Drupal\warmer\QueueManager $queue_manager
   *   The queue manager.
   */
  public function setQueueManager($queue_manager) {
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'warmer.enqueue';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#type' => 'item',
      '#description' => $this->t('This page allows you to enqueue cache warming operations manually. This will put the cache warming operations in a queue. If you want to actually execute them right away you can force processing the queue. A good way to do that is by installing the <a href=":url">Queue UI</a> module or using Drush. This module will provide a UI to process an entire queue.', [':url' => 'https://www.drupal.org/project/queue_ui']),
    ];
    $options = array_map(function (array $definition) {
      return [
        'title' => $definition['label'],
        'description' => $definition['description'],
      ];
    }, $this->warmerManager->getDefinitions());
    $header = [
      'title' => $this->t('Warmer'),
      'description' => $this->t('Description'),
    ];
    $form['warmers'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No warmers available. Enable the Entity Warmer submodule, or try installing extending modules like JSON:API Boost.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Warm Caches'),
      '#button_type' => 'primary',
    ];

    try {
      // If Queue UI exists, link to it.
      $this->getUrlGenerator()->generateFromRoute('queue_ui.overview_form');
      $form['queues'] = [
        '#type' => 'link',
        '#title' => $this->t('List of queues'),
        '#url' => Url::fromRoute('queue_ui.overview_form'),
      ];
    }
    catch (RouteNotFoundException $e) {}

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $warmer_ids = $form_state->getValue('warmers');
    $warmers = $this->warmerManager->getWarmers($warmer_ids);
    $count_list = array_map(function (WarmerPluginBase $warmer) {
      $count = 0;
      $ids = [NULL];
      while ($ids = $warmer->buildIdsBatch(end($ids))) {
        $this->queueManager->enqueueBatch(HookImplementations::class . '::warmBatch', $ids, $warmer);
        $count += count($ids);
      }
      return $count;
    }, $warmers);
    $total = array_sum($count_list);
    $this->messenger->addStatus(
      $this->t('@total items enqueued for cache warming.', ['@total' => $total])
    );
  }

}
