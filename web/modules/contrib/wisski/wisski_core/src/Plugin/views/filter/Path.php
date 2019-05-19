<?php
/**
 * @file
 * Contains \Drupal\wisski_core\Plugin\views\filter\PathString.
 */

namespace Drupal\wisski_core\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\StringFilter as ViewsString;

/**
 * Filter handler for string.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("wisski_path_string")
 */
class PathString extends ViewsString {

  function operators() {
    $operators = array(
      '=' => array(
        'title' => t('Is equal to'),
        'short' => t('='),
        'method' => 'opSimple',
        'values' => 1,
      ),
      '!=' => array(
        'title' => t('Is not equal to'),
        'short' => t('!='),
        'method' => 'opSimple',
        'values' => 1,
      ),
      'CONTAINS' => array(
        'title' => t('Contains'),
        'short' => t('contains'),
        'method' => 'opSimple',
        'values' => 1,
      ),
      'STARTS_WITH' => array(
        'title' => t('Starts with'),
        'short' => t('begins'),
        'method' => 'opSimple',
        'values' => 1,
      ),
      'ENDS_WITH' => array(
        'title' => t('Ends with'),
        'short' => t('ends'),
        'method' => 'opSimple',
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
  function opSimple() {
    $this->query->query->condition($this->realField, $this->value, $this->operator);
  }

}
