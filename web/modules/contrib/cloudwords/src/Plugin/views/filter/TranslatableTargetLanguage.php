<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable translation status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_target_language_filter")
 */
class TranslatableTargetLanguage extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $this->valueOptions = cloudwords_language_list();
    return $this->valueOptions;
  }
}
