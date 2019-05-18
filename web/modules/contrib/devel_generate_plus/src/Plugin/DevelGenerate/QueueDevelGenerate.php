<?php

namespace Drupal\devel_generate_plus\Plugin\DevelGenerate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ContentDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "queue",
 *   label = @Translation("queue"),
 *   description = @Translation("Generate queue items."),
 *   url = "queue",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 100,
 *     "queue_num" = 1,
 *     "kill" = FALSE
 *   }
 * )
 */
class QueueDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueFactory $queue_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queueService = $queue_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($values['kill']) {
      $this->deleteQueueItems();
      $this->setMessage($this->t('Deleted existing queue items.'));
    }

    for ($i=1; $i<=$values['queue_num']; $i++) {
      $queue = $this->queueService->get('queue_devel_generate:' . $i);
      for ($j=0; $j<$values['num']; $j++) {
        $item = new \stdClass();
        $item->data = [
          'id' => $j,
          'string' => 'Item ' . $j,
        ];
        $queue->createItem($item);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['num'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of queue items?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    );

    $form['queue_num'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of different queues to create?'),
      '#description' => $this->t('A maximum of 3 is allowed.'),
      '#default_value' => $this->getSetting('queue_num'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 3,
    );

    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing queue items before generating new ones.'),
      '#default_value' => $this->getSetting('kill'),
    );

    return $form;
  }

  /**
   * Delete any queue items in any of our dummy queues.
   */
  protected function deleteQueueItems() {
    for ($i=1; $i<=3; $i++) {
      $queue = $this->queueService->get('queue_devel_generate:' . $i);
      $queue->deleteQueue();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args, $options = []) {}

}
