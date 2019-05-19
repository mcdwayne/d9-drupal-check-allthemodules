<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A ContentLoader implementation supporting configuration options.
 *
 * @see \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
 * @see \Drupal\yaml_content\ContentLoader\ContentLoaderBase
 *
 * @todo Use service parameter for override-able default options.
 */
class ConfigurableContentLoader extends ContentLoaderBase implements ContentLoaderInterface {

  /**
   * A collection of configurable options affecting execution behavior.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, SerializationInterface $parser, EventDispatcherInterface $dispatcher) {
    parent::__construct($entityTypeManager, $parser, $dispatcher);

    // Initialize with default configuration options.
    $this->options = $this->getDefaultOptions();
  }

  /**
   * Set a configurable option value.
   *
   * @param string $option
   *   The name of the option being configured.
   * @param mixed $value
   *   The value to assign into the option.
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   *   The called object.
   */
  public function setOption($option, $value) {
    $this->options[$option] = $value;

    return $this;
  }

  /**
   * Get the value from a configured option.
   *
   * @param string $option
   *   The name of the option value to retrieve.
   *
   * @return mixed|null
   *   The value of the specified option or NULL if the option is unset.
   */
  public function getOption($option) {
    $value = NULL;
    if (isset($this->options[$option])) {
      $value = $this->options[$option];
    }

    return $value;
  }

  /**
   * Fetch all configured options.
   *
   * @return array
   *   All configured options for this ContentLoader.
   */
  public function getOptions() {
    return $this->options + $this->getDefaultOptions();
  }

  /**
   * Set multiple configuration options.
   *
   * @param array $options
   *   A collection of configuration values to assign. These are used to
   *   override currently set or default values.
   *
   * @return $this
   *   The called object.
   */
  public function setOptions(array $options) {
    $this->options = $options + $this->options + $this->getDefaultOptions();

    return $this;
  }

  /**
   * Fetch all default configuration options.
   *
   * @return array
   *   A collection of all default options for this ContentLoader.
   *
   * @todo Integrate default options with service parameters.
   */
  public function getDefaultOptions() {
    return [];
  }

}
