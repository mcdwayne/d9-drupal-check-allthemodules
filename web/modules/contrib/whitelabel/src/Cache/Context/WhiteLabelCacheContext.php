<?php

namespace Drupal\whitelabel\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\whitelabel\WhiteLabelProviderInterface;

/**
 * Defines the WhiteLabelCacheContext service, for "per white label" caching.
 */
class WhiteLabelCacheContext implements CacheContextInterface {

  /**
   * Holds the white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * WhiteLabelCacheContext constructor.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider) {
    $this->whiteLabelProvider = $white_label_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('White label');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $white_label = $this->whiteLabelProvider->getWhiteLabel();
    return !empty($white_label) ? $white_label->id() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
