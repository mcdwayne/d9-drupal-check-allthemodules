<?php

/**
 * @file
 * Contains \Drupal\taxonews\Plugin\Derivative\TaxonewsBlock.
 */

namespace Drupal\taxonews\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Component\Plugin\Derivative\DerivativeInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\taxonews\Plugin\Block\TaxonewsBlock
 */
class TaxonewsBlock extends DeriverBase {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   *
   * Retrieves Taxonews block definitions from the list of vocabularies.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $allowed_vocabularies = \Drupal::config('taxonews.settings')->get('allowed_vocabularies');

    if (!empty($allowed_vocabularies)) {
      $vocabularies = entity_load_multiple('taxonomy_vocabulary', $allowed_vocabularies);
        // Provide block plugin definitions for all vocabularies.
      foreach ($vocabularies as $vocabulary_name => $vocabulary) {
        $q = \Drupal::entityQuery('taxonomy_term')
          ->condition('vid', $vocabulary_name)
          ->sort('weight')
          ->sort('name');
        $tids = $q->execute();
        $terms = entity_load_multiple('taxonomy_term', $tids);
        foreach ($terms as $tid => $term) {
          $key = "$vocabulary_name:$tid";
          $this->derivatives[$key] = array(
            'admin_label' => t("Taxonews / @vocabulary_name / (@tid) @term_name", array(
              '@vocabulary_name' => $vocabulary_name,
              '@term_name' => $term->label(),
              '@tid' => $term->id(),
             )),
            'cache' => array(
              'max-age' => Cache::PERMANENT,
            ),
          ) + $base_plugin_definition;
        }
      }
    }

    if (empty($this->derivatives)) {
      $this->derivatives = array('safetynet' => $base_plugin_definition + array(
        'delta' => 'default',
        'admin_label' => t('Taxonews safety block'),
        'cache' => array(
          'max-age' => Cache::PERMANENT,
        ),
      ));
      $this->derivatives = array();
    }

    return $this->derivatives;
  }
}
