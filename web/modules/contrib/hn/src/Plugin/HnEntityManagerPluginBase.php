<?php

namespace Drupal\hn\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for Headless Ninja Entity Manager Plugin plugins.
 */
abstract class HnEntityManagerPluginBase extends PluginBase implements HnEntityManagerPluginInterface {


  /**
   * The interface or class that this HnEntityManager supports.
   *
   * @var string|\stdClass
   */
  protected $supports;

  /**
   * {@inheritdoc}
   */
  public function isSupported(EntityInterface $entity) {
    return $entity instanceof $this->supports;
  }

}
