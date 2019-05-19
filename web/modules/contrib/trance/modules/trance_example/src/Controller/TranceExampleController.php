<?php

namespace Drupal\trance_example\Controller;

use Drupal\trance\Controller\TranceController;
use Drupal\trance\TranceInterface;
use Drupal\trance\TranceTypeInterface;

/**
 * Returns responses for TranceExample routes.
 */
class TranceExampleController extends TranceController {

  /**
   * Provides the trance_example submission form.
   *
   * @param \Drupal\trance\TranceTypeInterface $trance_example_type
   *   The trance_example type entity for the trance_example.
   *
   * @return array
   *   A trance_example submission form.
   */
  public function add(TranceTypeInterface $trance_example_type) {
    return parent::add($trance_example_type);

  }

  /**
   * Generates an overview table of older revisions of a trance_example.
   *
   * @param \Drupal\trance\TranceInterface $trance_example
   *   A trance_example object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TranceInterface $trance_example) {
    return parent::revisionOverview($trance_example);
  }

}
