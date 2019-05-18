<?php

namespace Drupal\devel_generate_plus\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QueueDevelWorker extends DeriverBase implements ContainerDeriverInterface {
  /**
   * Constructs a QueueDevelWorker object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    for($i=1; $i<=3; $i++) {
      $this->derivatives[$i] = $base_plugin_definition;
      $this->derivatives[$i]['label'] = 'Queue Devel Worker for Queue ' . $i;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
