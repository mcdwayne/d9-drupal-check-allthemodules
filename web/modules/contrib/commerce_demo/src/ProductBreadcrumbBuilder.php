<?php

namespace Drupal\commerce_demo;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\pathauto\AliasCleanerInterface;

/**
 * Builds a product breadcrumb based on the "field_product_categories" field.
 */
class ProductBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * Constructs a new ProductBreadcrumbBuilder object.
   *
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AliasCleanerInterface $alias_cleaner, EntityTypeManagerInterface $entity_type_manager) {
    $this->aliasCleaner = $alias_cleaner;
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() != 'entity.commerce_product.canonical') {
      return FALSE;
    }
    $product = $route_match->getParameter('commerce_product');

    return $product && $product->hasField('field_product_categories');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Catalog'), 'view.product_catalog.page_1'));

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $route_match->getParameter('commerce_product');
    /** @var \Drupal\taxonomy\TermInterface $category */
    $category = $product->get('field_product_categories')->first()->entity;
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->facetStorage->load($category->bundle());
    $label = $this->aliasCleaner->cleanString($category->label());

    $view_url = Url::fromRoute('view.product_catalog.page_1');
    $facet_url = Url::fromUserInput($view_url->toString() . '/' . $facet->getUrlAlias() . '/' . $label . '-' . $category->id());
    $breadcrumb->addLink(Link::fromTextAndUrl($category->label(), $facet_url));

    $breadcrumb->addCacheableDependency($product);
    $breadcrumb->addCacheableDependency($category);
    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

}
