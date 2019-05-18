<?php

namespace Drupal\redmine_connector;

/**
 * Class GroupingService.
 */
class GroupingService {

  /**
   * Sum all time spent on the project.
   *
   * @param array $data
   *   Project's array from Redmine.
   *
   * @return float
   *   Return spent time from project's time entries.
   */
  public function getHoursFromTimeEntries(array $data) {
    $spent_time = 0;
    foreach ($data['time_entries'] as $time_entry) {
      $spent_time += $time_entry['hours'];
    }
    return $spent_time;
  }

  /**
   * Find time periods.
   *
   * @return array
   *   Return array with periods.
   */
  public function getPeriods() {
    // Getting time entries for Last Month.
    // Finding time periods.
    // For last month.
    $start_date = date('Y-m-d', strtotime('first day of last month'));
    $end_date = date('Y-m-d', strtotime('last day of last month'));
    $periods['last_month_period'] = $start_date . '|' . $end_date;

    // For this month.
    $start_date = date('Y-m-d', strtotime('first day of this month'));
    $end_date = date('Y-m-d', strtotime('last day of this month'));
    $periods['this_month_period'] = $start_date . '|' . $end_date;

    // For last week.
    $start_date = date('Y-m-d', strtotime('last week'));
    $end_date = date('Y-m-d', strtotime('last week + 6 days'));
    $periods['last_week_period'] = $start_date . '|' . $end_date;

    // For this week.
    $start_date = date('Y-m-d', strtotime('this week'));
    $end_date = date('Y-m-d', strtotime('this week + 6 days'));
    $periods['this_week_period'] = $start_date . '|' . $end_date;

    return $periods;
  }

  /**
   * Sum all spent time of project's time entries for different periods.
   *
   * @param int $project_id
   *   Project from Redmine.
   *
   * @return array
   *   Return array with spent hours for different periods.
   */
  public function getProjectTimeEntriesForDifferentPeriods($project_id) {
    // Getting date periods.
    $periods = \Drupal::service('redmine_connector.grouping')->getPeriods();
    // Getting spent hours for special Project from Redmine.
    // For all time.
    $p_time_entries_all = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['project_id' => $project_id]);
    if (!empty($p_time_entries_all)) {
      $hours['spent_hours_all'] = $this->getHoursFromTimeEntries($p_time_entries_all);
    }
    else {
      $hours['spent_hours_all'] = 0;
    }
    // For last month.
    $p_time_entries_lm = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['project_id' => $project_id, 'date' => $periods['last_month_period']]);
    if (!empty($p_time_entries_lm)) {
      $hours['spent_hours_lm'] = $this->getHoursFromTimeEntries($p_time_entries_lm);
    }
    else {
      $hours['spent_hours_lm'] = 0;
    }
    // For this month.
    $p_time_entries_tm = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['project_id' => $project_id, 'date' => $periods['this_month_period']]);
    if (!empty($p_time_entries_tm)) {
      $hours['spent_hours_tm'] = $this->getHoursFromTimeEntries($p_time_entries_tm);
    }
    else {
      $hours['spent_hours_tm'] = 0;
    }
    // For last week.
    $p_time_entries_lw = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['project_id' => $project_id, 'date' => $periods['last_week_period']]);
    if (!empty($p_time_entries_lw)) {
      $hours['spent_hours_lw'] = $this->getHoursFromTimeEntries($p_time_entries_lw);
    }
    else {
      $hours['spent_hours_lw'] = 0;
    }
    // For this week.
    $p_time_entries_tw = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['project_id' => $project_id, 'date' => $periods['this_week_period']]);
    if (!empty($p_time_entries_tw)) {
      $hours['spent_hours_tw'] = $this->getHoursFromTimeEntries($p_time_entries_tw);
    }
    else {
      $hours['spent_hours_tw'] = 0;
    }

    return $hours;
  }

  /**
   * Sum all spent time of user's time entries for different periods.
   *
   * @param int $user_id
   *   Project from Redmine.
   *
   * @return array
   *   Return array with spent hours for different periods.
   */
  public function getUserTimeEntriesForDifferentPeriods($user_id) {
    // Getting date periods.
    $periods = \Drupal::service('redmine_connector.grouping')->getPeriods();
    // Getting spent hours for special Project from Redmine.
    // For all time.
    $u_time_entries_all = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['user_id' => $user_id]);
    if (!empty($u_time_entries_all)) {
      $hours['spent_hours_all'] = $this->getHoursFromTimeEntries($u_time_entries_all);
    }
    else {
      $hours['spent_hours_all'] = 0;
    }
    // For last month.
    $u_time_entries_lm = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['user_id' => $user_id, 'date' => $periods['last_month_period']]);
    if (!empty($u_time_entries_lm)) {
      $hours['spent_hours_lm'] = $this->getHoursFromTimeEntries($u_time_entries_lm);
    }
    else {
      $hours['spent_hours_lm'] = 0;
    }
    // For this month.
    $u_time_entries_tm = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['user_id' => $user_id, 'date' => $periods['this_month_period']]);
    if (!empty($u_time_entries_tm)) {
      $hours['spent_hours_tm'] = $this->getHoursFromTimeEntries($u_time_entries_tm);
    }
    else {
      $hours['spent_hours_tm'] = 0;
    }
    // For last week.
    $u_time_entries_lw = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['user_id' => $user_id, 'date' => $periods['last_week_period']]);
    if (!empty($u_time_entries_lw)) {
      $hours['spent_hours_lw'] = $this->getHoursFromTimeEntries($u_time_entries_lw);
    }
    else {
      $hours['spent_hours_lw'] = 0;
    }
    // For this week.
    $u_time_entries_tw = \Drupal::service('redmine_connector.connect')
      ->getData('time_entries', ['user_id' => $user_id, 'date' => $periods['this_week_period']]);
    if (!empty($u_time_entries_tw)) {
      $hours['spent_hours_tw'] = $this->getHoursFromTimeEntries($u_time_entries_tw);
    }
    else {
      $hours['spent_hours_tw'] = 0;
    }

    return $hours;
  }

}
