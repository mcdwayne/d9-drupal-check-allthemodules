<?php

namespace Drupal\asf;

use Drupal\asf\AsfAction;
use Drupal\Core\Datetime\DrupalDateTime;


/**
 * Class AsfSchema.
 */
class AsfSchema {
  /**
   * Return a list of actions according to your selection.
   *
   * @param array $options
   *   key value array of action-field and action-value
   */
  public static function selectActions($options) {
    //$query = \Drupal::entityQuery('asf_schedule');
    $query = db_select('asf_schedule', 'asf');
    $query->fields('asf');
    if (isset($options['aid'])) {
      $query->condition('asf.aid', $options['aid'], '=');
    }
    if (isset($options['eid'])) {
      $query->condition('asf.eid', $options['eid'], '=');
    }
    if (isset($options['fid'])) {
      $query->condition('asf.fid', $options['fid'], '=');
    }
    if (isset($options['action'])) {
      $query->condition('asf.action', $options['action'], '=');
    }
    if (isset($options['status'])) {
      $query->condition('asf.status', $options['status'], '=');
    }
    if (isset($options['time'])) {
      $query->condition('asf.time', $options['time']['value'], $options['time']['operator']);
    }
    if (isset($options['created'])) {
      $query->condition('asf.created', $options['created']['value'], $options['created']['operator']);
    }
    if (isset($options['changed'])) {
      $query->condition('asf.changed', $options['changed']['value'], $options['changed']['operator']);
    }
    $query->orderBy('time', 'ASC');
    $actions = $query->execute()->fetchAll();
    return $actions;
  }


  /**
   * Delete pending actions of this entity so the newer version can override it.
   */
  public static function deleteSchedule($eid, $fieldName) {
    // Select and delete future actions.
    $options = array(
      'eid' => $eid,
      'status' => ASF_STATUS_PENDING,
      'fid' => $fieldName,
    );
    $existing_actions = AsfSchema::selectActions($options);
    foreach ($existing_actions as $action) {
      $a = new AsfAction();
      $a->delete($action->aid);
    }
  }

  /**
   * Calculate scheme for node.
   */
  public function generateScheme($entity, $mod_set, $fieldName) {
    $id = $entity->id();
    switch ($mod_set['publication_type']) {
      case ASF_TYPE_START:
        $start_time = $mod_set['startdate'];
        $this->generateSchemeCreateAction($id, $start_time, ASF_ACTION_PUBLISH, $fieldName);
        break;

      case ASF_TYPE_START_END:
        $start_time = $mod_set['startdate'];
        $this->generateSchemeCreateAction($id, $start_time, ASF_ACTION_PUBLISH, $fieldName);
        $end_time = $mod_set['enddate'];
        $this->generateSchemeCreateAction($id, $end_time, ASF_ACTION_UNPUBLISH, $fieldName);
        break;

      case ASF_TYPE_ITERATE:
      case ASF_TYPE_ITERATE_AMOUNT:
        $mod_set = $this->expandModalities($mod_set);
        $scheme = $this->modToScheme($mod_set);
        foreach ($scheme as $action => $item) {
          $this->generateSchemeCreateAction($id, $item['time'], $item['action'], $fieldName);
        }
        break;

      case ASF_TYPE_INHERIT:
        //_asf_inherit_publication_schema($mod_set['inherit_nid'], $node->nid);
        break;
    }
  }

  /**
   * Helper function to create actions while generating the scheme.
   *
   * @param $id
   *   The id of the action.
   * @param $time
   *   The unix timestamp of the action.
   * @param $action
   *   The action type.
   * @param $fieldName
   *   The name/id of the action.
   */
  private function generateSchemeCreateAction($id, $time, $action, $fieldName) {
    // Get current datetime object with Drupal's timezone.
    $dateTime = new DrupalDateTime();
    // Get current timestamp.
    $current_time = $dateTime->format('U');

    // Don't create the action if it's in the past.
    if ($time < $current_time) {
      \Drupal::logger('asf')
        ->notice(
          'Prevented to create an action in the past:' .
          ' ID=' . $id .
          ' CURRENT_TIME=' . $current_time .
          ' TIME=' . $time .
          ' ACTION=' . $action .
          ' FIELDNAME=' . $fieldName
        );
      return;
    }

    $a = new AsfAction();
    $a->create($id, $time, $action, $fieldName);
    $a->insert();
  }

  /**
   * This expands the modalities with calculated data.
   */
  function expandModalities($mod_set) {
    $time_format = 'H:i';
    $mod_set['iteration_days'] = $this->splitField($mod_set['iteration_day']);
    $mod_set['iteration_weekdays'] = $this->splitField($mod_set['iteration_weekday']);
    $mod_set['iteration_weeks'] = $this->splitField($mod_set['iteration_week']);
    $mod_set['iteration_months'] = $this->splitField($mod_set['iteration_month']);
    $mod_set['iteration_years'] = $this->splitField($mod_set['iteration_year']);
    $mod_set['start_hour'] = date('H', $mod_set['start_time']);
    $mod_set['start_min'] = date('i', $mod_set['start_time']);
    $mod_set['end_hour'] = date('H', $mod_set['end_time']);
    $mod_set['end_min'] = date('i', $mod_set['end_time']);
    return $mod_set;
  }

