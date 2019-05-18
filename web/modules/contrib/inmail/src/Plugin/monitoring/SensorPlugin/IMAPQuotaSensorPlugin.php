<?php

namespace Drupal\inmail\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Plugin\inmail\Deliverer\ImapFetcher;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors IMAP quota of Inmail fetchers.
 *
 * @todo add tests for this sensor.
 *
 * @SensorPlugin(
 *   id = "inmail_monitoring_imap_quota",
 *   label = @Translation("Inmail IMAP Quota"),
 *   description = @Translation("Monitors IMAP quota of Inmail fetchers."),
 *   addable = TRUE
 * )
 */
class IMAPQuotaSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Find active deliverers.
    $deliverer_ids = \Drupal::entityQuery('inmail_deliverer')->condition('status', TRUE)->execute();
    /** @var \Drupal\inmail\Entity\DelivererConfig[] $deliverers */
    $deliverers = \Drupal::entityTypeManager()->getStorage('inmail_deliverer')->loadMultiple($deliverer_ids);

    $imap_fetchers = [];
    foreach ($deliverers as $deliverer) {
      // List IMAP fetchers only.
      if ($deliverer->getPluginInstance() instanceof ImapFetcher) {
        $imap_fetchers[$deliverer->id()] = $deliverer->label();
      }
    }

    $has_fetchers = (bool) $imap_fetchers;
    $form['imap_fetcher'] = array(
      '#type' => 'select',
      '#options' => $imap_fetchers,
      '#title' => t('IMAP Fetchers'),
      '#description' => t('Select a fetcher to track its IMAP quota.'),
      '#default_value' => $this->sensorConfig->getSetting('imap_fetcher'),
      '#access' => $has_fetchers,
    );
    if (!$has_fetchers) {
      drupal_set_message(t('There are no IMAP fetchers. Please <a href=":url">add</a> one.', array(':url' => '/admin/config/system/inmail/deliverers/add')), 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $sensor_config = $form_state->getFormObject()->getEntity();
    parent::submitConfigurationForm($form, $form_state);

    $sensor_config->settings['imap_fetcher'] = $form_state->getValue(['settings', 'imap_fetcher']);
  }

    /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $fetcher_name = $this->sensorConfig->settings['imap_fetcher'];
    $imap_fetcher = DelivererConfig::load($fetcher_name);
    if (!$imap_fetcher) {
      $result->addStatusMessage('Missing IMAP fetcher @fetcher', [
        '@fetcher' => $fetcher_name,
      ]);
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      return;
    }

    if ($quota = $imap_fetcher->getPluginInstance()->getQuota()) {
      $usage = $quota['STORAGE']['usage'];
      $limit = $quota['STORAGE']['limit'];
      $percentage = round($usage/$limit * 100);
      $result->setValue($percentage);
      $result->addStatusMessage('@usage/@limit used by @fetcher', [
        '@usage' => $usage,
        '@limit' => $limit,
        '@fetcher' => $imap_fetcher->id()
      ]);
    }
    else {
      $result->addStatusMessage('No quota information for @fetcher fetcher.', ['@fetcher' => $imap_fetcher->id()]);
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
    }
  }
}
