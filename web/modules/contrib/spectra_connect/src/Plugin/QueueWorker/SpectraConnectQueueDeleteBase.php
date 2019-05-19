<?php

namespace Drupal\spectra_connect\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\spectra_connect\SpectraConnectUtilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SpectraConnectQueueDeleteBase.
 *
 * @package Drupal\spectra_connect\Plugin\QueueWorker
 */
abstract class SpectraConnectQueueDeleteBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $utility;

  /**
   * Constructor.
   */
  public function __construct(SpectraConnectUtilities $utility) {
    $this->utility = $utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $utility = new SpectraConnectUtilities();
    return new static(
      $utility
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $connector = $item->connector;
    $data = $item->data;

    SpectraConnectUtilities::spectraDelete($connector, $data);
  }

}
