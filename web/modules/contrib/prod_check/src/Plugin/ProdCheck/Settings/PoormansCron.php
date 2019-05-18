<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Settings;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Poormans cron check.
 *
 * @ProdCheck(
 *   id = "poormans_cron",
 *   title = @Translation("Cron"),
 *   category = "settings",
 * )
 */
class PoormansCron extends ProdCheckBase {

  /**
   * The cron interval.
   */
  protected $cronInterval;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->cronInterval = $this->configFactory->get('system.cron')->get('threshold.autorun');
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->cronInterval === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t("Drupal's built in cron mechanism is disabled."),
      'description' => $this->generateDescription(
        $this->title(),
        'system.cron_settings'
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t("Drupal's built in cron mechanism is set to run every %interval.", array('%interval' => $this->dateFormatter->formatInterval($this->cronInterval))),
      'description' => $this->generateDescription(
        $this->title(),
        'system.cron_settings',
        'The %link interval should be disabled if you have also setup a crontab or scheduled task for this to avoid running the cron more often than you have planned to!'
      ),
    ];
  }

}
