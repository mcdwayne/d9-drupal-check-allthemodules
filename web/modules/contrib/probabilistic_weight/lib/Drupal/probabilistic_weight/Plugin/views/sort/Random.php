<?php

/**
 * @file
 * Definition of Drupal\probabilistic_weight\Plugin\views\sort\Random.
 */

namespace Drupal\probabilistic_weight\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Sort handler to sort by random probabilistic weight.
 *
 * @ingroup views_sort_handlers
 *
 * @PluginID("probabilistic_weight_random")
 */
class Random extends SortPluginBase {

  public function query() {

    $this->ensureMyTable();

    $counter = &drupal_static('probabilistic_weight_handler_sort_weight_counter', 0);

    $current_table = $this->query->ensureTable($this->actualTable, $this->relationship);
    $current_field = $this->query->addField($current_table, $this->realField);

    $driver = db_driver();
    switch ($driver) {

      case 'pgsql':
        $sub = 'RANDOM() * ' . $current_field;
        break;

      case 'mysql':
      case 'mysqli':
      case 'sqlite':
      default:
        $sub = 'RAND() * ' . $current_field;

    }

    $this->query->addOrderBy(
      NULL,
      $sub,
      $this->options['order'],
      $current_field . '_weight_' . $counter
    );

    $counter++;

  }

}
