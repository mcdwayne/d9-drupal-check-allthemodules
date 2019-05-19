<?php

namespace Drupal\testswarm;

class TestswarmStorageController {

  static function getAllTests($githash, $theme, $karma, $filter_failures) {
    $q = db_select('testswarm_test', 'tt')->fields('tt', array('theme', 'total'));
    $q->join('testswarm_info', 'ti', 'tt.info_id = ti.id');
    $q->fields('ti', array('caller', 'githash', 'sitename', 'version'));
    $q->addExpression('COUNT(tt.id)', 'count');
    $q->addExpression('COUNT(tt.id)', 'num_runs');
    $q->addExpression('100 * (AVG(tt.failed) / tt.total)', 'failed');
    $q->addExpression('AVG(tt.runtime)', 'runtime');
    $q->addExpression('MIN(tt.timestamp)', 'first_run');
    $q->addExpression('MAX(tt.timestamp)', 'last_run');

    // Filter by githash.
    if (!empty($githash) && $githash != 'ALL') {
      $q->condition('ti.githash', check_plain($githash));
    }

    // Filter by theme.
    if (!empty($theme) && $theme != 'ALL') {
      $q->condition('tt.theme', check_plain($theme));
    }

    // Filter by karma.
    if (!empty($karma) && $karma != 'ALL') {
      $q->condition('tt.karma', check_plain($karma));
    }

    // Only show failures.
    if (isset($filter_failures) && !empty($filter_failures) && $filter_failures) {
      $q->condition('tt.failed', 0, '<>');
    }

    $q->groupBy('ti.caller')->groupBy('tt.theme')->groupBy('ti.githash')->groupBy('ti.url');
    $q->orderBy('ti.caller')->orderBy('tt.theme')->orderBy('ti.githash')->orderBy('ti.url');
    $result = $q->execute()->fetchAll();

    return $result;
  }

  static function deleteTest($test) {
    $q = db_delete('testswarm_test');
    if ($test != 'all') {
      $q->condition('info_id', db_select('testswarm_info', 'ti')->fields('ti', array('id'))->condition('caller', check_plain($test)), 'IN');
    }
    $q->execute();

    db_delete('testswarm_test_run')->condition('qt_id', db_select('testswarm_test', 'tt')->fields('tt', array('id')), 'NOT IN')->execute();
    db_delete('testswarm_test_run_detail')->condition('tri', db_select('testswarm_test_run', 'ttr')->fields('ttr', array('id')), 'NOT IN')->execute();
  }
}
