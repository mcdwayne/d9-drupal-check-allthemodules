<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches the list of profiles via Aegir's Web service API.
 */
abstract class ListHostingServiceCall extends HostingServiceCall {

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @see ContainerInjectionInterface::create()
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('recurly_aegir'),
      $container->get('http_client'),
      $container->get('config.factory')->get('recurly_aegir.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler')
    );
  }

}
