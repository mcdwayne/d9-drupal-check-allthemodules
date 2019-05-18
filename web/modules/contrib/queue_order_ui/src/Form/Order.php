<?php

namespace Drupal\queue_order_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Order.
 *
 * @package Drupal\queue_order_ui\Form
 */
class Order extends ConfigFormBase {

  const CONFIG_NAME = 'queue_order.settings';

  /**
   * Queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManager;

  /**
   * Constructs a Queue Order form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   Queue worker manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueWorkerManagerInterface $queue_worker_manager) {
    parent::__construct($config_factory);
    $this->queueWorkerManager = $queue_worker_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'queue_order_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $order_list = $this->config(self::CONFIG_NAME)->get('order');
    $form[$this->getFormId()] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        $this->t('Default weight'),
        $this->t('Weight'),
        /*$this->t('Operations'),*/
      ],
      '#empty' => $this->t('There are no queues yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => "{$this->getFormId()}-order-weight",
        ],
      ],
    ];
    foreach ($this->queueWorkerManager->getDefinitions() as $name => $definition) {
      $weight = empty($order_list[$name]) ? $definition['cron']['weight'] : $order_list[$name];
      $form[$this->getFormId()][$name]['#attributes']['class'][] = 'draggable';
      $form[$this->getFormId()][$name]['#weight'] = $weight;
      $form[$this->getFormId()][$name]['label'] = [
        '#plain_text' => $definition['title'],
      ];
      $form[$this->getFormId()][$name]['id'] = [
        '#plain_text' => $name,
      ];
      $form[$this->getFormId()][$name]['default_weight'] = [
        '#plain_text' => $definition['cron']['weight'],
      ];
      $form[$this->getFormId()][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $definition['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ["{$this->getFormId()}-order-weight"]],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_configs = [];
    $form_values = $form_state->getValue($this->getFormId());
    foreach ($form_values as $name => $array) {
      $new_configs[$name] = intval($array['weight']);
    }
    $this->config(self::CONFIG_NAME)
      ->set('order', $new_configs)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
