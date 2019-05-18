<?php

namespace Drupal\dropshark\Fingerprint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DrupalKernelInterface;

/**
 * Class FingerprintDefault.
 */
class FingerprintDefault implements FingerprintInterface {

  /**
   * The computed fingerprint.
   *
   * @var string
   */
  protected $fingerprint;

  /**
   * The kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The site ID.
   *
   * @var string
   */
  protected $siteId;

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The kernel.
   */
  public function __construct(ConfigFactoryInterface $configFactory, DrupalKernelInterface $kernel) {
    $config = $configFactory->get('dropshark.settings');
    $this->siteId = $config->get('site_id');
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function getFingerprint() {
    if (!$this->fingerprint) {
      $fingerprint['host'] = $_SERVER['SERVER_NAME'];
      $fingerprint['app_root'] = $this->kernel->getAppRoot();
      $fingerprint['site_path'] = $this->kernel->getSitePath();
      $this->fingerprint = base64_encode(json_encode($fingerprint));
    }

    return $this->fingerprint;
  }

}
