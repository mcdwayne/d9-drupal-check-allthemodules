<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginBase as CorePluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\plus\Traits\PluginSerializationTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for an update.
 *
 * @ingroup utility
 */
class PluginBase extends CorePluginBase implements ContainerAwareInterface, ContainerFactoryPluginInterface {

  use ContainerAwareTrait;
  use PluginSerializationTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->setContainer($container);
    return $instance;
  }

}
