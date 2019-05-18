<?php

namespace Drupal\sdk;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * SDK plugin definition.
 */
final class SdkPluginDefinition implements PluginDefinitionInterface {

  /**
   * SDK type.
   *
   * @var string
   */
  protected $id;
  /**
   * Human-readable name of SDK.
   *
   * @var string
   */
  protected $label;
  /**
   * The plugin provider.
   *
   * @var string
   */
  protected $provider;
  /**
   * A fully qualified class name of deriver.
   *
   * @var string
   */
  protected $class;
  /**
   * A fully qualified class name of configuration form.
   *
   * @var string
   */
  protected $formClass;

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setProvider($provider) {
    $this->provider = $provider;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setClass($class) {
    if (!is_subclass_of($class, SdkPluginBase::class)) {
      throw new \InvalidArgumentException(sprintf(
        'SDK plugin "%s", provided by "%s", must extends "%s"',
        $this->id,
        $this->provider,
        SdkPluginBase::class
      ));
    }

    $this->class = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setFormClass($class) {
    if (!is_subclass_of($class, SdkPluginConfigurationFormBase::class)) {
      throw new \InvalidArgumentException(sprintf(
        'SDK configuration form of "%s" plugin, provided by "%s", must extends "%s"',
        $this->id,
        $this->provider,
        SdkPluginConfigurationFormBase::class
      ));
    }

    $this->formClass = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass() {
    return $this->formClass;
  }

}
