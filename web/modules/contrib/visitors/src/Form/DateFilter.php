<?php

namespace Drupal\visitors\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Form\FormStateInterface;

class DateFilter extends FormBase {
  protected $_order = array('month', 'day', 'year');

  /**
   * @return array
   */
  protected function _getOrder() {
    return $this->_order;
  }


  public function getFormID() {
    return 'visitors_date_filter_form';
  }

  /**
   * Set to session info default values for visitors date filter.
   */
  protected function _setSessionDateRange() {
    if (!isset($_SESSION['visitors_from'])) {
      $_SESSION['visitors_from'] = array(
        'month' => date('n'),
        'day'   => 1,
        'year'  => date('Y'),
      );
    }

    if (!isset($_SESSION['visitors_to'])) {
      $_SESSION['visitors_to'] = array(
        'month' => date('n'),
        'day'   => date('j'),
        'year'  => date('Y'),
      );
    }
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->_setSessionDateRange();

    $from = DrupalDateTime::createFromArray($_SESSION['visitors_from']);
    $to   = DrupalDateTime::createFromArray($_SESSION['visitors_to']);

    $form = array();

    $form['visitors_date_filter'] = array(
      '#collapsed'        => FALSE,
      '#collapsible'      => FALSE,
      '#description'      => t('Choose date range'),
      '#title'            => t('Date filter'),
      '#type'             => 'fieldset',
    );

    $form['visitors_date_filter']['from'] = array(
      '#date_part_order'  => $this->_getOrder(),
      '#date_timezone'    => drupal_get_user_timezone(),
      '#default_value'    => $from,
      '#element_validate' => array(array($this, 'datelistValidate')),
      '#process'          => array(array($this, 'formProcessDatelist')),
      '#title'            => t('From'),
      '#type'             => 'datelist',
      '#value_callback'   => array($this, 'datelistValueCallback'),
    );

    $form['visitors_date_filter']['to'] = array(
      '#date_part_order'  => $this->_getOrder(),
      '#date_timezone'    => drupal_get_user_timezone(),
      '#default_value'    => $to,
      '#element_validate' => array(array($this, 'datelistValidate')),
      '#process'          => array(array($this, 'formProcessDatelist')),
      '#title'            => t('To'),
      '#type'             => 'datelist',
      '#value_callback'   => array($this, 'datelistValueCallback'),
    );

    $form['submit'] = array(
      '#type'             => 'submit',
      '#value'            => t('Save'),
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fromvalue          = $form_state->getValue('from');
    $tovalue     = $form_state->getValue('to');
    $from = array();
    $to = array();
    $from['month'] = (int) $fromvalue->format('m');
    $from['day']   = (int) $fromvalue->format('d');
    $from['year']  = (int) $fromvalue->format('y');

    $to['month']   = (int) $tovalue->format('m');
    $to['day']     = (int) $tovalue->format('d');
    $to['year']    = (int) $tovalue->format('y');

    $error_message = $this->t('The specified date is invalid.');

    if (!checkdate($from['month'], $from['day'], $from['year'])) {
      return $this->setFormError('from', $form_state, $error_message);
    }

    if (!checkdate($to['month'], $to['day'], $to['year'])) {
      return $this->setFormError('to', $form_state, $error_message);
    }

    $from = mktime(0, 0, 0, $from['month'], $from['day'], $from['year']);
    $to   = mktime(23, 59, 59, $to['month'], $to['day'], $to['year']);

    if ((int) $from <= 0) {
      return $this->setFormError('from', $form_state, $error_message);
    }

    if ((int) $to <= 0) {
      return $this->setFormError('to', $form_state, $error_message);
    }

    if ($from > $to) {
      return $this->setFormError('from', $form_state, $error_message);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from = $form_state->getValue('from');
    $to   = $form_state->getValue('to');

    $_SESSION['visitors_from'] = array(
      'month' => $from->format('n'),
      'day'   => $from->format('j'),
      'year'  => $from->format('Y'),
    );

    $_SESSION['visitors_to'] = array(
      'month' => $to->format('n'),
      'day'   => $to->format('j'),
      'year'  => $to->format('Y'),
    );
  }

  /**
   * Validates the date type to prevent invalid dates (e.g., February 30,
   * 2006).
   *
   * If the date is valid, the date is set in the form as a string
   * using the format designated in __toString().
   */
  public function datelistValidate($element, FormStateInterface $form_state) {
    $input_exists = FALSE;

    $input = NestedArray::getValue(
      $form_state->getValues(),
      $element['#parents'],
      $input_exists
    );

    if (!$input_exists) {
      return;
    }

    // If there's empty input, set an error.
    if (
      empty($input['year']) ||
      empty($input['month']) ||
      empty($input['day'])
    ) {
      $form_state->setError($element,$this->t('The %field date is required.'));
      return;
    }

    if (!checkdate($input['month'], $input['day'], $input['year'])) {
      $this->setError($element,$this->t('The specified date is invalid.'));
      return;
    }
    $date = DrupalDateTime::createFromArray($input);
    if ($date instanceOf DrupalDateTime && !$date->hasErrors()) {
      $form_state->setValueForElement($element, $date);
      return;
    }
    $form_state->setError($element,$this->t('The %field date is invalid.'));
  }

  /**
   * Element value callback for datelist element.
   */
  public function datelistValueCallback($element, $input = FALSE, &$form_state = array()) {
    $parts  = $this->_getOrder();
    $return = array_fill_keys($parts, '');

    foreach ($parts as $part) {
      $return[$part] = $input[$part];
    }

    return $return;
  }

  public function formProcessDatelist($element, &$form_state) {
    if (
      empty($element['#value']['month']) ||
      empty($element['#value']['day']) ||
      empty($element['#value']['year'])
    ) {
      $element['#value'] = array(
        'month' => $element['#default_value']->format('n'),
        'day'   => $element['#default_value']->format('j'),
        'year'  => $element['#default_value']->format('Y')
      );
    }

    $element['#tree'] = TRUE;

    // Output multi-selector for date.
    foreach ($this->_getOrder() as $part) {
      switch ($part) {
        case 'month':
          $options = DateHelper::monthNamesAbbr(TRUE);
          $title = t('Month');
          break;

        case 'day':
          $options = DateHelper::days(TRUE);
          $title = t('Day');
          break;

        case 'year':
          $options = DateHelper::years($this->_getMinYear(), date('Y'), TRUE);
          $title = t('Year');
          break;
      }

      $element['#attributes']['title'] = $title;

      $element[$part] = array(
        '#attributes'    => $element['#attributes'],
        '#options'       => $options,
        '#required'      => $element['#required'],
        '#title'         => $title,
        '#title_display' => 'invisible',
        '#type'          => 'select',
        '#value'         => (int) $element['#value'][$part],
      );
    }

    return $element;
  }

  /**
   * Get min year for date fields visitors date filter.
   * Min year - min value from {visitors} table.
   *
   * @return int min year
   */
  protected function _getMinYear() {
    $query = db_select('visitors');
    $query->addExpression('MIN(visitors_date_time)');
    $min_timestamp = $query->execute()->fetchField();

    $timezone = drupal_get_user_timezone();

    return format_date($min_timestamp, 'custom', 'Y', $timezone);
  }
}

