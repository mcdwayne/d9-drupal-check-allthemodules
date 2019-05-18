<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelPluginBase.
 */

namespace Drupal\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Entity\Model;
use Drupal\collect\Query\DelegatingQueryEvaluator;
use Drupal\collect\Query\QueryEvaluatorHelperInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Plugin\PluginBase;

/**
 * Abstract base class for Collect model plugins.
 *
 * For getTypedData and getQueryEvaluator, this base class returns a reference
 * to itself, so new plugins can be practically implemented in single classes.
 *
 * Note that extending plugins should override getStaticPropertyDefinitions()
 * rather than getPropertyDefinitions() to define properties.
 *
 * For models where the set of applicable properties may vary depending on the
 * set of data instances it is used for, plugins should also implement
 * \Drupal\collect\Model\DynamicModelTypedDataInterface.
 */
abstract class ModelPluginBase extends PluginBase implements ModelPluginInterface, ModelTypedDataInterface, QueryEvaluatorHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return isset($this->configuration['config']) ? $this->configuration['config'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    // Help text is only needed if the description in the definition is not
    // enough.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    return isset($this->getPluginDefinition()['patterns']) ? $this->getPluginDefinition()['patterns'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestConfig(CollectContainerInterface $container, array $definition) {
    return Model::create(array(
      'label' => (string) $definition['label'],
      'uri_pattern' => $container->getSchemaUri(),
      'plugin_id' => $definition['id'],
      'container_revision' => TRUE,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    // @todo Remove when implementing view modes, https://www.drupal.org/node/2420839
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['collect-teaser']],
      '#attached' => ['library' => ['collect/collect.default']],
      'title' => [
        '#markup' => '<h2>' . $data->getContainer()->link() . '</h2>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    // @todo Restore static caching of propertyDefinitions in https://www.drupal.org/node/2495039
    if ($this->getConfig() instanceof ModelInterface) {
      return $this->getConfig()->getTypedProperties();
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryEvaluator() {
    return new DelegatingQueryEvaluator($this);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveQueryPath($data, array $path) {
    // Recursion base case: if the path is empty, return the given data.
    if (empty($path)) {
      return $data;
    }

    // Only allow arrays or Traversable object.
    if (!is_array($data) && !$data instanceof \Traversable) {
      return NULL;
    }

    // Compare head segment case-insensitively to keys in the data. Recurse on
    // match.
    $path_head = array_shift($path);
    foreach ($data as $key => $value) {
      if (!strcasecmp($key, $path_head)) {
        return $this->resolveQueryPath($value, $path);
      }
    }

    // Head segment does not match any data key.
    return NULL;
  }

}
