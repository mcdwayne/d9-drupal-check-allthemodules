<?php

namespace Drupal\contest;

use Drupal\views\EntityViewsData;

/**
 * Render controller to integrate contests into views.
 */
class ContestViewData extends EntityViewsData {

  /**
   * Describe fields to views.
   *
   * @return array
   *   An array describing the contest data to views.
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['contest_field_data']['entries'] = [
      'title'      => 'Total Entries',
      'help'       => 'Displays the total number of contest entries.',
      'real field' => 'id',
      'field'      => ['id' => 'contest_total_entries'],
    ];
    $data['contest_field_data']['end'] = [
      'title'      => 'End Date',
      'help'       => 'The contest end date.',
      'real field' => 'id',
      'field'      => ['id' => 'contest_end_date'],
    ];
    $data['contest_field_data']['start'] = [
      'title'      => 'Start Date',
      'help'       => 'The contest start date.',
      'real field' => 'id',
      'field'      => ['id' => 'contest_start_date'],
    ];
    return $data;
  }

}
