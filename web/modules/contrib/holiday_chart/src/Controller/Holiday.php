<?php

namespace Drupal\holiday_chart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Manages complete cart functions
 * 
 */
class Holiday extends ControllerBase {

  /**
   *
   * Stores DB Connection 
   */
  public $connection;

  /**
   * Constructor for Setting database connection
   * 
   */
  public function __construct() {
    $this->connection = Database::getConnection();
  }

  /**
   * 
   * Update holiday date
   */
  public function UpdateDate($date, $status, $month, $year) {
    if ($status == 'W') {
      $query = $this->connection->merge('holiday_chart');
      $query->fields([
        'holiday_date' => $date,
        'month' => $month,
        'year' => $year,
        'timestamp' => strtotime($date),
        'holiday' => 'H',
      ]);
      $query->key(['holiday_date' => $date])->execute();
    }
    else if ($status == 'H') {
      $query = $this->connection->delete('holiday_chart');
      $query->condition('holiday_date', $date);
      $query->execute();
    }
    return new JsonResponse($status);
  }

  /*
   * Return holiday count.
   */

  public function HolidayCount($start_date, $total_days) {
   $date_arr=array();
    for ($i = 1; $i <= $total_days; $i++) {
      array_push($date_arr, date('d-m-Y', strtotime("+$i day", $start_date)));
    }
    $count = $this->fetch_holiday_count($date_arr);
    if ($count != 0) {
      $start_date = strtotime($date_arr[$total_days - 1]);
      $date_arr = array();
      for ($i = 1; $i <= $count; $i++) {
        array_push($date_arr, date('d-m-Y', strtotime("+$i day", $start_date)));
      }
      $new_count = $this->fetch_holiday_count($date_arr);
      $final_count = $count + $new_count;
      if ($new_count != 0) {
        $start_date = strtotime($date_arr[$count - 1]);
        $date_arr = array();
        for ($i = 1; $i <= $new_count; $i++) {
          array_push($date_arr, date('d-m-Y', strtotime("+$i day", $start_date)));
        }
        $new_count2 = $this->fetch_holiday_count($date_arr);
        $final_count = $final_count + $new_count2;
      }
    }
    return $final_count;
  }

  /*
   * Return holiday count between dates.
   */
  public function fetch_holiday_count($date_arr) {
    $query = $this->connection->select('holiday_chart', 'hc');
    $query->condition('hc.holiday_date', $date_arr, 'IN');
    $query->addExpression('COUNT(*)');
    $count = $query->execute()->fetchField();
    return $count;
  }

}
