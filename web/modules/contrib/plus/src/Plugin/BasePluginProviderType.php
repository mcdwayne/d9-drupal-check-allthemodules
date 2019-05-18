<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\Extension;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\plus\Utility\ArrayObject;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class BasePluginProviderType.
 */
abstract class BasePluginProviderType implements PluginProviderTypeInterface {

  use ContainerAwareTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * An array of cache tags to use for the cached definitions.
   *
   * @var array
   */
  protected $cacheTags = [];

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * Retrieves the namespaces for a list of Extension objects.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   An array of Extension objects to iterate over.
   *
   * @return \Drupal\plus\Utility\ArrayObject
   *   An iterable list of namespace => path key/value pairs.
   *
   * @todo Refactor in 8.6.x when all extension namespaces are registered.
   * @see https://www.drupal.org/project/drupal/issues/2941757
   */
  protected function getExtensionNamespaces(array $extensions = []) {
    $namespaces = new ArrayObject();
    foreach ($extensions as $extension) {
      if ($extension instanceof Extension) {
        $namespaces['Drupal\\' . $extension->getName()] = [DRUPAL_ROOT . '/' . $extension->getPath() . '/src'];
      }
    }
    return $namespaces;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getType();

}
