<?php

namespace Drupal\recurly_aegir\Wrappers;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for externally managed objects.
 */
abstract class Wrapper {

  /**
   * The current HTTP/S request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Node storage.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The Recurly configuration.
   *
   * @var Drupal\Core\Config\ImmutableConfig
   */
  protected $recurlyConfig;

  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class Constructor.
   *
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current HTTP/S request.
   * @param Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   Node storage.
   * @param Drupal\Core\Config\ImmutableConfig $recurly_config
   *   The Recurly configuration.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
      Request $current_request = NULL,
      EntityStorageInterface $node_storage = NULL,
      ImmutableConfig $recurly_config = NULL,
      ModuleHandlerInterface $module_handler = NULL
  ) {
    $service_container = \Drupal::getContainer();

    $this->currentRequest = $current_request ?: $service_container->get('request_stack')->getCurrentRequest();
    $this->nodeStorage = $node_storage ?: $service_container->get('entity_type.manager')->getStorage('node');
    $this->recurlyConfig = $recurly_config ?: $service_container->get('config.factory')->get('recurly.settings');
    $this->moduleHandler = $module_handler ?: $service_container->get('module_handler');
  }

}
