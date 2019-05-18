<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Controller\ControllerBase;
use Drupal\aws_cloud\Service\InstanceTypePriceTableRenderer;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller responsible to show price list.
 */
class InstanceTypePriceController extends ControllerBase implements InstanceTypePriceControllerInterface {

  /**
   * InstanceTypePriceController constructor.
   *
   * @param \Drupal\aws_cloud\Service\InstanceTypePriceTableRenderer $price_table_renderer
   *   AWS Pricing service.
   */
  public function __construct(InstanceTypePriceTableRenderer $price_table_renderer) {
    $this->priceTableRenderer = $price_table_renderer;
  }

  /**
   * Dependency Injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aws_cloud.instance_type_price_table_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function show($cloud_context) {
    $build = [];

    $build['table'] = $this->priceTableRenderer->render($cloud_context);

    return $build;
  }

}
