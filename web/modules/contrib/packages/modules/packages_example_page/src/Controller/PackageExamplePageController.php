<?php

namespace Drupal\packages_example_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\packages\PackagesInterface;

/**
 * Class PackageExamplePageController.
 *
 * @package Drupal\packages_example_page\Controller
 */
class PackageExamplePageController extends ControllerBase {

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * {@inheritdoc}
   */
  public function __construct(PackagesInterface $packages) {
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('packages')
    );
  }

  /**
   * Index page.
   *
   * @return array
   *   The renderable page content.
   */
  public function index() {
    // Load the package.
    $package = $this->packages->getPackage('example_page');

    // Build and return the page.
    return $package->page();
  }

}
