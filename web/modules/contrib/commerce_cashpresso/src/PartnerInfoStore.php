<?php

namespace Drupal\commerce_cashpresso;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

/**
 * Default partner info store implementation.
 */
class PartnerInfoStore implements PartnerInfoStoreInterface {

  /**
   * The key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueStore;

  /**
   * Constructs a new PartnerInfoStore object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The expirable key/value factory.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expirable_factory) {
    $this->keyValueStore = $key_value_expirable_factory->get('commerce_cashpresso');
  }

  /**
   * {@inheritdoc}
   */
  public function getPartnerInfo($merchant_id = '') {
    $key = $merchant_id ? sprintf('%s.partner_info', $merchant_id) : 'partner_info';
    return $this->keyValueStore->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function clearPartnerInfo($merchant_id = '') {
    $key = $merchant_id ? sprintf('%s.partner_info', $merchant_id) : 'partner_info';
    $this->keyValueStore->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setPartnerInfo(PartnerInfo $partner_info, $merchant_id = '') {
    $key = $merchant_id ? sprintf('%s.partner_info', $merchant_id) : 'partner_info';
    $this->keyValueStore->setWithExpire($key, $partner_info, 86400);
  }

}
