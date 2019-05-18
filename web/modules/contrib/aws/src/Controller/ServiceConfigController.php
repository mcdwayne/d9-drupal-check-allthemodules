<?php

namespace Drupal\aws\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\aws\Aws;

/**
 * Defines a controller to list profiles.
 */
class ServiceConfigController implements ContainerInjectionInterface {

  /**
   * The AWS service.
   *
   * @var \Drupal\aws\Aws
   */
  protected $aws;

  /**
   * {@inheritdoc}
   */
  public function __construct(Aws $aws) {
    $this->aws = $aws;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aws')
    );
  }

  /**
   * Gets the title for the service config page.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return string
   *   The title of the service.
   */
  public function getTitle($service_id) {
    $service = $this->aws->getService($service_id);
    return $service['label'];
  }

}
