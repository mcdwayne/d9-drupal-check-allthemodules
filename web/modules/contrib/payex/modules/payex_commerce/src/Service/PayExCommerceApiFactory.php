<?php

namespace Drupal\payex_commerce\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\payex\Service\PayExApiFactory;

/**
 * Class PayExCommerceApiFactory
 *
 * Factory for creating instances of PayExCommerceApi
 */
class PayExCommerceApiFactory {

  /**
   * The PayExAPI factory class.
   *
   * @var PayExApiFactory
   */
  protected $apiFactory;

  /**
   * The Drupal config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cnstructs a PayExApiFactory class.
   *
   * @param PayExApiFactory $apiFactory
   *   The PayExAPI factory class.
   * @param ConfigFactoryInterface $configFactory
   *   The Drupal config factory.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(PayExApiFactory $apiFactory, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->apiFactory = $apiFactory;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets an instance of PayExApi class with a specific setting
   *
   * @param string $id
   *   The id of the setting to use for the PayExApi class.
   *
   * @return bool|PayExCommerceApi
   *   Instance of a PayExCommerceApi class ready to use or FALSE if config doesn't exist.
   */
  public function get($id) {
    $api = $this->apiFactory->get($id);
    if (!$api) {
      return FALSE;
    }
    return new PayExCommerceApi($api, $this->configFactory, $this->entityTypeManager);
  }
}
