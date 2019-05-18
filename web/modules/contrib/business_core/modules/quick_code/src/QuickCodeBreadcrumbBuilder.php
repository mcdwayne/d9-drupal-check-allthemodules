<?php

namespace Drupal\quick_code;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides breadcrumb builder for quick code.
 */
class QuickCodeBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.quick_code.canonical';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Quick code'), 'entity.quick_code_type.collection'));
    /** @var \Drupal\quick_code\QuickCodeInterface $entity */
    $entity = $route_match->getParameter('quick_code');
    $breadcrumb->addCacheableDependency($entity);
    /** @var \Drupal\quick_code\QuickCodeTypeInterface $type */
    $type = $entity->type->entity;
    $breadcrumb->addLink($type->toLink());
    $breadcrumb->addLink($entity->toLink());

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
