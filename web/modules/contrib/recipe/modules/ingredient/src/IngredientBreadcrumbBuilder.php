<?php

namespace Drupal\ingredient;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds breadcrumbs for Ingredient entities.
 */
class IngredientBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $ingredient = $route_match->getParameter('ingredient');
    $match = $ingredient instanceof IngredientInterface;
    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[] = Link::createFromRoute($this->t('Ingredients'), 'ingredient.landing_page');
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

}
