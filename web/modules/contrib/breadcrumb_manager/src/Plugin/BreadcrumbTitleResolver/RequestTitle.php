<?php

namespace Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver;

use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestTitle.
 *
 * @BreadcrumbTitleResolver(
 *   id = "request_title",
 *   label = @Translation("Page title"),
 *   description = @Translation("Use page title if exists."),
 *   weight = 1
 * )
 *
 * @package Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver
 */
class RequestTitle extends BreadcrumbTitleResolverBase {

  /**
   * The Title Resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TitleResolverInterface $title_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($path, Request $request, RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    return $this->titleResolver->getTitle($request, $route);
  }

}