  /**
   * Translate modalities to publish/unpublish scheme.
   */
  function modToScheme($mods) {
    $day = $mods['startdate'];
    $iteration_count = 0;
    $scheme = array();
    // Infinite means do it for 1 month and then recalculate.
    // Doing 2 days extra for if tha cron is held: taking some reserve.
    $infinity_end = strtotime('+ 33 days', REQUEST_TIME);
    while ($this->continueIteration($day, $mods, $iteration_count, $infinity_end)) {
      $start_time = mktime(intval($mods['start_hour']), intval($mods['start_min']), 0, date('m', $day), date('d', $day), date('Y', $day));
      $end_time = mktime(intval($mods['end_hour']), intval($mods['end_min']), 0, date('m', $day), date('d', $day), date('Y', $day));
      $h_start_time = date("Y-m-d H:i:s", $start_time);
      $h_end_time = date("Y-m-d H:i:s", $end_time);
      if ($meet = $this->meetsRequirements($start_time, $end_time, $mods)) {
        if ($start_time > REQUEST_TIME) {
          $scheme[] = array(
            'time' => $start_time,
            'action' => ASF_ACTION_PUBLISH,
          );
        }
        if ($end_time > REQUEST_TIME) {
          $scheme[] = array(
            'time' => $end_time,
            'action' => ASF_ACTION_UNPUBLISH,
          );
        }
      }
      $day = strtotime('+1 day', $day);
      $iteration_count++;
    }
    return $scheme;
  }


  /**
   * Test if a date meets the requirements.
   */
  function meetsRequirements($date, $date2, $mods) {

    // Publish and unpublish on the same time is useless.
    if ($date == $date2) {
      return FALSE;
    }

    foreach ($mods as $mod => $mod_data) {
      switch ($mod) {
        case 'iteration_days':
          if (!empty($mods['iteration_days'])) {
            $dayint = trim(date('d', $date), '0');
            if (!in_array($dayint, $mods['iteration_days']) && $mods['iteration_day'] != '*') {
              return FALSE;
            }
          }
          break;

        case 'iteration_weekdays':
          if (!empty($mods['iteration_weekdays'])) {
            $dayint = trim(date('w', $date), '0');
            if (!in_array($dayint, $mods['iteration_weekdays']) && $mods['iteration_weekday'] != '*') {
              return FALSE;
            }
          }
          break;

        case 'iteration_weeks':
          if (!empty($mods['iteration_weeks'])) {
            $dayint = trim(date('W', $date), '0');
            if (!in_array($dayint, $mods['iteration_weeks']) && $mods['iteration_week'] != '*') {
              return FALSE;
            }
          }
          break;

        case 'iteration_months':
          if (!empty($mods['iteration_months'])) {
            $dayint = trim(date('n', $date), '0');
            if (!in_array($dayint, $mods['iteration_months']) && $mods['iteration_month'] != '*') {
              return FALSE;
            }
          }
          break;

        case 'iteration_years':
          if (!empty($mods['iteration_years'])) {
            $dayint = date('Y', $date);
            if (!in_array($dayint, $mods['iteration_years']) && $mods['iteration_year'] != '*') {
              return FALSE;
            }
          }
          break;

      }
    }
    return TRUE;
  }


  /**
   * Determine if we can keep looping.
   *
   * @param string $day
   *   the generated day to test.
   * @param array $mods
   *   the modalities (filters)
   * @param int $iteration_count
   *   count the iteration count.
   *
   * @return boolean
   *   TRUE = continue iterating.
   */
  function continueIteration($day, $mods, $iteration_count, $infinity_end) {

    if ($mods['publication_type'] == ASF_TYPE_ITERATE) {
      return ($day < $mods['enddate']);
    }
    elseif ($mods['publication_type'] == ASF_TYPE_ITERATE_AMOUNT) {
      if ($mods['iteration_max'] == '') {
        return TRUE;
      }
      return ($iteration_count < intval($mods['iteration_max']));
    }
//    elseif ($mods['iteration_end'] == ASF_ITERATION_FIRST) {
//      return (($day < $mods['enddate']) && ($iteration_count < $mods['iteration_max']));
//    }
//    elseif ($mods['iteration_end'] == ASF_ITERATION_INFINITE) {
//      return ($day < $infinity_end);
//    }
    return FALSE;
  }

  /**
   * Explode the field.
   */
  function splitField($field) {
    $items = explode(',', $field);
    if (count($items) == 1 && $items[0] == '') {
      return NULL;
    }
    return $items;
  }
}
