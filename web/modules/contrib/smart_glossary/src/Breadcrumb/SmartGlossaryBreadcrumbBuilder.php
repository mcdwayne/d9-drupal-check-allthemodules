<?php

namespace Drupal\smart_glossary\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;

class SmartGlossaryBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    $parameters = $attributes->getParameters()->all();
    return (isset($parameters['smart_glossary_config']) && isset($parameters['glossary_language']));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    /** @var \Drupal\smart_glossary\Entity\SmartGlossaryConfig $smart_glossary_config */
    $smart_glossary_config = $route_match->getParameter('smart_glossary_config');
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($smart_glossary_config->getTitle(), 'smart_glossary.display.' . $smart_glossary_config->id()));
    $breadcrumb->addCacheContexts(['url.path.parent']);
    return $breadcrumb;
  }

}