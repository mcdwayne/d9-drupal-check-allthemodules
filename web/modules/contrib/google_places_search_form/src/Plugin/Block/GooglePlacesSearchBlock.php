<?php

namespace Drupal\google_places_search_form\Plugin\Block;

/**
 * @file
 * This file contains the Google Places Search Block.
 */

use Drupal\Core\Block\BlockBase;

/**
 * Provide a 'Google Places Search' Block.
 *
 * @Block(
 *   id = "google_places_search_block",
 *   admin_label = @Translation("Google Places Search Block"),
 * )
 */
class GooglePlacesSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\google_places_search_form\Form\GoogleSearchAutocomplete');
    return $form;
  }

}
