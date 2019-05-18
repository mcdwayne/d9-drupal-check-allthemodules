<?php

namespace Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver;

use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RawPathComponent.
 *
 * @BreadcrumbTitleResolver(
 *   id = "raw_path_component",
 *   label = @Translation("Raw Path Component"),
 *   description = @Translation("Use the raw path component as title."),
 *   weight = 100
 * )
 *
 * @package Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver
 */
class RawPathComponent extends BreadcrumbTitleResolverBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle($path, Request $request, RouteMatchInterface $route_match) {
    $path_elements = explode('/', $path);
    $element = end($path_elements);
    return str_replace(['-', '_'], ' ', Unicode::ucfirst($element));
  }

}
