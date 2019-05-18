<?php

namespace Drupal\notify_log\DataCollector;

use Drupal\webprofiler\DrupalDataCollectorInterface;
use Drupal\webprofiler\DataCollector\DrupalDataCollectorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class NotifyLogDataCollector.
 */
class NotifyLogDataCollector extends DataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait;

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response, \Exception $exception = NULL) {
  }

  /**
   * Increment count by new log entry.
   *
   * @param string $channel
   *   Channel name.
   */
  public function addLog($channel) {
    $this->data['notify_log_entry'][$channel] = isset($this->data['notify_log_entry'][$channel]) ? $this->data['notify_log_entry'][$channel] + 1 : 1;
  }

  /**
   * Twig callback to show all requested state keys.
   */
  public function getLogChannelsCount() {
    return empty($this->data['notify_log_entry']) ? 0 : array_sum($this->data['notify_log_entry']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Log Entries');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'notify_log';
  }

  /**
   * {@inheritdoc}
   */
  public function getPanelSummary() {
    return $this->t('Total: @variables', ['@variables' => $this->getLogChannelsCount()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon() {
    return 'iVBORw0KGgoAAAANSUhEUgAAABgAAAAcCAMAAABMOI/cAAAAolBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACgESU6AAAANXRSTlMAMvdMxqAjdlT73OS5y79H8e7nNmdO1IQb6rEnEtmmQzwsGA2th3tkHwfekHFta1yzl45uU05tvMsAAAEbSURBVCjPrdDJboMwEIBhY2wTB9uAidl3sq/d5v1frSmliUTSW/7LHD5pRhqELLWf30tFi8bspbHuxT7t/oAydG8R8I/iOQCO++cAgbN7DuBuHmGTXoHL5gGKxvO8S2aPMInRKySnzFiT7OwzQTRKJZkk04gijiXhAOATuhwOl1mlAuAI9MX2AfSaJUkjIVhZSRcKAASuF/qAaX+cV6gODoVRJdE30DOLANjbd9OW3PfxDfa1FwEcOyf8cqWVr2/gmlACN9tDHUZuvFv8Qr4UIu4cUhV16RQnIfoRdiw3csOsbaN4tEjath9W4UgpJbFWlZhz4Fq+ZcS9goYxzjkMDVMjFqvzbNJZxezf774WxMqZtBI/kAv8kMjRN38CJW7xh8NYAAAAAElFTkSuQmCC';
  }

}
