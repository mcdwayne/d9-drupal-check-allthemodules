<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\plus\Utility\ArrayObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ThemePluginProviderType.
 */
class ThemePluginProviderType extends BasePluginProviderType {

  /**
   * {@inheritdoc}
   */
  protected $cacheTags = ['theme_registry'];

  /**
   * The Theme Handler service.
   *
   * @var \Drupal\plus\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * The Theme Manager service.
   *
   * @var \Drupal\plus\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * ThemePluginProviderType constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The Theme Handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The Theme Manager service.
   *
   * @todo Refactor in 8.6.x to use ThemeExtensionList.
   * @see https://www.drupal.org/node/2709919
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterDefinitions($hook, array &$definitions) {
    $this->themeManager->alter($hook, $definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespaces($name = NULL, $type = NULL, $ancestry = TRUE) {
    // Immediately return if not the right type.
    if (isset($type) && $type !== $this->getType()) {
      return ArrayObject::create();
    }

    if (!empty($name) && $ancestry) {
      return $this->getExtensionNamespaces($this->themeHandler->getAncestry($name));
    }

    $themes = $this->themeHandler->listInfo();
    if (isset($themes[$name])) {
      return $this->getExtensionNamespaces([$themes[$name]]);
    }

    return $this->getExtensionNamespaces($themes);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'theme';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor in 8.6.x to use ThemeExtensionList.
   * @see https://www.drupal.org/node/2709919
   */
  public function providerExists($provider) {
    return $this->themeHandler->themeExists($provider);
  }

}
