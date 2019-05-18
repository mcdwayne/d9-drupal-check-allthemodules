<?php

namespace Drupal\inmail\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors the incoming mails.
 *
 * @SensorPlugin(
 *   id = "inmail_incoming_mails",
 *   label = @Translation("Incoming mails"),
 *   description = @Translation("Provides details about the incoming mails."),
 *   addable = TRUE
 * )
 */
class IncomingMailsSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    // Sets the deliverers to track.
    $deliverers = [];
    if ($this->sensorConfig->getSetting('deliverers')) {
      $deliverer_ids = $this->sensorConfig->getSetting('deliverers');
      /** @var \Drupal\inmail\Entity\DelivererConfig[] $deliverers */
      $deliverers = \Drupal::entityTypeManager()->getStorage('inmail_deliverer')->loadMultiple($deliverer_ids);
    }
    else {
      // Find active deliverers.
      $deliverers = \Drupal::entityTypeManager()->getStorage('inmail_deliverer')->loadByProperties(['status' => TRUE]);
    }

    $total = 0;
    foreach ($deliverers as $deliverer) {
      if ($this->sensorConfig->getSetting('count_type') == 'processed') {
        $total += $deliverer->getPluginInstance()->getProcessedCount();
      }
      else if ($deliverer->getPluginInstance() instanceof FetcherInterface){
        $total += $deliverer->getPluginInstance()->getUnprocessedCount();
      }
    }
    $sensor_result->setValue($total);
  }

  /**
   * Returns the deliverers according to the count type setting.
   *
   * @return array
   *   Array of deliverer ids.
   */
  public function getDeliverers() {
    // Find active deliverers.
    $deliverers = \Drupal::entityTypeManager()->getStorage('inmail_deliverer')->loadByProperties(['status' => TRUE]);

    if ($this->sensorConfig->getSetting('count_type') == 'unprocessed') {
      // Find active fetchers.
      $fetchers_ids = [];
      foreach ($deliverers as $deliverer){
        if ($deliverer->getPluginInstance() instanceof FetcherInterface) {
          $fetchers_ids[$deliverer->id()] = $deliverer->label();
        }
      }
      return $fetchers_ids;
    }
    else {
      $deliverer_ids = [];
      foreach ($deliverers as $deliverer) {
        $deliverer_ids[$deliverer->id()] = $deliverer->label();
      }
      return $deliverer_ids;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $count_type = [
      'processed' => $this->t('Processed'),
      'unprocessed' => $this->t('Unprocessed'),
    ];
    $default_count_type = $this->sensorConfig->getSetting('count_type');
    // If the filter deliverers ajax has been triggered, store the new count
    // type to display in the UI.
    if ($form_state->hasValue('settings') && !($form_state->getValue('settings')['count_type'] == $default_count_type)) {
      $default_count_type = $form_state->getValue('settings')['count_type'];
      $this->sensorConfig->settings['count_type'] = $default_count_type;
    }
    $options = $this->getDeliverers();

    $form['count_type'] = [
      '#type' => 'select',
      '#options' => $count_type,
      '#title' => $this->t('Count'),
      '#required' => TRUE,
      '#description' => $this->t('Messages to track. Defaults to unprocessed.'),
      '#default_value' => $default_count_type,
      '#ajax' => [
        'callback' => '::ajaxReplacePluginSpecificForm',
        'wrapper' => 'monitoring-sensor-plugin',
      ],
    ];

    $form['deliverers'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Deliverers'),
      '#required' => FALSE,
      '#description' => $this->t('Deliverers to track. If none selected, defaults to all active deliverers.'),
      '#default_value' => $this->sensorConfig->getSetting('deliverers'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $deliverers = array_keys(array_filter($form_state->getValue(['settings', 'deliverers'])));
    $this->sensorConfig->settings['count_type'] = $form_state->getValue(['settings', 'count_type']);
    $this->sensorConfig->settings['deliverers'] = $deliverers;
  }

}
