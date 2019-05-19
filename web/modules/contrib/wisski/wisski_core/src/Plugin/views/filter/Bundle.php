<?php
/**
 * @file
 * Contains \Drupal\wisski_core\Plugin\views\filter\StringFilter.
 */

namespace Drupal\wisski_core\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Bundle as ViewsBundle;
use Drupal\wisski_core\WisskiHelper;

/**
 * Filter handler for string.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("wisski_bundle")
 */
class Bundle extends ViewsBundle {

  function operators() {
    $operators = array(
      'IN' => array(
        'title' => t('Is equal to'),
        'short' => t('='),
        'method' => 'opIn',
        'values' => 1,
      ),
    );

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }


  /**
   * {@inheritdoc}
   */
  function opIn() {
    $this->query->query->condition($this->realField, $this->value, $this->operator);
  }

}
