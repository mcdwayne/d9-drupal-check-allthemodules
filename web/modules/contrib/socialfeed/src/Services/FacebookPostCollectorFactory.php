<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class FacebookPostCollectorFactory.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollectorFactory {

  /**
   * Default Facebook application id.
   *
   * @var string
   */
  protected $defaultAppId;

  /**
   * Default Facebook application secret.
   *
   * @var string
   */
  protected $defaultAppSecret;

  /**
   * Page name.
   *
   * @var string
   */
  protected $pageName;

  /**
   * FacebookPostCollector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.facebooksettings');
    $this->defaultAppId = $config->get('app_id');
    $this->defaultAppSecret = $config->get('secret_key');
    $this->defaultUserToken = $config->get('user_token');
    $this->pageName = $config->get('page_name');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($appId, $appSecret, $userToken, $pageName) {
    return new FacebookPostCollector(
      $appId ?: $this->defaultAppId,
      $appSecret ?: $this->defaultAppSecret,
      $userToken ?: $this->defaultUserToken,
      $this->pageName = $pageName,
      NULL
    );
  }

}
