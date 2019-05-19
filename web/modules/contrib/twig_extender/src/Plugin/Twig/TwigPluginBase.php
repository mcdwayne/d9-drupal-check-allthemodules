<?php

namespace Drupal\twig_extender\Plugin\Twig;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for Twig plugins plugins.
 */
class TwigPluginBase extends PluginBase implements TwigExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunction() {
    return $this->pluginDefinition['function'];
  }

  /**
   * {@inheritdoc}
   */
  public function register() {
    if ($this->getType() == 'function') {
      return new \Twig_SimpleFunction($this->getName(), [$this, $this->getFunction()]);
    }
    return new \Twig_SimpleFilter($this->getName(), [$this, $this->getFunction()]);
  }

}
