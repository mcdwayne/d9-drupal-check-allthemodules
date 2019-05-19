<?php

namespace Drupal\whitelabel\Resolver;

use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\whitelabel\WhiteLabelProviderInterface;

/**
 * Returns the store for the owning user of an active White label.
 */
class WhiteLabelStoreResolver implements StoreResolverInterface {

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The white label provider.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * Constructs a new WhiteLabelStoreResolver object.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->whiteLabelProvider = $white_label_provider;
    $this->configFactory = $config_factory;
    $this->storage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    // This resolver can be enabled in the white label settings.
    $enabled = $this->configFactory->get('whitelabel.settings')->get('store_resolver');

    if ($enabled && $whitelabel = $this->whiteLabelProvider->getWhiteLabel()) {
      $uid = $whitelabel->getOwnerId();

      /** @var \Drupal\commerce_store\Entity\StoreInterface[] $user_stores */
      $user_stores = $this->storage->loadByProperties(['uid' => $uid]);

      if ($user_stores) {
        // Return the first.
        $store = reset($user_stores);
        return $store;
      }
    }
  }
}
