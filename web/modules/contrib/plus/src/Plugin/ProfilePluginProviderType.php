<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProfilePluginProviderType.
 */
class ProfilePluginProviderType extends BasePluginProviderType {

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
   * @todo Refactor in 8.6.x to use ProfileExtensionList.
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

    // @todo Remove filtering in 8.6.x when ProfileExtensionList is used.
    // @see https://www.drupal.org/node/2709919
    $profiles = array_filter($this->moduleHandler->getModuleList(), function ($extension) {
      return $this->isProfile($extension);
    });

    if (isset($profiles[$name])) {
      return $this->getExtensionNamespaces([$profiles[$name]]);
    }
    return $this->getExtensionNamespaces($profiles);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'profile';
  }

  /**
   * Determines whether the extension type is "profile".
   *
   * @param \Drupal\Core\Extension\Extension|string $extension
   *   An Extension object.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @todo Remove in 8.6.x when ProfileExtensionList is used.
   * @see https://www.drupal.org/node/2709919
   */
  protected function isProfile($extension = NULL) {
    if (is_string($extension)) {
      try {
        $extension = $this->moduleHandler->getModule($extension);
      }
      catch (\InvalidArgumentException $e) {
        $extension = FALSE;
      }
    }
    return $extension && $extension->getType() === 'profile';
  }


  /**
   * {@inheritdoc}
   *
   * @todo Refactor in 8.6.x to use ProfileExtensionList.
   * @see https://www.drupal.org/node/2709919
   */
  public function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) && $this->isProfile($provider);
  }

}
