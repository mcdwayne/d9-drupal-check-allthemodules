<?php

namespace Drupal\partner_link\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

/**
 * An example controller.
 */
class PartnersController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "partner_link__partners");
    $query->condition('field_partner_link__enabled', 1);
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);

    $partners = [];
    foreach ($terms as $term) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($term->getEntityTypeId());
      $partners[] = $view_builder->view($term, 'default');
    }

    $build = [
      '#theme' => 'partner_link_list',
      '#partners' => $partners,
    ];

    return $build;
  }

}
