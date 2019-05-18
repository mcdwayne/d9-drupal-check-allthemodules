<?php

namespace Drupal\plus;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plus\Annotation\FormAlter;
use Drupal\plus\Core\Form\FormAlterInterface;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Drupal\plus\Utility\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages discovery and instantiation of Bootstrap form alters.
 *
 * @ingroup plugins_form
 */
class FormAlterPluginManager extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\FormAlterPluginManager object.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, CacheBackendInterface $cache_backend) {
    parent::__construct($provider_type, 'Plugin/FormAlter', FormAlterInterface::class, FormAlter::class, $cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.providers'),
      $container->get('cache.discovery')
    );
  }

  public function alter(array &$form = [], FormStateInterface $form_state, $form_id = NULL) {

    $form_element = Element::reference($form, $form_state);
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {

    }
  }


}
