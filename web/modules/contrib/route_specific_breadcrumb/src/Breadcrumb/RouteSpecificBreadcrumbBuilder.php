<?php

namespace Drupal\route_specific_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\route_specific_breadcrumb\Controller\ListRecordsController;
use Drupal\Core\Url;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class RouteSpecificBreadcrumbBuilder.
 */
class RouteSpecificBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * Protected database variable.
   *
   * @var database
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    $routeName = $attributes->getRouteName();
    $data = ListRecordsController::routeCheck($this->database, $routeName);
    if (isset($data->route) && ($data->route == $routeName)) {
      return $routeName;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $data = ListRecordsController::routeCheck($this->database, $route_match->getRouteName());
    $routeName = $route_match->getRouteName();
    if ($data->route == $routeName) {
      $breadcrumb = new Breadcrumb();
      $description = unserialize($data->description);
      foreach ($description as $value) {
        if ($value['link'] === '<front>') {
          $value['link'] = '/';
        }
        $url = Url::fromUri('internal:' . $value['link']);
        $project_link = Link::fromTextAndUrl($value['name'], $url);
        $breadcrumb->addLink($project_link);
      }
    }
    return $breadcrumb;
  }

}
