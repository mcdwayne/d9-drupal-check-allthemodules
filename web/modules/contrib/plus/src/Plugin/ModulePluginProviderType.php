<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModulePluginProviderType.
 */
class ModulePluginProviderType extends BasePluginProviderType {

  /**
   * The Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ModulePluginProviderType constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler service.
   *
   * @todo Refactor in 8.6.x to use ModuleExtensionList.
   * @see https://www.drupal.org/node/2709919
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterDefinitions($hook, array &$definitions) {
    $this->moduleHandler->alter($hook, $definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespaces($name = NULL, $type = NULL) {
    // Immediately return if not the right type.
    if (isset($type) && $type !== $this->getType()) {
      return [];
    }

    $modules = $this->moduleHandler->getModuleList();
    if (isset($modules[$name])) {
      return $this->getExtensionNamespaces([$modules[$name]]);
    }
    return $this->getExtensionNamespaces($modules);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'module';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor in 8.6.x to use ModuleExtensionList.
   * @see https://www.drupal.org/node/2709919
   */
  public function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider);
  }

}
