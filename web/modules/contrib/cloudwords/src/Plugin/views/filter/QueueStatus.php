<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable translation status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_status_filter")
 */
class QueueStatus extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $queueStatuses = [0 => $this->t('No'), 2 => $this->t('Yes')];

    $this->valueOptions = $queueStatuses;
    return $this->valueOptions;
  }
}
