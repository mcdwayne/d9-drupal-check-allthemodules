<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable in current project or not for current user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_added_to_project_filter")
 */
class AddedToProject extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $this->valueOptions = ['yes'=>t('Yes'), 'no'=>t('No')];
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $key = reset($this->value);
    $uid = \Drupal::currentUser()->id();
    switch($key) {
      case 'yes':
        $this->query->addWhere($this->options['group'], 'user_id', $uid, '=');
      break;
      case 'no':
        $this->query->addWhere($this->options['group'], 'user_id', $uid, '!=');
      break;
    }
  }
}
