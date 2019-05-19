<?php
/**
 * @file
 * Contains \Drupal\temporal\TemporalRangedHistory.
 */

namespace Drupal\temporal;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\temporal\Entity\Temporal;
use Drupal\temporal\Entity\TemporalType;

class TemporalRangedHistory implements TemporalRangedHistoryInterface {

  /**
   * @var TemporalListService $temporal_list_service;
   */
  protected $temporal_list_service;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * @var array $temporal_types
   */
  protected $temporal_types;

  /**
   * @var integer $start_date
   */
  protected $start_date;

  /**
   * @var integer $end_date
   */
  protected $end_date;

  /**
   * @var \DateInterval $resolution_interval
   */
  protected $resolution_interval;

  /**
   * @var \DateTimeZone $timezone
   */
  protected $timezone;

  /**
   * @var array $data
   */
  protected $data;

  public function __construct(TemporalListService $temporal_list_service, EntityTypeManagerInterface $entity_type_manager, $temporal_types, $start_date, $end_date, \DateInterval $resolution_interval, \DateTimeZone $timezone = NULL) {
    $this->temporal_list_service = $temporal_list_service;
    $this->entity_type_manager = $entity_type_manager;
    $this->setTemporalTypes($temporal_types);
    $this->setStartDate($start_date);
    $this->setEndDate($end_date);
    $this->setResolutionInterval($resolution_interval);
    if (NULL === $timezone) {
      $timezone = new \DateTimeZone(drupal_get_user_timezone());
    }
    $this->setTimezone($timezone);

    $this->captureTemporalEntries();
    $this->captureEntityFieldValues();
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedEntityFieldValues($temporal_type, callable $reducer) {
    if (!in_array($temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Temporal type provided is not part of this object\'s configuration.');
    }

    /** @var TemporalType $ttype */
    $etm = $this->entity_type_manager;
    $ttype = $etm->getStorage('temporal_type')->load($temporal_type);

    $entity_type = $ttype->getTemporalEntityType();
    $entity_field = $ttype->getTemporalEntityField();

    $used_values = [];
    foreach ($this->getReducedEntityFieldValues($temporal_type, $reducer) as $row_start_ts => $values_by_entity_id) {
      foreach ($values_by_entity_id as $entity_id => $value) {
        if (!in_array($value, $used_values) && NULL !== $value) {
          $used_values[] = $value;
        }
      }
    }

    return $used_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilteredEntityIDs($temporal_type, callable $reducer, callable $filter) {
    if (!in_array($temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Temporal type provided is not part of this object\'s configuration.');
    }

    /** @var TemporalType $ttype */
    $ttype = $this->entity_type_manager->getStorage('temporal_type')->load($temporal_type);

    $entity_type = $ttype->getTemporalEntityType();
    $entity_field = $ttype->getTemporalEntityField();

    $reduced = $this->getReducedEntityFieldValues($temporal_type, $reducer);
    $entity_ids = [];
    foreach ($reduced as $row_start_ts => $values_by_entity) {
      $filtered_values_by_entity = array_filter($values_by_entity, $filter);
      $entity_ids[$row_start_ts] = array_keys($filtered_values_by_entity);
    }

    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedFilteredEntityIDs($temporal_type, callable $reducer, callable $filter, $grouping_temporal_type, callable $grouping_reducer) {
    if (!in_array($temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Temporal type provided is not part of this object\'s configuration.');
    }
    if (!in_array($grouping_temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Grouping temporal type provided is not part of this object\'s configuration.');
    }

    $grouping_values = $this->getReducedEntityFieldValues($grouping_temporal_type, $grouping_reducer);
    $entity_ids      = $this->getFilteredEntityIDs($temporal_type, $reducer, $filter);

    $used_grouping_values = $this->getUsedEntityFieldValues($grouping_temporal_type, $grouping_reducer);

    $grouped_entity_ids = array_fill_keys(array_keys($entity_ids), []);
    foreach ($grouped_entity_ids as $row_start_ts => $row) {
      $grouping_row = $grouping_values[$row_start_ts];
      $id_row       = $entity_ids[$row_start_ts];

      foreach ($used_grouping_values as $used_grouping_value) {
        foreach ($id_row as $entity_id) {
          if ($used_grouping_value == $grouping_values[$row_start_ts][$entity_id]) {
            $grouped_entity_ids[$row_start_ts][$used_grouping_value][$entity_id] = $entity_id;
          }
        }
      }
    }

    return $grouped_entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedReducedPeriodValuesByEntityID($temporal_type, callable $reducer, callable $filter, $grouping_temporal_type, callable $grouping_reducer, callable $period_reducer) {
    $grouped_entity_ids = $this->getGroupedFilteredEntityIDs($temporal_type, $reducer, $filter, $grouping_temporal_type, $grouping_reducer);
    $reduced = [];
    foreach ($grouped_entity_ids as $row_start_ts => $entities_by_group) {
      foreach ($entities_by_group as $group_value => $entity_ids) {
        $reduced[$row_start_ts][$group_value] = array_reduce($entity_ids, $period_reducer);
      }
    }
    return $reduced;
  }

  /**
   * {@inheritdoc}
   */
  public function getReducedEntityFieldValues($temporal_type, callable $reducer) {
    if (!in_array($temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Temporal type provided is not part of this object\'s configuration.');
    }

    /** @var TemporalType $ttype */
    $ttype = $this->entity_type_manager->getStorage('temporal_type')->load($temporal_type);

    $entity_type = $ttype->getTemporalEntityType();
    $entity_field = $ttype->getTemporalEntityField();

    $reduced = [];

    foreach ($this->data as $row_start_ts => $row_data) {
      $reduced[$row_start_ts] = [];
      foreach ($row_data['entity_field_values'][$entity_type] as $entity_id => $entity_fields) {
        if (empty($entity_fields[$entity_field]['changes'])) {
          $changes = array($entity_fields[$entity_field]['open']);
        } else {
          $changes = $entity_fields[$entity_field]['changes'];
        }
        $reduced[$row_start_ts][$entity_id] = array_reduce($changes, $reducer);
      }
    }

    return $reduced;
  }

  /**
   * {@inheritdoc}
   */
  public function getReducedPeriodValues($temporal_type, callable $reducer, callable $period_reducer) {
    $reduced_entity_field_values = $this->getReducedEntityFieldValues($temporal_type, $reducer);

    $reduced = [];
    foreach ($reduced_entity_field_values as $start_ts => $entity_field_values) {
      $reduced[$start_ts] = array_reduce($entity_field_values, $period_reducer);
    }

    return $reduced;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedReducedEntityFieldValues($temporal_type, callable $reducer, $grouping_temporal_type, callable $grouping_reducer) {
    if (!in_array($temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Temporal type provided is not part of this object\'s configuration.');
    }
    if (!in_array($grouping_temporal_type, $this->getTemporalTypes())) {
      throw new \InvalidArgumentException('Grouping temporal type provided is not part of this object\'s configuration.');
    }

    $grouping_values = $this->getReducedEntityFieldValues($grouping_temporal_type, $grouping_reducer);
    $field_values    = $this->getReducedEntityFieldValues($temporal_type, $reducer);

    $used_grouping_values = $this->getUsedEntityFieldValues($grouping_temporal_type, $grouping_reducer);

    $reduced = array_fill_keys(array_keys($field_values), []);
    foreach ($reduced as $row_start_ts => $row) {
      $grouping_row = $grouping_values[$row_start_ts];
      $field_row    = $field_values[$row_start_ts];

      foreach ($used_grouping_values as $used_grouping_value) {
        foreach ($field_values[$row_start_ts] as $entity_id => $field_value) {
          if ($used_grouping_value == $grouping_values[$row_start_ts][$entity_id]) {
            $reduced[$row_start_ts][$used_grouping_value][$entity_id] = $field_value;
          }
        }
      }
    }

    return $reduced;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedReducedPeriodValues($temporal_type, callable $reducer, $grouping_temporal_type, callable $grouping_reducer, callable $period_reducer) {
    $entity_field_data = $this->getGroupedReducedEntityFieldValues($temporal_type, $reducer, $grouping_temporal_type, $grouping_reducer);
    $reduced = [];
    foreach ($entity_field_data as $row_start_ts => $entities_by_group) {
      foreach ($entities_by_group as $group_value => $entity_field_values) {
        $reduced[$row_start_ts][$group_value] = array_reduce($entity_field_values, $period_reducer);
      }
    }
    return $reduced;
  }

  public function reduceToOpen($carry, $item) {
    if (NULL !== $item) {
      if (NULL === $carry) {
        $carry = $item;
      }
    }
    return $carry;
  }

  public function reduceToClose($carry, $item) {
    if (NULL !== $item) {
      $carry = $item;
    }
    return $carry;
  }

  public function reduceToMax($carry, $item) {
    if (NULL !== $item) {
      if (NULL === $carry || $item > $carry) {
        $carry = $item;
      }
    }
    return $carry;
  }

  public function reduceToMin($carry, $item) {
    if (NULL !== $item) {
      if (NULL === $carry || $item < $carry) {
        $carry = $item;
      }
    }
    return $carry;
  }

  public function reduceToSum($carry, $item) {
    if (NULL !== $item) {
      if (NULL === $carry) {
        $carry = 0;
      }
      $carry += $item;
    }
    return $carry;
  }

  public function reduceToCount($carry, $item) {
    if (NULL !== $item) {
      if (NULL === $carry) {
        $carry = 0;
      }
      $carry += 1;
    }
    return $carry;
  }

  public function reduceToSumAndCount($carry, $item) {
    if (!isset($carry)) {
      $carry = ['sum' => 0, 'count' => 0];
    }
    if (NULL !== $item) {
      $carry['sum'] += $item;
      $carry['count'] += 1;
    }
    return $carry;
  }

  protected function captureTemporalEntries() {
    $this->initialize();
    foreach ($this->getTemporalTypes() as $temporal_type) {
      $this->captureTemporalEntriesByType($temporal_type);
    }
    return $this;
  }

  protected function captureTemporalEntriesByType($temporal_type) {
    $query = $this->temporal_list_service->prepareFieldValuesByTemporalType($temporal_type);
    $data = $this->temporal_list_service->getResults($query);

    // Ensure the data are sorted chronologically.
    usort($data, function ($a, $b) {
      if ($a['created'] == $b['created']) {
        return 0;
      }
      return $a['created'] < $b['created'] ? -1 : 1;
    });

    foreach ($data as $temporal_row) {
      $temporal_row_created = new \DateTime();
      $temporal_row_created->setTimezone($this->getTimezone());
      $temporal_row_created->setTimestamp($temporal_row['created']);

      foreach ($this->data as $row_start_ts => $data_row) {
        $match = FALSE;
        if ($row_start_ts == 'open' ) {
          $match = ($temporal_row_created < $data_row['dates']['end']);
        }
        else if ($row_start_ts =='close') {
          $match = ($temporal_row_created >= $data_row['dates']['start']);
        }
        else {
          $match = ($temporal_row_created >= $data_row['dates']['start'] && $temporal_row_created < $data_row['dates']['end']);
        }

        if ($match) {
          $data_row['temporal_entries'][] = $temporal_row;
        }
        $this->data[$row_start_ts] = $data_row;
      }
    }

    return $this;
  }

  protected function captureEntityFieldValues() {
    $involved_fields = array_reduce($this->data, function($carry, $data_row) {
      foreach ($data_row['temporal_entries'] as $temporal_entry) {
        if (!array_key_exists($temporal_entry['entity_type'], $carry)) {
          $carry[$temporal_entry['entity_type']] = [];
        }
        if (!in_array($temporal_entry['entity_field'], $carry[$temporal_entry['entity_type']])) {
          $carry[$temporal_entry['entity_type']][] = $temporal_entry['entity_field'];
        }
      }
      return $carry;
    }, []);

    $etm = $this->entity_type_manager;
    $ttype_storage = $etm->getStorage('temporal_type');
    $involved_entity_ids = [];
    $temporal_types = $this->getTemporalTypes();
    foreach ($temporal_types as $temporal_type) {
      /** @var TemporalType $ttype */
      $ttype = $ttype_storage->load($temporal_type);
      if (!$ttype) {
        throw new \InvalidArgumentException(t("Temporal type @temporal_type does not exist.", ['@temporal_type' => $temporal_type]));
      }
      $temporal_entity_type = $ttype->getTemporalEntityType();
      $entity_storage = $etm->getStorage($temporal_entity_type);
      if (!array_key_exists($temporal_entity_type, $involved_entity_ids)) {
        $involved_entity_ids[$temporal_entity_type] = [];
      }
      $query = $entity_storage->getQuery();
      $bundle = $ttype->getTemporalEntityBundle();
      if ($bundle) {
        // TODO determine if we need to vary the field name here for bundles on different entity types
        if ($temporal_entity_type == 'user' && $bundle == 'user') {
          // This is fine.  Not sure why we store bundle names for users, but we can ignore this.
        }
        else {
          if ($temporal_entity_type != 'node') {
            throw new \Exception('Temporal data does not yet fully support bundles on non-node entity types.');
          }
          $query->condition('type', $bundle);
        }
      }
      $result = $query->execute();
      $new_entity_ids = array_keys($result);
      $involved_entity_ids[$temporal_entity_type] = array_unique(array_merge($involved_entity_ids[$temporal_entity_type], $new_entity_ids));
      // make sure the key and value match; otherwise weird 0s show up
      $involved_entity_ids[$temporal_entity_type] = array_combine($involved_entity_ids[$temporal_entity_type], $involved_entity_ids[$ttype->getTemporalEntityType()]);
    }

    foreach ($involved_entity_ids as $entity_type => $entity_ids) {
      foreach ($entity_ids as $entity_id) {
        foreach ($involved_fields[$entity_type] as $entity_field) {
          $previous_close = NULL;
          foreach ($this->data as $row_start_ts => $row_data) {
            // Make sure the array structure is ready.
            if (!array_key_exists($entity_type, $row_data['entity_field_values'])) {
              $row_data['entity_field_values'][$entity_type] = [];
            }
            if (!array_key_exists($entity_id, $row_data['entity_field_values'][$entity_type])) {
              $row_data['entity_field_values'][$entity_type][$entity_id] = [];
            }
            if (!array_key_exists($entity_field, $row_data['entity_field_values'][$entity_type][$entity_id])) {
              $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field] = [
                'open' => $previous_close,
                'changes' => [],
                'close' => NULL,
              ];
            }

            foreach ($row_data['temporal_entries'] as $temporal_entry) {
              if ($temporal_entry['entity_type'] != $entity_type) {
                continue;
              }
              if ($temporal_entry['entity_id'] != $entity_id) {
                continue;
              }
              if ($temporal_entry['entity_field'] != $entity_field) {
                continue;
              }

              $temporal_entry_created = new \DateTime();
              $temporal_entry_created->setTimezone($this->getTimezone());
              $temporal_entry_created->setTimestamp($temporal_entry['created']);

              if ($temporal_entry_created >= $row_data['dates']['start'] && $temporal_entry_created < $row_data['dates']['end']) {
                $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['changes'][$temporal_entry['created']] = $temporal_entry['value'];
                $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['close'] = $temporal_entry['value'];
              }
            }

            if (count($row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['changes']) == 0) {
              // We only have keys for open and close, so that means open and close should be equal.
              $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['close'] = $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['open'];
            }

            $previous_close = $row_data['entity_field_values'][$entity_type][$entity_id][$entity_field]['close'];

            $this->data[$row_start_ts] = $row_data;
          }
        }
      }
    }

    return $this;
  }

  /**
   * Initialize date range structure.
   *
   * Generates an array of date ranges based on the class properties, like so:
   * array(
   *   $start_timestamp => array(
   *     'dates' => array(
   *       'start' => \DateTime $start
   *       'end'   => \DateTime $end
   *     ),
   *     'temporal_entries' => array(
   *     ),
   *     'entity_field_values' => array(
   *     ),
   *   ),
   * )
   */
  protected function initialize() {
    $final_end_datetime = new \DateTime();
    $final_end_datetime->setTimezone($this->getTimezone());
    $final_end_datetime->setTimestamp($this->getEndDate());
    $final_end_datetime->setTime(0, 0, 0);

    $interval_start_datetime = new \DateTime();
    $interval_start_datetime->setTimezone($this->getTimezone());
    $interval_start_datetime->setTimestamp($this->getStartDate());
    $interval_start_datetime->setTime(0, 0, 0);

    $final_end = $final_end_datetime;
    $interval_start = $interval_start_datetime;

    $interval = $this->getResolutionInterval();

    $structure = [
      'open' => [
        'dates' => [
          'start' => NULL,
          'end'   => $interval_start,
        ],
        'temporal_entries' => [],
        'entity_field_values' => [],
      ],
    ];
    do {
      $interval_end = clone $interval_start;
      $interval_end->add($interval);

      $structure[$interval_start->format('U')] = [
        'dates' => [
          'start' => $interval_start,
          'end'   => $interval_end,
        ],
        'temporal_entries' => [],
        'entity_field_values' => [],
      ];

      $interval_start = clone $interval_start;
      $interval_start->add($interval);
      $interval_end = clone $interval_start;
      $interval_end->add($interval);
    } while ($interval_end <= $final_end);
    $structure['close'] = [
      'dates' => [
        'start' => $interval_start,
        'end'   => NULL,
      ],
      'temporal_entries' => [],
      'entity_field_values' => [],
    ];

    $this->data = $structure;
    return $this;
  }

  public function getTemporalTypes() {
    return $this->temporal_types;
  }

  public function setTemporalTypes($temporal_types) {
    if (!is_array($temporal_types)) {
      $temporal_types = array($temporal_types);
    }
    $this->temporal_types = $temporal_types;
    return $this;
  }

  public function getStartDate() {
    return $this->start_date;
  }

  public function setStartDate($start_date) {
    $this->start_date = (integer)$start_date;
    return $this;
  }

  public function getEndDate() {
    return $this->end_date;
  }

  public function setEndDate($end_date) {
    $this->end_date = (integer)$end_date;
    return $this;
  }

  public function getResolutionInterval() {
    return $this->resolution_interval;
  }

  public function setResolutionInterval(\DateInterval $resolution_interval) {
    $this->resolution_interval = $resolution_interval;
    return $this;
  }

  public function getTimezone() {
    return $this->timezone;
  }

  public function setTimezone(\DateTimeZone $timezone) {
    $this->timezone = $timezone;
    return $this;
  }

}
