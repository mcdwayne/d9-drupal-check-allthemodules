<?php

namespace Drupal\evergreen;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\evergreen\Entity\EvergreenConfigInterface;
use Drupal\evergreen\Entity\EvergreenContentInterface;

/**
 * Base class for Evergreen plugins.
 */
abstract class EvergreenBase extends PluginBase implements EvergreenInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleOptions() {
    dpm('EvergreenBase::getBundleOptions');
    return [];
  }

  /**
   * Called from the evergreen views data alter hook.
   */
  public function alterViewsData(array &$data) {
  }

  /**
   * Get the default expiration date for this content.
   */
  public function getDefaultExpirationDateForEntity(ContentEntityInterface $entity, EvergreenConfigInterface $config) {
    if (method_exists($entity, 'getCreatedTime')) {
      return $entity->getCreatedTime() + $config->get('evergreen_expiry');
    }
    return FALSE;
  }

}
