<?php

/**
 * @file
 * Contains \Drupal\wisski_core\Plugin\views\argument\EntityId.
 *
 * The file is based on EntityId from efq_views module.
 */

namespace Drupal\wisski_core\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\NumericArgument  as ViewsNumeric;

/**
 * Numeric argument for fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("wisski_entity_id")
 */
class EntityId extends ViewsNumeric {


  /**
   * We don't support every operator from the parent class ("not between", for example),
   * hence the need to define only the operators we do support.
   */
  function operators() {
dpm(__METHOD__, __METHOD__);    
    $operators = array(
      'IN' => array(
        'title' => t('Is equal to'),
        'method' => 'opSimple',
        'short' => t('='),
        'values' => 1,
      ),
      'NOT IN' => array(
        'title' => t('Is not equal to'),
        'method' => 'opSimple',
        'short' => t('!='),
        'values' => 1,
      ),
    );

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  function query($group_by = false) {
    # $this->value may be an array or a single value depending on the "Allow multiple values" option
    $values = $this->value;
    if (empty($values)) {
      $values = [];
    }
    elseif (!is_array($values)) {
      $values = [$values];
    }
    $this->query->query->condition("eid", $values, 'IN');
  }

}
