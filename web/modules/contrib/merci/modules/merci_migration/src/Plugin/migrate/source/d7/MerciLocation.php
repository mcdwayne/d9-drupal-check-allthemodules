<?php

namespace Drupal\merci_migration\Plugin\migrate\source\d7;


use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 7 node source from database.
 *
 * @MigrateSource(
 *   id = "merci_location",
 * )
 */
class MerciLocation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Filter out creators membership.

    $query = $this->select('variable', 'v')->fields('v');

    $query->condition('v.name', 'merci_hours_operation');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.

    $hours = unserialize($row->getSourceProperty('value'));

    $open_hours = array();

    foreach ($hours as $day => $open) {
      if (is_numeric($day) and is_array($open)) {
        list($starthour, $startmin) = explode(':', $open['open']);
        list($endhour, $endmin) = explode(':', $open['close']);
        $open_hours[] = array(
          'day' => $day,
          'starthours' => intval($starthour) . $startmin,
          'endhours' => intval($endhour) . $endmin,
        );
      }
      elseif ($day == 'closed_days') {
        $holidays = array();
        foreach ($open as $holiday) {
          $holidays[] = array(
            'value' => $holiday,
          );
        }

        $row->setSourceProperty('holidays', $holidays);
      }
    }

    $row->setSourceProperty('open_hours', $open_hours);
    $row->setSourceProperty('id', 1);

    
    return parent::prepareRow($row);
  }

/**
   * {@inheritdoc}
   */

  public function fields() {
    $fields = [
      'id' => $this->t('Row ID'),
      'name' => $this->t('Name of variable'),
      'value' => $this->t('Original value from database'),
      'open_hours' => $this->t('Open Hours'),
    ];
    return $fields;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'text';
    return $ids;
  }

}
