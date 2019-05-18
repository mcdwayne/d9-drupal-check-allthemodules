<?php

namespace Drupal\astrology\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class UtilityController.
 */
class UtilityController extends ControllerBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->connection = $connection;
    $this->config = $config_factory;
  }

  /**
   * Returns timestamps for the given date.
   */
  public function getTimestamps($date = '') {
    return strtotime($date);
  }

  /**
   * Returns first and last day of week.
   */
  public function getFirstLastDow($newdate = '') {
    // +1 for first day of week as Monday.
    $week_days[] = mktime(0, 0, 0, date("n", $newdate), date("j", $newdate) - date("N", $newdate) + 1);
    // +7 for last day of week as Sunday.
    $week_days[] = mktime(0, 0, 0, date("n", $newdate), date("j", $newdate) - date("N", $newdate) + 7);

    return $week_days;
  }

  /**
   * Returns formatted date value.
   */
  public function getFormatDateValue($formatter, $newdate) {
    $timestamps = strtotime($newdate);
    $month = date('m', $timestamps);
    $day = date('d', $timestamps);
    $formated_value = date($formatter, mktime(0, 0, 0, $month, $day));
    return $formated_value;
  }

  /**
   * Returns day of the year.
   */
  public function getDoy($day, $format = 'Y-m-d') {
    $date = mktime(0, 0, 0, 1, $day);
    return date($format, $date);
  }

  /**
   * Returns associative array of days.
   */
  public function getDaysArray() {
    $days_array = [];
    $i = 1;
    while ($i <= 31) {
      $days_array[$i] = $i;
      $i += 1;
    }
    return $days_array;
  }

  /**
   * Returns array of months.
   */
  public function getMonthsArray() {
    $months_array = [
      1 => $this->t('January'),
      2 => $this->t('February'),
      3 => $this->t('March'),
      4 => $this->t('April'),
      5 => $this->t('May'),
      6 => $this->t('June'),
      7 => $this->t('July'),
      8 => $this->t('August'),
      9 => $this->t('September'),
      10 => $this->t('October'),
      11 => $this->t('November'),
      12 => $this->t('December'),
    ];
    return $months_array;
  }

  /**
   * Returns array of years, next 10 years.
   */
  public function getYearsArray() {
    $years_array = array_combine(range(date("Y") - 1, date("Y") + 1), range(date("Y") - 1, date("Y") + 1));
    return $years_array;
  }

  /**
   * Returns associative array of astrologies.
   */
  public function getAstrologyArray() {
    $query = $this->connection->query("SELECT id, name FROM astrology");
    $data = [];
    while ($row = $query->fetchObject()) {
      $data[$row->id] = $row->name;
    }
    return $data;
  }

  /**
   * Return associative array of astrology.
   */
  public function getAstrologyListSignArray($astrology_id) {
    $query = $this->connection->query("SELECT id, name FROM {astrology_signs} WHERE astrology_id = :hid", [
      ':hid' => $astrology_id,
    ]);
    $data = [];
    $data[0] = 'ALL';
    while ($row = $query->fetchObject()) {
      $data[$row->id] = $row->name;
    }
    return $data;
  }

  /**
   * Return associative array of astrology.
   */
  public function getAstrologySignArray($sign_id, $astrology_id) {

    $query = $this->connection->select('astrology_signs', 'as_')
      ->fields('as_')
      ->condition('id', $sign_id, '=')
      ->condition('astrology_id ', $astrology_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    if (!$query->rowCount()) {
      throw new AccessDeniedHttpException();
    }
    return $query->fetchAssoc();
  }

  /**
   * Return TRUE if text_id belongs to a valid sign.
   */
  public function isValidText($sign_id, $text_id) {

    $query = $this->connection->select('astrology_text', 'at_')
      ->fields('at_', ['id', 'text', 'text_format'])
      ->condition('id', $text_id, '=')
      ->condition('astrology_sign_id ', $sign_id, '=')
      ->execute();
    $query->allowRowCount = TRUE;
    if (!$query->rowCount()) {
      throw new AccessDeniedHttpException();
    }
    $result = $query->fetchObject();
    return $result;
  }

  /**
   * Update configurations, if changes made on astrology settings page.
   */
  private function updateAstrologyConfigSettings($enabled, $default_astrology, $astrology_id) {

    $astrology_config = $this->config->getEditable('astrology.settings');
    $this->connection->update('astrology')
      ->fields([
        'enabled' => $enabled,
      ])->condition('id', $default_astrology, '=')
      ->execute();

    $astrology_config->set('astrology', $astrology_id)->save();

    // Invalidate astrology block cache on add/update astrology.
    self::invalidateAstrologyBlockCache();
  }

  /**
   * Update default astrology if there is a change.
   */
  public function updateDefaultAstrology($astrology_id, $status, $op) {

    $astrology_config = $this->config('astrology.settings');
    $default_astrology = $astrology_config->get('astrology');

    if ($op == 'new' && $status) {
      $this->updateAstrologyConfigSettings('0', $default_astrology, $astrology_id);
    }
    elseif ($op == 'update' && $astrology_id != $default_astrology && $status) {
      $this->updateAstrologyConfigSettings('0', $default_astrology, $astrology_id);
    }
    elseif ($op == 'update' && $astrology_id == $default_astrology && !$status) {
      $this->updateAstrologyConfigSettings('1', '1', '1');
    }
    elseif ($op == 'delete' && $astrology_id == $default_astrology) {
      $this->updateAstrologyConfigSettings('1', '1', '1');
    }
  }

  /**
   * Invalidate astrology block cache.
   */
  public static function invalidateAstrologyBlockCache() {
    \Drupal::service('cache_tags.invalidator')
      ->invalidateTags(['astrology_block']);
  }

  /**
   * Check and return return next and previous link accordingly.
   */
  public function astrologyCheckNextPrev($formatter, $next_prev) {

    $next_prev_val = [];

    // If leap year, set number of days in year.
    $last_day_of_year = date('L') ? 365 : 364;

    // If leap year, set max week number in year.
    $last_week_of_year = date('L') ? 53 : 52;

    $last_month_of_year = 12;
    $max_year_range = date('Y') + 1;
    $min_year_range = date('Y') - 1;

    switch ($formatter) {
      case 'day':
        if ($next_prev < $last_day_of_year && $next_prev > 0) {
          $next_prev_val['next'] = $next_prev + 1;
          $next_prev_val['prev'] = ($next_prev - 1) ? $next_prev - 1 : '+0';
        }
        elseif ($next_prev == $last_day_of_year) {
          $next_prev_val['next'] = FALSE;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        elseif ($next_prev == '0') {
          $next_prev_val['next'] = 1;
          $next_prev_val['prev'] = FALSE;
        }
        break;

      case 'week':
        if ($next_prev >= 1 && $next_prev < $last_week_of_year) {
          $next_prev_val['next'] = $next_prev + 1;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        elseif ($next_prev == $last_week_of_year) {
          $next_prev_val['next'] = FALSE;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        break;

      case 'month':
        if ($next_prev >= 1 && $next_prev < $last_month_of_year) {
          $next_prev_val['next'] = $next_prev + 1;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        elseif ($next_prev == $last_month_of_year) {
          $next_prev_val['next'] = FALSE;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        break;

      case 'year':
        if ($next_prev > $min_year_range && $next_prev < $max_year_range) {
          $next_prev_val['next'] = $next_prev + 1;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        elseif ($next_prev == $max_year_range) {
          $next_prev_val['next'] = FALSE;
          $next_prev_val['prev'] = $next_prev - 1;
        }
        elseif ($next_prev == $min_year_range) {
          $next_prev_val['next'] = $next_prev + 1;
          $next_prev_val['prev'] = FALSE;
        }
        break;
    }
    return $next_prev_val;
  }

  /**
   * Checks for valid formatter and next_prev value supplied.
   */
  public function astrologyCheckValidDate($formatter, $next_prev) {

    if (!is_numeric($next_prev)) {
      return FALSE;
    }

    // If leap year, set number of days in year.
    $last_day_of_year = date('L') ? 365 : 364;

    // If leap year, set max week number in year.
    $last_week_of_year = date('L') ? 53 : 52;

    $last_month_of_year = 12;
    $max_year_range = date('Y') + 1;
    $min_year_range = date('Y') - 1;

    if ($formatter == 'day' && ($next_prev < 0 || $next_prev > $last_day_of_year)) {
      return FALSE;
    }
    if ($formatter == 'week' && ($next_prev < 1 || $next_prev > $last_week_of_year)) {
      return FALSE;
    }
    if ($formatter == 'month' && ($next_prev < 1 || $next_prev > $last_month_of_year)) {
      return FALSE;
    }
    if ($formatter == 'year' && ($next_prev < $min_year_range || $next_prev > $max_year_range)) {
      return FALSE;
    }
    return TRUE;
  }

}
