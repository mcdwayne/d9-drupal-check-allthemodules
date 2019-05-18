<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable translation status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_translation_status_filter")
 */
class TranslationStatus extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $this->valueOptions = cloudwords_exists_options_list();

    return $this->valueOptions;
  }
}
