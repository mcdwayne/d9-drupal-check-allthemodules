<?php

namespace Drupal\appointment_calendar\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

class AppointmentCalendarListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_calendar_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    // Default year.
    $default_year = date('Y', time());
    $beginOfDay = strtotime("midnight", time());
    $endOfDay = strtotime("tomorrow", time()) - 1;
    $from_date = \Drupal::request()->query->get('date');
    $to_date = \Drupal::request()->query->get('todate');
    if ($from_date == '') {
      $from_date = DrupalDateTime::createFromTimestamp($beginOfDay);
    }
    else {
      $from_date = DrupalDateTime::createFromTimestamp($from_date);
    }
    if ($to_date == '') {
      $to_date = DrupalDateTime::createFromTimestamp($endOfDay);
    }
    else {
      $to_date = DrupalDateTime::createFromTimestamp($to_date);
    }
    $form['filter_date'] = [
      '#type' => 'datetime',
      '#title' => t('From date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => $default_year . ':+3',
      '#default_value' => $from_date,
    ];
    $form['filter_to_date'] = [
      '#type' => 'datetime',
      '#title' => t('To date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => $default_year . ':+3',
      '#default_value' => $to_date,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Filter'),
    ];
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => t('Reset'),
    ];
    // Headers.
    $headers = [t('Date'), t('No. Slots'), t('Operations')];
    $db_conn = \Drupal::database();
    $date_query = $db_conn->select('appointment_date', 'ad');
    $date_query->fields('ad');
    if (\Drupal::request()->query->get('date')) {
      $date_query->condition('date', \Drupal::request()->query->get('date'), '>=');
    }
    if (\Drupal::request()->query->get('todate')) {
      $date_query->condition('date', \Drupal::request()->query->get('todate'), '<=');
    }
    $date_query->orderBy('date');
    $table_sort = $date_query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderBy('date');
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $date_result = $pager->execute();
    $rows = [];
    foreach ($date_result as $date) {
      $capacity = appointment_calendar_slot_capacity($date->date);
      $slots = count((array) json_decode($capacity));
      $view = Html::escape($base_url . '/admin/appointment-calendar/view?date=' . $date->date);
      $edit = Html::escape($base_url . '/admin/appointment-calendar/edit?date=' . $date->date);
      $delete = Html::escape($base_url . '/admin/appointment-calendar/delete?date=' . $date->date);
      $row = [];
      $row[] = date('Y-m-d', $date->date);
      $row[] = $slots;
      $row[] = Markup::create('<div><a href="' . $view . '">' . t('View') . '</a></div><div><a href="' . $edit . '">' . t('Edit') . '</a></div><div><a href="' . $delete . '">' . t('Delete') . '</a></div>');
      $rows[] = $row;
    }
    $form['data'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows
    ];
    $form['pager'] = [
      '#type' => 'pager'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];
    if ($op == $this->t('Filter')) {
      $filter_date = $values['filter_date']->getTimestamp();
      $filter_to_date = $values['filter_to_date']->getTimestamp();
      if ($filter_date > $filter_to_date) {
        $form_state->setErrorByName('filter_date', $this->t('From Date is greater than "TO" date'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];
    // Goto current path if reset.
    if ($op == $this->t('Reset')) {
      $form_state->setRedirect('appointment_calendar.list_page');
    }
    // Pass values to url.
    if ($op == $this->t('Filter')) {
      $filter_date = $values['filter_date']->getTimestamp();
      $filter_to_date = $values['filter_to_date']->getTimestamp();
      $params['date'] = Html::escape($filter_date);
      $params['todate'] = Html::escape($filter_to_date);
      $form_state->setRedirect('appointment_calendar.list_page', [$params]);
    }
  }

}
