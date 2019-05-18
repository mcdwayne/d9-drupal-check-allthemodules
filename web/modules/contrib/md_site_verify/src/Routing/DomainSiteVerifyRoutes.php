<?php

namespace Drupal\md_site_verify\Routing;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class DomainSiteVerifyRoutes implements ContainerInjectionInterface{

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * Constructs a DomainSiteVerifyService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Create function return static database loader configuration.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return loader database.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /*
     requirements:
    _permission: 'administer multidomain site verify'
   * */

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $verifications = $this->database->select('md_site_verify', 'dsv')
      ->fields('dsv', ['dsv_id', 'file'])
      ->condition('file', '', '<>')
      ->execute()
      ->fetchAll();
    if(!empty($verifications)){
      $route_collection = new RouteCollection();
      foreach ($verifications as $verification) {
        $route = new Route(
          $verification->file, [
          '_controller' => '\Drupal\md_site_verify\Controller\DomainSiteVerifyController::domainVerificationsFileContent',
          'dsverify' => $verification->dsv_id,
        ],
          ['_md_site_verify_access_route' => 'TRUE']
        );
        $route_collection->add('md_site_verify.' . $verification->file, $route);
      }

      return $route_collection;
    }

  }

}
