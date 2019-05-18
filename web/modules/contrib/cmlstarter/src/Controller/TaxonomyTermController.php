<?php

namespace Drupal\cmlstarter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TaxonomyTermController.
 */
class TaxonomyTermController extends ControllerBase {
  /**
   * @var \Drupal\tvi\Service\TaxonomyViewsIntegratorManager
   */
  private $term_display_manager;

  /**
   * TaxonomyViewsIntegratorTermPageController constructor.
   * @param \Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface $term_display_manager
   */
  public function __construct(TaxonomyViewsIntegratorManagerInterface $term_display_manager) {
    $this->term_display_manager = $term_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $term_display_manager = $container->get('tvi.tvi_manager');
    return new static($term_display_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function render(TermInterface $taxonomy_term) {
    if ($taxonomy_term->bundle() == 'catalog') {
      return views_embed_view('product', 'embed', $taxonomy_term->id());
    }
    if ($taxonomy_term->bundle() == 'brand') {
      return views_embed_view('product', 'embed_1', $taxonomy_term->id());
    }
    if ($taxonomy_term->bundle() == 'product_options') {
      return views_embed_view('product', 'embed_2', $taxonomy_term->id());
    }
    else {
      return $this->term_display_manager->getTaxonomyTermView($taxonomy_term);
    }
  }

}
