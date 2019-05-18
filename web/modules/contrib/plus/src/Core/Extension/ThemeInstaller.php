<?php

namespace Drupal\plus\Core\Extension;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\plus\Events\ThemeEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstaller as CoreThemeInstaller;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\plus\Events\ThemeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * {@inheritdoc}
 */
class ThemeInstaller extends CoreThemeInstaller {

  /**
   * The Event Dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, ConfigInstallerInterface $config_installer, ModuleHandlerInterface $module_handler, ConfigManagerInterface $config_manager, AssetCollectionOptimizerInterface $css_collection_optimizer, RouteBuilderInterface $route_builder, LoggerInterface $logger, StateInterface $state, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($theme_handler, $config_factory, $config_installer, $module_handler, $config_manager, $css_collection_optimizer, $route_builder, $logger, $state);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $theme_list, $install_dependencies = TRUE) {
    // Install (before).
    $event = new ThemeEvent($theme_list);
    $this->eventDispatcher->dispatch(ThemeEvents::INSTALL, $event);
    if ($event->isPropagationStopped()) {
      return FALSE;
    }

    // Invoke original core method.
    $result = parent::install($theme_list, $install_dependencies);

    // Installed (after).
    $this->eventDispatcher->dispatch(ThemeEvents::INSTALLED, $event);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $theme_list) {
    // Uninstall (before).
    $event = new ThemeEvent($theme_list);
    $this->eventDispatcher->dispatch(ThemeEvents::UNINSTALL, $event);
    if ($event->isPropagationStopped()) {
      return;
    }

    // Invoke original core method.
    parent::uninstall($theme_list);

    // Uninstalled (after).
    $this->eventDispatcher->dispatch(ThemeEvents::UNINSTALLED, $event);
  }

}
