<?php

namespace Drupal\plugindecorator;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Decorates all plugin managers.
 */
class PluginManagerDecorator extends PluginManagerDecoratorBase {

  /**
   * The decorated plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $decorated;

  /**
   * The decorator plugin manager.
   *
   * @var \Drupal\plugindecorator\PluginDecoratorManager
   */
  private $pluginManager;

  /**
   * PluginManagerDecorator constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $decorated
   *   The decorated plugin manager.
   * @param \Drupal\plugindecorator\PluginDecoratorManager $pluginManager
   *   The decorator plugin manager.
   */
  public function __construct(PluginManagerInterface $decorated, PluginDecoratorManager $pluginManager) {
    $this->decorated = $decorated;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);
    $this->decorate($instance);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $instance = parent::getInstance($options);
    $this->decorate($instance);
    return $instance;
  }

  /**
   * Decorate the instance.
   *
   * @param object $instance
   *   The instance to decorate.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see \Drupal\media_imagick\Plugin\MediaSourceDecoratorBase::__construct
   */
  private function decorate(&$instance) {
    $instanceInterfaces = class_implements($instance);
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      $decoratorInterface = $definition['decorates'];
      if (in_array($decoratorInterface, $instanceInterfaces)) {
        $decoratorInstance = $this->pluginManager
          ->createInstance($id, ['decorated' => $instance]);
        $instance = $decoratorInstance;
      }
    }
  }

}
