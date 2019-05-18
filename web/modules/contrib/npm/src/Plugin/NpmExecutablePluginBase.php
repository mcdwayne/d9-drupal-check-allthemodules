<?php

namespace Drupal\npm\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for node package manager plugins.
 */
abstract class NpmExecutablePluginBase extends PluginBase implements NpmExecutableInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * NpmExecutablePluginBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * Return the location for package.json.
   *
   * @return string
   */
  protected function getWorkingDirectory() {
    $dir = DRUPAL_ROOT . '/..';
    // TODO: Document this in api.php.
    $this->moduleHandler->alter('npm_working_dir', $dir);
    return $dir;
  }

}
