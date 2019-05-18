<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\PropertyTaxonomyVocabulary.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\Component\Utility\String;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Handle matching of multiple options selectable via checkboxes.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_property_taxonomy_vocabulary")
 */
class PropertyTaxonomyVocabulary extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $vocabularies = Vocabulary::loadMultiple();
    $options = array();
    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->vid] = String::checkPlain($vocabulary->name);
    }
    $this->valueOptions = $options;
    return $options;
  }

}
