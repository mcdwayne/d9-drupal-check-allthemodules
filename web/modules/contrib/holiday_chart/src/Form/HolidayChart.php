<?php

namespace Drupal\holiday_chart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Displays the Cart form.
 */
class HolidayChart extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'holiday_chart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $user = $this->currentUser();

    $form = [];
    $dates = [];
    if (!empty($_GET['month']) && !empty($_GET['year'])) {
      $month = $_GET['month'];
      $year = $_GET['year'];

      $q = db_select('holiday_chart', 'holy');
      $q->fields('holy', array('holiday_date'));
      $q->condition('holy.month', $month);
      $q->condition('holy.year', $year);
      $q->condition('holy.holiday', 'H');
      $holidays = $q->execute()->fetchAllKeyed(0, 0);

      for ($d = 1; $d <= 31; $d++) {
        $time = mktime(12, 0, 0, $month, $d, $year);
        if (date('m', $time) == $month)
          $dates[] = date('d-m-Y::D', $time);
      }
    }

    $months = [
      '' => '--Select--',
      '01' => 'January',
      '02' => 'February',
      '03' => 'March',
      '04' => 'April',
      '05' => 'May',
      '06' => 'June',
      '07' => 'July',
      '08' => 'August',
      '09' => 'September',
      '10' => 'October',
      '11' => 'November',
      '12' => 'December'
    ];

    $years = [];
    $years[] = '--Select--';
    foreach (range(2000, 2050) as $i) {
      $years[$i] = $i;
    }
    $form['month'] = [
      '#type' => 'select',
      '#title' => t('Select Month :'),
      '#options' => $months,
      '#default_value' => $month,
      '#prefix' => '<div class="row"><div class="col-md-4">',
      '#suffix' => '</div>',
    ];

    $form['year'] = [
      '#type' => 'select',
      '#title' => t('Select Year :'),
      '#options' => $years,
      '#default_value' => $year,
      '#prefix' => '<div class="col-md-4">',
      '#suffix' => '</div>',
    ];

    $form['filter'] = [
      '#type' => 'submit',
      '#value' => 'Filter',
      '#prefix' => '<div class="col-md-2">',
      '#suffix' => '</div>',
    ];

    $form['reset'] = [
      '#type' => 'submit',
      '#value' => 'Reset',
      '#prefix' => '<div class="col-md-2">',
      '#suffix' => '</div></div>',
    ];

    $form['filters'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#suffix' => '<div class="holiday-wrapper"><div class="holiday-box start">',
    ];

    if (!empty($dates)) {
      foreach ($dates as $key => $date) {

        $item = [];

        $explode = explode('::', $date);

        if (in_array($explode[0], $holidays) || $explode[1] == 'Sun') {
          $class = 'holiday';
          $holiday = 'H';
        }
        else {
          $class = 'working';
          $holiday = 'W';
        }

        if (!in_array($explode[0], $holidays) && $explode[1] == 'Sun') {
          // insert default sat and sun
          $query = \Drupal::database()->merge('holiday_chart');
          $query->fields([
            'holiday_date' => $explode[0],
            'month' => $month,
            'year' => $year,
            'timestamp' => strtotime($explode[0]),
            'holiday' => 'H',
          ]);
          $query->key(['holiday_date' => $explode[0]])->execute();
        }

        $dexplode = explode('-', $explode[0]);

        $item['date'] = $explode[0];
        $item['sdate'] = $dexplode[0];
        $item['day'] = $explode[1];
        $item['class'] = $class;
        $item['holiday'] = $holiday;
        $item['month'] = $month;
        $item['year'] = $year;
        $item['style'] = ($explode[1] == 'Sun') ? 'pointer-events: none;' : '';
        if ($explode[1] == 'Sat') {
          $item['break'] = 1;
        }
        $date_block = ['#theme' => 'holiday_chart_date_view', '#item' => $item];

        $form['date_' . $key] = [
          '#type' => 'markup',
          '#markup' => render($date_block),
        ];
      }

      $form['end_mark'] = [
        '#type' => 'markup',
        '#prefix' => '</div></div></div>',
      ];
    }

    $form['#attached']['library'][] = 'holiday_chart/holiday-chart-js';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $op = $form_state->getValue("op");

    switch ($op) {
      case 'Filter':
        $month = $form_state->getValue('month');
        $year = $form_state->getValue('year');
        $url = Url::fromUri('internal:' . "/holiday-chart?month=$month&year=$year");
        $form_state->setRedirectUrl($url);
        break;
      case 'Reset':
        $url = Url::fromUri('internal:' . "/holiday-chart");
        $form_state->setRedirectUrl($url);
        break;
    }
  }

}
