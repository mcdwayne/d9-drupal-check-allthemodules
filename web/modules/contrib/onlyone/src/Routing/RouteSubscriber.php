<?php

namespace Drupal\onlyone\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;

/**
 * Class RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The local action manager instance.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LocalActionManagerInterface $local_action_manager) {
    $this->configFactory = $config_factory;
    $this->localActionManager = $local_action_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Cleaning the action link cache.
    $this->localActionManager->clearCachedDefinitions();
    // Removing the onlyone.add_page route if is not needed and exists.
    if (!$this->configFactory->get('onlyone.settings')->get('onlyone_new_menu_entry') && $collection->get('onlyone.add_page')) {
      $collection->remove('onlyone.add_page');
    }
  }

}
